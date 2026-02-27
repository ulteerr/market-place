<template>
  <span
    class="inline-block h-2.5 w-2.5 rounded-full border border-[var(--border)]"
    :style="{ backgroundColor: color || 'transparent' }"
    aria-hidden="true"
  />
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    color?: string | null;
  }>(),
  {
    color: null,
  }
);
</script>
