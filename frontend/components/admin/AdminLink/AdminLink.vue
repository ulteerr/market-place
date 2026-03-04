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

<style lang="scss" scoped src="./AdminLink.scss"></style>
