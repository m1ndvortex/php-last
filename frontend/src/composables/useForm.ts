import { ref, reactive, computed } from "vue";
import type { FormErrors } from "@/types";

export function useForm<T extends Record<string, any>>(initialData: T) {
  const form = reactive<T>({ ...initialData });
  const errors = ref<FormErrors>({});
  const loading = ref(false);

  const hasErrors = computed(() => Object.keys(errors.value).length > 0);

  const getError = (field: string): string | null => {
    const fieldErrors = errors.value[field];
    return fieldErrors && fieldErrors.length > 0 ? fieldErrors[0] : null;
  };

  const setError = (field: string, message: string) => {
    errors.value[field] = [message];
  };

  const setErrors = (newErrors: FormErrors) => {
    errors.value = newErrors;
  };

  const clearError = (field: string) => {
    delete errors.value[field];
  };

  const clearErrors = () => {
    errors.value = {};
  };

  const reset = () => {
    Object.assign(form, initialData);
    clearErrors();
  };

  const validate = (rules: Record<string, (value: any) => string | null>) => {
    clearErrors();

    for (const [field, rule] of Object.entries(rules)) {
      const error = rule(form[field]);
      if (error) {
        setError(field, error);
      }
    }

    return !hasErrors.value;
  };

  const submit = async (
    submitFn: (data: T) => Promise<any>,
    options: {
      onSuccess?: (result: any) => void;
      onError?: (error: any) => void;
      resetOnSuccess?: boolean;
    } = {},
  ) => {
    try {
      loading.value = true;
      clearErrors();

      const result = await submitFn({ ...form } as T);

      if (options.onSuccess) {
        options.onSuccess(result);
      }

      if (options.resetOnSuccess) {
        reset();
      }

      return result;
    } catch (error: any) {
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      }

      if (options.onError) {
        options.onError(error);
      }

      throw error;
    } finally {
      loading.value = false;
    }
  };

  return {
    form,
    errors,
    loading,
    hasErrors,
    getError,
    setError,
    setErrors,
    clearError,
    clearErrors,
    reset,
    validate,
    submit,
  };
}
