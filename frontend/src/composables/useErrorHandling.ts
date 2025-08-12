import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotifications } from './useNotifications'

interface ApiError {
  success: boolean
  error: string
  message: string
  details?: {
    type: string
    code: number
    timestamp: string
    [key: string]: any
  }
  errors?: Record<string, string[]>
  unavailable_items?: Array<{
    item_id: number
    item_name?: string
    item_sku?: string
    requested_quantity: number
    available_quantity: number
    error: string
  }>
  pricing_data?: Record<string, any>
  inventory_data?: Record<string, any>
}

export function useErrorHandling() {
  const { t } = useI18n()
  const { showError, showWarning } = useNotifications()
  
  const currentError = ref<ApiError | null>(null)
  const isLoading = ref(false)
  
  const hasError = computed(() => currentError.value !== null)
  const errorMessage = computed(() => currentError.value?.message || '')
  const errorType = computed(() => currentError.value?.error || '')
  
  /**
   * Clear current error state
   */
  const clearError = () => {
    currentError.value = null
  }
  
  /**
   * Handle API errors with appropriate user feedback
   */
  const handleApiError = (error: any): void => {
    console.error('API Error:', error)
    
    // Handle network errors
    if (!error.response) {
      const networkError: ApiError = {
        success: false,
        error: 'network_error',
        message: t('errors.network_error'),
        details: {
          type: 'network_error',
          code: 0,
          timestamp: new Date().toISOString()
        }
      }
      currentError.value = networkError
      showError(t('errors.network_error'), networkError.message)
      return
    }
    
    const apiError: ApiError = error.response.data
    currentError.value = apiError
    
    // Handle specific error types
    switch (apiError.error) {
      case 'insufficient_inventory':
        handleInsufficientInventoryError(apiError)
        break
        
      case 'pricing_error':
        handlePricingError(apiError)
        break
        
      case 'inventory_error':
        handleInventoryError(apiError)
        break
        
      case 'validation_failed':
        handleValidationError(apiError)
        break
        
      case 'unauthenticated':
        handleAuthenticationError(apiError)
        break
        
      case 'resource_not_found':
      case 'endpoint_not_found':
        handleNotFoundError(apiError)
        break
        
      case 'database_error':
        handleDatabaseError(apiError)
        break
        
      default:
        handleGenericError(apiError)
        break
    }
  }
  
  /**
   * Handle insufficient inventory errors with detailed feedback
   */
  const handleInsufficientInventoryError = (error: ApiError): void => {
    if (error.unavailable_items && error.unavailable_items.length > 0) {
      const itemsList = error.unavailable_items
        .map(item => `${item.item_name || item.item_sku || `Item ${item.item_id}`}: ${t('inventory.requested')} ${item.requested_quantity}, ${t('inventory.available')} ${item.available_quantity}`)
        .join('\n')
      
      showError(t('errors.inventory.insufficient_stock'), `${error.message}\n\n${t('inventory.unavailable_items')}:\n${itemsList}`)
    } else {
      showError(t('errors.inventory.insufficient_stock'), error.message)
    }
  }
  
  /**
   * Handle pricing calculation errors
   */
  const handlePricingError = (error: ApiError): void => {
    let message = error.message
    
    if (error.pricing_data) {
      const details = Object.entries(error.pricing_data)
        .map(([key, value]) => `${key}: ${value}`)
        .join(', ')
      message += `\n${t('pricing.error_details')}: ${details}`
    }
    
    showError(t('errors.pricing.calculation_failed'), message)
  }
  
  /**
   * Handle general inventory errors
   */
  const handleInventoryError = (error: ApiError): void => {
    showError(t('errors.inventory.failed_to_create_item'), error.message)
  }
  
  /**
   * Handle validation errors with field-specific messages
   */
  const handleValidationError = (error: ApiError): void => {
    if (error.errors) {
      const fieldErrors = Object.entries(error.errors)
        .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
        .join('\n')
      
      showError(t('errors.validation_failed'), `${error.message}\n\n${fieldErrors}`)
    } else {
      showError(t('errors.validation_failed'), error.message)
    }
  }
  
  /**
   * Handle authentication errors
   */
  const handleAuthenticationError = (error: ApiError): void => {
    showError(t('errors.auth.invalid_credentials'), error.message)
    
    // Redirect to login after a delay
    setTimeout(() => {
      window.location.href = '/login'
    }, 2000)
  }
  
  /**
   * Handle not found errors
   */
  const handleNotFoundError = (error: ApiError): void => {
    showWarning(t('errors.not_found'), error.message)
  }
  
  /**
   * Handle database errors
   */
  const handleDatabaseError = (error: ApiError): void => {
    showError(t('errors.database.connection_failed'), error.message || t('errors.database.connection_failed'))
  }
  
  /**
   * Handle generic errors
   */
  const handleGenericError = (error: ApiError): void => {
    showError(t('errors.something_went_wrong'), error.message || t('errors.something_went_wrong'))
  }
  
  /**
   * Wrap async operations with error handling
   */
  const withErrorHandling = async <T>(
    operation: () => Promise<T>,
    options: {
      loadingState?: boolean
      showSuccessMessage?: string
      customErrorHandler?: (error: any) => void
    } = {}
  ): Promise<T | null> => {
    const { loadingState = true, showSuccessMessage, customErrorHandler } = options
    
    try {
      if (loadingState) {
        isLoading.value = true
      }
      clearError()
      
      const result = await operation()
      
      if (showSuccessMessage) {
        // Assuming we have a success notification method
        // showSuccess(showSuccessMessage)
      }
      
      return result
    } catch (error) {
      if (customErrorHandler) {
        customErrorHandler(error)
      } else {
        handleApiError(error)
      }
      return null
    } finally {
      if (loadingState) {
        isLoading.value = false
      }
    }
  }
  
  /**
   * Get user-friendly error message for specific error types
   */
  const getErrorMessage = (errorType: string, fallback?: string): string => {
    const errorMessages: Record<string, string> = {
      network_error: t('errors.network_error'),
      server_error: t('errors.server_error'),
      validation_failed: t('errors.validation_failed'),
      unauthorized: t('errors.unauthorized'),
      not_found: t('errors.not_found'),
      insufficient_inventory: t('errors.inventory.insufficient_stock'),
      pricing_error: t('errors.pricing.calculation_failed'),
      inventory_error: t('errors.inventory.failed_to_create_item'),
      database_error: t('errors.database.connection_failed')
    }
    
    return errorMessages[errorType] || fallback || t('errors.something_went_wrong')
  }
  
  /**
   * Check if error is retryable
   */
  const isRetryableError = (error: ApiError): boolean => {
    const retryableErrors = [
      'network_error',
      'server_error',
      'database_error'
    ]
    
    return retryableErrors.includes(error.error)
  }
  
  /**
   * Format error for logging
   */
  const formatErrorForLogging = (error: any): Record<string, any> => {
    return {
      message: error.message,
      status: error.response?.status,
      statusText: error.response?.statusText,
      url: error.config?.url,
      method: error.config?.method,
      data: error.response?.data,
      timestamp: new Date().toISOString()
    }
  }
  
  return {
    // State
    currentError,
    isLoading,
    hasError,
    errorMessage,
    errorType,
    
    // Methods
    clearError,
    handleApiError,
    withErrorHandling,
    getErrorMessage,
    isRetryableError,
    formatErrorForLogging
  }
}