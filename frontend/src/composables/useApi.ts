import { ref, computed } from "vue";
import { apiService } from "@/services/api";
import type { ApiResponse } from "@/types";

export function useApi<T = any>() {
  const data = ref<T | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const isLoading = computed(() => loading.value);
  const hasError = computed(() => !!error.value);
  const hasData = computed(() => !!data.value);

  const execute = async <R = T>(
    apiCall: () => Promise<{ data: ApiResponse<R> }>,
    options: {
      onSuccess?: (data: R) => void;
      onError?: (error: string) => void;
      showErrorToast?: boolean;
    } = {},
  ): Promise<R | null> => {
    try {
      loading.value = true;
      error.value = null;

      const response = await apiCall();
      const result = response.data.data;

      data.value = result as unknown as T;

      if (options.onSuccess) {
        options.onSuccess(result);
      }

      return result;
    } catch (err: any) {
      const errorMessage =
        err.response?.data?.message || err.message || "An error occurred";
      error.value = errorMessage;

      if (options.onError) {
        options.onError(errorMessage);
      }

      if (options.showErrorToast !== false) {
        // TODO: Show toast notification
        console.error("API Error:", errorMessage);
      }

      return null;
    } finally {
      loading.value = false;
    }
  };

  const reset = () => {
    data.value = null;
    loading.value = false;
    error.value = null;
  };

  // Direct HTTP methods
  const get = async (url: string, config?: any) => {
    return execute(() =>
      import("@/services/api").then((api) => api.get(url, config)),
    );
  };

  const post = async (url: string, data?: any, config?: any) => {
    return execute(() =>
      import("@/services/api").then((api) => api.post(url, data, config)),
    );
  };

  const put = async (url: string, data?: any, config?: any) => {
    return execute(() =>
      import("@/services/api").then((api) => api.put(url, data, config)),
    );
  };

  const del = async (url: string, config?: any) => {
    return execute(() =>
      import("@/services/api").then((api) => api.del(url, config)),
    );
  };

  return {
    data,
    loading,
    error,
    isLoading,
    hasError,
    hasData,
    execute,
    reset,
    get,
    post,
    put,
    delete: del,
  };
}

// Specific API composables
export function useAuth() {
  const { execute, ...rest } = useApi();

  const login = (credentials: { email: string; password: string }) =>
    execute(() => apiService.auth.login(credentials));

  const logout = () => execute(() => apiService.auth.logout());

  const fetchUser = () => execute(() => apiService.auth.me());

  return {
    ...rest,
    login,
    logout,
    fetchUser,
  };
}

export function useDashboard() {
  const { execute, ...rest } = useApi();

  const fetchKPIs = () => execute(() => apiService.dashboard.getKPIs());

  const fetchWidgets = () => execute(() => apiService.dashboard.getWidgets());

  const saveWidgetLayout = (layout: any) =>
    execute(() => apiService.dashboard.saveWidgetLayout(layout));

  return {
    ...rest,
    fetchKPIs,
    fetchWidgets,
    saveWidgetLayout,
  };
}
