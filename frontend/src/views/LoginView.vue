<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <h2 class="text-3xl font-bold text-gray-900">
          {{ $t('auth.login') }}
        </h2>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="card">
        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <label for="email" class="form-label">
              {{ $t('auth.email') }}
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="form-input"
            />
          </div>

          <div>
            <label for="password" class="form-label">
              {{ $t('auth.password') }}
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="form-input"
            />
          </div>

          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember-me"
                v-model="form.remember"
                type="checkbox"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                {{ $t('auth.remember_me') }}
              </label>
            </div>

            <div class="text-sm">
              <a href="#" class="font-medium text-primary-600 hover:text-primary-500">
                {{ $t('auth.forgot_password') }}
              </a>
            </div>
          </div>

          <div>
            <button
              type="submit"
              :disabled="loading"
              class="w-full btn btn-primary"
            >
              {{ loading ? $t('common.loading') : $t('auth.login') }}
            </button>
          </div>
        </form>
        
        <!-- Language Switcher -->
        <div class="mt-6 text-center">
          <button
            @click="toggleLanguage"
            class="text-sm text-gray-600 hover:text-gray-900"
          >
            {{ $t('language.switch_to_' + (locale === 'en' ? 'persian' : 'english')) }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const { locale } = useI18n()
const router = useRouter()

const loading = ref(false)
const form = ref({
  email: '',
  password: '',
  remember: false,
})

const handleLogin = async () => {
  loading.value = true
  try {
    // TODO: Implement actual login logic
    console.log('Login attempt:', form.value)
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Redirect to dashboard
    router.push('/dashboard')
  } catch (error) {
    console.error('Login error:', error)
  } finally {
    loading.value = false
  }
}

const toggleLanguage = () => {
  locale.value = locale.value === 'en' ? 'fa' : 'en'
}</script>