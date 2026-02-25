<template>
  <NuxtLink :to="to" class="admin-link">
    <slot />
  </NuxtLink>
</template>

<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router';

defineProps<{
  to: RouteLocationRaw;
}>();
</script>

<style scoped lang="scss">
.admin-link {
  color: #93c5fd;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.admin-link:hover {
  color: #bfdbfe;
}
</style>
