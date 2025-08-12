import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useNotifications } from '@/composables/useNotifications';

export interface SessionSyncOptions {
  syncInterval?: number; // minutes
  warningTime?: number; // minutes before expiry
  autoExtend?: boolean;
}

export function useSessionSync(options: SessionSyncOptions = {}) {
  const authStore = useAuthStore();
  const { showWarning, showError } = useNotifications();
  
  const {
    syncInterval = 5, // 5 minutes
    warningTime = 5, // 5 minutes before expiry
    autoExtend = true
  } = options;

  const isActive = ref(false);
  const lastSyncTime = ref<Date | null>(null);
  const syncInterval_id = ref<number | null>(null);
  const warningShown = ref(false);

  // Computed properties
  const timeUntilExpiry = computed(() => {
    return authStore.sessionTimeRemaining;
  });

  const shouldShowWarning = computed(() => {
    return timeUntilExpiry.value > 0 && 
           timeUntilExpiry.value <= warningTime && 
           !warningShown.value;
  });

  const isExpired = computed(() => {
    return timeUntilExpiry.value <= 0;
  });

  // Start session synchronization
  const startSync = (): void => {
    if (isActive.value || !authStore.isAuthenticated) return;

    isActive.value = true;
    console.log('Starting session synchronization...');

    // Initial sync
    syncSession();

    // Set up periodic sync
    syncInterval_id.value = window.setInterval(() => {
      syncSession();
    }, syncInterval * 60 * 1000);

    // Set up warning check (every minute)
    const warningCheck = setInterval(() => {
      checkForWarnings();
    }, 60 * 1000);

    // Store warning interval for cleanup
    (syncInterval_id.value as any).warningCheck = warningCheck;
  };

  // Stop session synchronization
  const stopSync = (): void => {
    if (!isActive.value) return;

    isActive.value = false;
    console.log('Stopping session synchronization...');

    if (syncInterval_id.value) {
      clearInterval(syncInterval_id.value);
      
      // Clear warning check if it exists
      if ((syncInterval_id.value as any).warningCheck) {
        clearInterval((syncInterval_id.value as any).warningCheck);
      }
      
      syncInterval_id.value = null;
    }

    warningShown.value = false;
  };

  // Synchronize session with backend
  const syncSession = async (): Promise<void> => {
    if (!authStore.isAuthenticated) {
      stopSync();
      return;
    }

    try {
      console.log('Synchronizing session...');
      const isValid = await authStore.validateSession();
      
      if (!isValid) {
        console.warn('Session is no longer valid');
        handleSessionExpired();
        return;
      }

      lastSyncTime.value = new Date();
      console.log('Session synchronized successfully');

      // Auto-extend session if it's expiring soon and auto-extend is enabled
      if (autoExtend && authStore.isSessionExpiringSoon) {
        await extendSession();
      }

    } catch (error) {
      console.error('Session sync failed:', error);
      // Don't immediately logout on sync failure, but show warning
      showError(
        'Session Sync Failed',
        'Unable to verify your session. Please check your connection.'
      );
    }
  };

  // Extend the current session
  const extendSession = async (): Promise<boolean> => {
    if (!authStore.isAuthenticated) return false;

    try {
      console.log('Extending session...');
      const extended = await authStore.extendSession();
      
      if (extended) {
        console.log('Session extended successfully');
        warningShown.value = false; // Reset warning flag
        return true;
      } else {
        console.warn('Failed to extend session');
        return false;
      }
    } catch (error) {
      console.error('Session extension failed:', error);
      return false;
    }
  };

  // Check for session warnings
  const checkForWarnings = (): void => {
    if (!authStore.isAuthenticated) return;

    // Check if session is expired
    if (isExpired.value) {
      handleSessionExpired();
      return;
    }

    // Show warning if session is expiring soon
    if (shouldShowWarning.value) {
      showSessionWarning();
    }
  };

  // Show session expiry warning
  const showSessionWarning = (): void => {
    warningShown.value = true;
    
    showWarning(
      'Session Expiring Soon',
      `Your session will expire in ${timeUntilExpiry.value} minutes. Click here to extend it.`,
      {
        duration: 0, // Don't auto-dismiss
        action: {
          label: 'Extend Session',
          handler: async () => {
            const extended = await extendSession();
            if (!extended) {
              showError(
                'Extension Failed',
                'Unable to extend your session. Please save your work and log in again.'
              );
            }
          }
        }
      }
    );
  };

  // Handle session expiry
  const handleSessionExpired = (): void => {
    console.warn('Session has expired');
    stopSync();
    
    showError(
      'Session Expired',
      'Your session has expired. You will be redirected to the login page.',
      {
        duration: 5000
      }
    );

    // Logout after a short delay to allow user to see the message
    setTimeout(() => {
      authStore.logout();
    }, 2000);
  };

  // Force session validation
  const validateSession = async (): Promise<boolean> => {
    if (!authStore.isAuthenticated) return false;

    try {
      const isValid = await authStore.validateSession();
      if (!isValid) {
        handleSessionExpired();
      }
      return isValid;
    } catch (error) {
      console.error('Session validation failed:', error);
      return false;
    }
  };

  // Handle user activity
  const handleActivity = (): void => {
    if (!authStore.isAuthenticated) return;
    
    authStore.updateActivity();
    
    // Reset warning if user is active
    if (warningShown.value && timeUntilExpiry.value > warningTime) {
      warningShown.value = false;
    }
  };

  // Lifecycle hooks
  onMounted(() => {
    if (authStore.isAuthenticated) {
      startSync();
    }
  });

  onUnmounted(() => {
    stopSync();
  });

  return {
    // State
    isActive,
    lastSyncTime,
    timeUntilExpiry,
    isExpired,
    shouldShowWarning,

    // Methods
    startSync,
    stopSync,
    syncSession,
    extendSession,
    validateSession,
    handleActivity,
    handleSessionExpired,
  };
}