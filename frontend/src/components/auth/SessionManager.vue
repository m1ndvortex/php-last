<template>
  <div>
    <h4 class="text-md font-medium text-gray-900 mb-4">
      {{ $t("auth.active_sessions") }}
    </h4>

    <div v-if="isLoading" class="text-center py-4">
      <div
        class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"
      ></div>
      <p class="mt-2 text-sm text-gray-600">{{ $t("common.loading") }}</p>
    </div>

    <div v-else-if="sessions.length === 0" class="text-center py-4">
      <p class="text-sm text-gray-600">{{ $t("auth.no_active_sessions") }}</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="session in sessions"
        :key="session.id"
        class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
        :class="{ 'bg-green-50 border-green-200': session.is_current }"
      >
        <div class="flex-1">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg
                v-if="session.is_current"
                class="h-5 w-5 text-green-500"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clip-rule="evenodd"
                />
              </svg>
              <svg
                v-else
                class="h-5 w-5 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-gray-900">
                {{ getDeviceInfo(session.user_agent) }}
                <span
                  v-if="session.is_current"
                  class="text-green-600 font-normal"
                >
                  ({{ $t("auth.current_session") }})
                </span>
              </p>
              <p class="text-xs text-gray-500">
                {{ session.ip_address }} •
                {{ formatDate(session.last_activity) }}
              </p>
            </div>
          </div>
        </div>

        <div v-if="!session.is_current">
          <button
            @click="revokeSession(session.id)"
            :disabled="isRevoking"
            class="btn btn-sm btn-outline-danger"
          >
            {{ $t("auth.revoke") }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="sessions.length > 1" class="mt-4 pt-4 border-t">
      <button
        @click="revokeAllOtherSessions"
        :disabled="isRevoking"
        class="btn btn-outline-danger"
      >
        {{ $t("auth.revoke_all_other_sessions") }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useNotifications } from "@/composables/useNotifications";
import { apiService } from "@/services/api";
import type { SessionData } from "@/types/auth";

const { showSuccess, showError } = useNotifications();

const sessions = ref<SessionData[]>([]);
const isLoading = ref(false);
const isRevoking = ref(false);

const fetchSessions = async () => {
  try {
    isLoading.value = true;
    const response = await apiService.auth.getSessions();
    sessions.value = response.data;
  } catch (error: any) {
    showError(
      "Load Failed",
      error.response?.data?.message || "Failed to load sessions",
    );
  } finally {
    isLoading.value = false;
  }
};

const revokeSession = async (sessionId: string) => {
  if (!confirm("Are you sure you want to revoke this session?")) {
    return;
  }

  try {
    isRevoking.value = true;
    await apiService.auth.revokeSession(sessionId);

    // Remove from local list
    sessions.value = sessions.value.filter((s) => s.id !== sessionId);

    showSuccess("Session Revoked", "The session has been revoked successfully");
  } catch (error: any) {
    showError(
      "Revoke Failed",
      error.response?.data?.message || "Failed to revoke session",
    );
  } finally {
    isRevoking.value = false;
  }
};

const revokeAllOtherSessions = async () => {
  if (
    !confirm(
      "Are you sure you want to revoke all other sessions? This will log you out from all other devices.",
    )
  ) {
    return;
  }

  try {
    isRevoking.value = true;
    await apiService.auth.revokeAllSessions();

    // Keep only current session
    sessions.value = sessions.value.filter((s) => s.is_current);

    showSuccess(
      "Sessions Revoked",
      "All other sessions have been revoked successfully",
    );
  } catch (error: any) {
    showError(
      "Revoke Failed",
      error.response?.data?.message || "Failed to revoke sessions",
    );
  } finally {
    isRevoking.value = false;
  }
};

const getDeviceInfo = (userAgent: string): string => {
  // Simple device detection
  if (userAgent.includes("Mobile")) return "Mobile Device";
  if (userAgent.includes("Chrome")) return "Chrome Browser";
  if (userAgent.includes("Firefox")) return "Firefox Browser";
  if (userAgent.includes("Safari")) return "Safari Browser";
  if (userAgent.includes("Edge")) return "Edge Browser";
  return "Unknown Device";
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInMinutes = Math.floor(
    (now.getTime() - date.getTime()) / (1000 * 60),
  );

  if (diffInMinutes < 1) return "Just now";
  if (diffInMinutes < 60) return `${diffInMinutes} minutes ago`;
  if (diffInMinutes < 1440)
    return `${Math.floor(diffInMinutes / 60)} hours ago`;
  return date.toLocaleDateString();
};

onMounted(() => {
  fetchSessions();
});
</script>
