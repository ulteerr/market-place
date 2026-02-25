<template>
  <LoginForm
    variant="popup"
    :description="description"
    :require-admin-access="requireAdminAccess"
    :success-redirect-to="successRedirectTo"
    :denied-redirect-to="deniedRedirectTo"
  />
</template>

<script setup lang="ts">
import LoginForm from './LoginForm.vue';

withDefaults(
  defineProps<{
    description?: string;
    requireAdminAccess?: boolean;
    successRedirectTo?: string;
    deniedRedirectTo?: string;
  }>(),
  {
    description: '',
    requireAdminAccess: false,
    successRedirectTo: '/admin',
    deniedRedirectTo: '/',
  }
);
</script>
