<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          {{ $t('app.name') }}
        </h1>
        <p class="text-gray-600 mb-8">
          {{ $t('app.description') }}
        </p>
        
        <!-- Language Switcher -->
        <div class="mb-8">
          <button
            @click="toggleLanguage"
            class="btn btn-secondary"
          >
            {{ $t('language.switch_to_' + (locale === 'en' ? 'persian' : 'english')) }}
          </button>
        </div>
        
        <!-- Navigation -->
        <div class="space-y-4">
          <router-link
            to="/login"
            class="block w-full btn btn-primary"
          >
            {{ $t('auth.login') }}
          </router-link>
          
          <router-link
            to="/dashboard"
            class="block w-full btn btn-secondary"
          >
            {{ $t('navigation.dashboard') }}
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { locale, t } = useI18n()

const toggleLanguage = () => {
  locale.value = locale.value === 'en' ? 'fa' : 'en'
}</script>