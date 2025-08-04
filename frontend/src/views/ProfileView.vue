<template>
  <div class="container mx-auto px-4 py-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">
        {{ $t("auth.profile") }}
      </h1>
      <p class="mt-1 text-sm text-gray-600">
        {{ $t("auth.profile_description") }}
      </p>
    </div>

    <UserProfile />
  </div>
</template>

<script setup lang="ts">
import UserProfile from "@/components/auth/UserProfile.vue";
</script>
