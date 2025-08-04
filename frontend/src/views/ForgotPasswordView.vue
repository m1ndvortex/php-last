<template>
  <div
    class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8"
  >
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <h2 class="text-3xl font-bold text-gray-900">
          {{ $t("auth.forgot_password") }}
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          {{ $t("auth.forgot_password_description") }}
        </p>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="card">
        <!-- Success Message -->
        <div
          v-if="emailSent"
          class="mb-4 p-3 bg-green-50 border border-green-200 rounded-md"
        >
          <p class="text-sm text-green-600">
            {{ $t("auth.reset_email_sent") }}
          </p>
        </div>

        <!-- Error Message -->
        <div
          v-if="error"
          class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md"
        >
          <p class="text-sm text-red-600">{{ error }}</p>
        </div>

        <form
          v-if="!emailSent"
          @submit.prevent="handleForgotPassword"
          class="space-y-6"
        >
          <div>
            <label for="email" class="form-label">
              {{ $t("auth.email") }}
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              :disabled="isLoading"
              class="form-input"
              :class="{
                'opacity-50': isLoading,
                'border-red-300 focus:border-red-500 focus:ring-red-500':
                  validationErrors.email,
              }"
              @blur="validateField('email')"
            />
            <p v-if="validationErrors.email" class="mt-1 text-sm text-red-600">
              {{ validationErrors.email }}
            </p>
          </div>

          <div>
            <button
              type="submit"
              :disabled="isLoading || !isFormValid"
              class="w-full btn btn-primary"
              :class="{
                'opacity-50 cursor-not-allowed': isLoading || !isFormValid,
              }"
            >
              <span v-if="isLoading" class="flex items-center justify-center">
                <svg
                  class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  ></circle>
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  ></path>
                </svg>
                {{ $t("common.loading") }}
              </span>
              <span v-else>{{ $t("auth.send_reset_link") }}</span>
            </button>
          </div>
        </form>

        <div v-else class="text-center">
          <p class="text-sm text-gray-600 mb-4">
            {{ $t("auth.check_email_instructions") }}
          </p>
          <button
            @click="resendEmail"
            :disabled="isLoading"
            class="btn btn-outline"
          >
            {{ $t("auth.resend_email") }}
          </button>
        </div>

        <div class="mt-6 text-center">
          <router-link
            to="/login"
            class="text-sm font-medium text-primary-600 hover:text-primary-500"
          >
            {{ $t("auth.back_to_login") }}
          </router-link>
        </div>

        <!-- Language Switcher -->
        <div class="mt-6 text-center">
          <LanguageSwitcher />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { apiService } from "@/services/api";
import LanguageSwitcher from "@/components/ui/LanguageSwitcher.vue";

const form = ref({
  email: "",
});

const isLoading = ref(false);
const error = ref<string | null>(null);
const emailSent = ref(false);
const validationErrors = ref<Record<string, string>>({});

const isFormValid = computed(() => {
  return (
    form.value.email.trim() !== "" &&
    Object.keys(validationErrors.value).length === 0
  );
});

const validateField = (field: string) => {
  switch (field) {
    case "email":
      if (!form.value.email.trim()) {
        validationErrors.value.email = "Email is required";
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
        validationErrors.value.email = "Please enter a valid email address";
      } else {
        delete validationErrors.value.email;
      }
      break;
  }
};

const validateForm = () => {
  validateField("email");
  return Object.keys(validationErrors.value).length === 0;
};

const handleForgotPassword = async () => {
  if (!validateForm()) return;

  try {
    isLoading.value = true;
    error.value = null;

    await apiService.auth.forgotPassword({ email: form.value.email });
    emailSent.value = true;
  } catch (err: any) {
    error.value = err.response?.data?.message || "Failed to send reset email";
  } finally {
    isLoading.value = false;
  }
};

const resendEmail = async () => {
  await handleForgotPassword();
};
</script>
