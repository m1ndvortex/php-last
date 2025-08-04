<template>
  <div class="max-w-2xl mx-auto">
    <div class="card">
      <div class="card-header">
        <h3 class="text-lg font-medium text-gray-900">
          {{ $t("auth.profile") }}
        </h3>
      </div>

      <div class="card-body space-y-6">
        <!-- Profile Information -->
        <div>
          <h4 class="text-md font-medium text-gray-900 mb-4">
            {{ $t("common.profile_information") }}
          </h4>

          <form @submit.prevent="updateProfile" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="name" class="form-label">
                  {{ $t("common.name") }}
                </label>
                <input
                  id="name"
                  v-model="profileForm.name"
                  type="text"
                  required
                  :disabled="isUpdatingProfile"
                  class="form-input"
                />
              </div>

              <div>
                <label for="email" class="form-label">
                  {{ $t("auth.email") }}
                </label>
                <input
                  id="email"
                  v-model="profileForm.email"
                  type="email"
                  required
                  :disabled="isUpdatingProfile"
                  class="form-input"
                />
              </div>
            </div>

            <div>
              <label for="preferred_language" class="form-label">
                {{ $t("common.preferred_language") }}
              </label>
              <select
                id="preferred_language"
                v-model="profileForm.preferred_language"
                :disabled="isUpdatingProfile"
                class="form-select"
              >
                <option value="en">{{ $t("language.english") }}</option>
                <option value="fa">{{ $t("language.persian") }}</option>
              </select>
            </div>

            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="isUpdatingProfile || !isProfileFormValid"
                class="btn btn-primary"
              >
                <span v-if="isUpdatingProfile">{{ $t("common.saving") }}</span>
                <span v-else>{{ $t("common.save") }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Change Password -->
        <div class="border-t pt-6">
          <h4 class="text-md font-medium text-gray-900 mb-4">
            {{ $t("auth.change_password") }}
          </h4>

          <form @submit.prevent="changePassword" class="space-y-4">
            <div>
              <label for="current_password" class="form-label">
                {{ $t("auth.current_password") }}
              </label>
              <input
                id="current_password"
                v-model="passwordForm.current_password"
                type="password"
                required
                :disabled="isChangingPassword"
                class="form-input"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="new_password" class="form-label">
                  {{ $t("auth.new_password") }}
                </label>
                <input
                  id="new_password"
                  v-model="passwordForm.new_password"
                  type="password"
                  required
                  :disabled="isChangingPassword"
                  class="form-input"
                />
              </div>

              <div>
                <label for="confirm_password" class="form-label">
                  {{ $t("auth.confirm_password") }}
                </label>
                <input
                  id="confirm_password"
                  v-model="passwordForm.confirm_password"
                  type="password"
                  required
                  :disabled="isChangingPassword"
                  class="form-input"
                />
              </div>
            </div>

            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="isChangingPassword || !isPasswordFormValid"
                class="btn btn-primary"
              >
                <span v-if="isChangingPassword">{{ $t("common.saving") }}</span>
                <span v-else>{{ $t("auth.change_password") }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Two-Factor Authentication -->
        <div class="border-t pt-6">
          <TwoFactorAuth />
        </div>

        <!-- Session Management -->
        <div class="border-t pt-6">
          <SessionManager />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useNotifications } from "@/composables/useNotifications";
import { apiService } from "@/services/api";
import TwoFactorAuth from "./TwoFactorAuth.vue";
import SessionManager from "./SessionManager.vue";

const authStore = useAuthStore();
const { showSuccess, showError } = useNotifications();

const isUpdatingProfile = ref(false);
const isChangingPassword = ref(false);

const profileForm = ref({
  name: "",
  email: "",
  preferred_language: "en",
});

const passwordForm = ref({
  current_password: "",
  new_password: "",
  confirm_password: "",
});

const isProfileFormValid = computed(() => {
  return (
    profileForm.value.name.trim() !== "" &&
    profileForm.value.email.trim() !== "" &&
    profileForm.value.preferred_language !== ""
  );
});

const isPasswordFormValid = computed(() => {
  return (
    passwordForm.value.current_password.trim() !== "" &&
    passwordForm.value.new_password.trim() !== "" &&
    passwordForm.value.confirm_password.trim() !== "" &&
    passwordForm.value.new_password === passwordForm.value.confirm_password &&
    passwordForm.value.new_password.length >= 8
  );
});

const updateProfile = async () => {
  if (!isProfileFormValid.value) return;

  try {
    isUpdatingProfile.value = true;

    // Call API to update profile
    await apiService.auth.updateProfile(profileForm.value);

    // Update auth store
    authStore.updateUser(profileForm.value);

    showSuccess(
      "Profile updated successfully",
      "Your profile information has been updated.",
    );
  } catch (error: any) {
    showError(
      "Update Failed",
      error.response?.data?.message || "Failed to update profile",
    );
  } finally {
    isUpdatingProfile.value = false;
  }
};

const changePassword = async () => {
  if (!isPasswordFormValid.value) return;

  try {
    isChangingPassword.value = true;

    // Call API to change password
    await apiService.auth.changePassword({
      current_password: passwordForm.value.current_password,
      password: passwordForm.value.new_password,
      password_confirmation: passwordForm.value.confirm_password,
    });

    // Clear form
    passwordForm.value = {
      current_password: "",
      new_password: "",
      confirm_password: "",
    };

    showSuccess(
      "Password Changed",
      "Your password has been changed successfully.",
    );
  } catch (error: any) {
    showError(
      "Change Failed",
      error.response?.data?.message || "Failed to change password",
    );
  } finally {
    isChangingPassword.value = false;
  }
};

onMounted(() => {
  if (authStore.user) {
    profileForm.value = {
      name: authStore.user.name,
      email: authStore.user.email,
      preferred_language: authStore.user.preferred_language,
    };
  }
});
</script>
