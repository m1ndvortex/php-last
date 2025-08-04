<template>
  <div
    class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8"
  >
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <h2 class="text-3xl font-bold text-gray-900">
          {{ $t("auth.login") }}
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          {{ $t("auth.login_description") }}
        </p>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="card">
        <!-- Error Message -->
        <div
          v-if="authStore.error"
          class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md"
        >
          <p class="text-sm text-red-600">{{ authStore.error }}</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <label for="email" class="form-label">
              {{ $t("auth.email") }}
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              :disabled="authStore.isLoading"
              class="form-input"
              :class="{
                'opacity-50': authStore.isLoading,
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
            <label for="password" class="form-label">
              {{ $t("auth.password") }}
            </label>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                :disabled="authStore.isLoading"
                class="form-input pr-10"
                :class="{
                  'opacity-50': authStore.isLoading,
                  'border-red-300 focus:border-red-500 focus:ring-red-500':
                    validationErrors.password,
                }"
                @blur="validateField('password')"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <svg
                  v-if="showPassword"
                  class="h-5 w-5 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"
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
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
              </button>
            </div>
            <p
              v-if="validationErrors.password"
              class="mt-1 text-sm text-red-600"
            >
              {{ validationErrors.password }}
            </p>
          </div>

          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember-me"
                v-model="form.remember"
                type="checkbox"
                :disabled="authStore.isLoading"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                {{ $t("auth.remember_me") }}
              </label>
            </div>

            <div class="text-sm">
              <router-link
                to="/forgot-password"
                class="font-medium text-primary-600 hover:text-primary-500"
              >
                {{ $t("auth.forgot_password") }}
              </router-link>
            </div>
          </div>

          <div>
            <button
              type="submit"
              :disabled="authStore.isLoading || !isFormValid"
              class="w-full btn btn-primary"
              :class="{
                'opacity-50 cursor-not-allowed':
                  authStore.isLoading || !isFormValid,
              }"
            >
              <span
                v-if="authStore.isLoading"
                class="flex items-center justify-center"
              >
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
              <span v-else>{{ $t("auth.login") }}</span>
            </button>
          </div>
        </form>

        <!-- Language Switcher -->
        <div class="mt-6 text-center">
          <LanguageSwitcher />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import LanguageSwitcher from "@/components/ui/LanguageSwitcher.vue";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const form = ref({
  email: "",
  password: "",
  remember: false,
});

const showPassword = ref(false);
const validationErrors = ref<Record<string, string>>({});

const isFormValid = computed(() => {
  return (
    form.value.email.trim() !== "" &&
    form.value.password.trim() !== "" &&
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
    case "password":
      if (!form.value.password.trim()) {
        validationErrors.value.password = "Password is required";
      } else if (form.value.password.length < 6) {
        validationErrors.value.password =
          "Password must be at least 6 characters";
      } else {
        delete validationErrors.value.password;
      }
      break;
  }
};

const validateForm = () => {
  validateField("email");
  validateField("password");
  return Object.keys(validationErrors.value).length === 0;
};

const handleLogin = async () => {
  if (!validateForm()) return;

  const result = await authStore.login({
    email: form.value.email,
    password: form.value.password,
  });

  if (result.success) {
    // Redirect to intended route or dashboard
    const redirectTo = (route.query.redirect as string) || "/dashboard";
    router.push(redirectTo);
  }
};

// Clear any existing errors when component mounts
onMounted(() => {
  authStore.error = null;
  validationErrors.value = {};
});
</script>
