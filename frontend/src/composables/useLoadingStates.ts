import { ref, computed } from 'vue'

interface LoadingState {
  [key: string]: boolean
}

export function useLoadingStates(initialStates: LoadingState = {}) {
  const loadingStates = ref<LoadingState>(initialStates)

  // Set loading state for a specific key
  const setLoading = (key: string, loading: boolean) => {
    loadingStates.value[key] = loading
  }

  // Get loading state for a specific key
  const isLoading = (key: string) => {
    return loadingStates.value[key] || false
  }

  // Check if any loading state is active
  const isAnyLoading = computed(() => {
    return Object.values(loadingStates.value).some(loading => loading)
  })

  // Start loading for a key
  const startLoading = (key: string) => {
    setLoading(key, true)
  }

  // Stop loading for a key
  const stopLoading = (key: string) => {
    setLoading(key, false)
  }

  // Execute a function with loading state
  const withLoading = async <T>(
    key: string,
    fn: () => Promise<T>
  ): Promise<T> => {
    startLoading(key)
    try {
      return await fn()
    } finally {
      stopLoading(key)
    }
  }

  // Reset all loading states
  const resetLoading = () => {
    Object.keys(loadingStates.value).forEach(key => {
      loadingStates.value[key] = false
    })
  }

  return {
    loadingStates,
    setLoading,
    isLoading,
    isAnyLoading,
    startLoading,
    stopLoading,
    withLoading,
    resetLoading
  }
}

// Specific loading composable for common operations
export function useOperationLoading() {
  return useLoadingStates({
    fetching: false,
    creating: false,
    updating: false,
    deleting: false,
    saving: false,
    loading: false
  })
}