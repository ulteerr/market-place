<template>
  <component
    :is="to ? AdminLink : 'span'"
    v-bind="to ? { to } : {}"
    class="inline-flex max-w-full min-w-0 flex-wrap items-center gap-2 align-top"
  >
    <span
      class="inline-block h-2.5 w-2.5 rounded-full border border-[var(--border)]"
      :style="{ backgroundColor: color || 'transparent' }"
      aria-hidden="true"
    />
    <span :class="['min-w-0 break-words', labelClass]">{{ name }}</span>
  </component>
</template>

<script setup lang="ts">
import AdminLink from '~/components/admin/AdminLink.vue';

withDefaults(
  defineProps<{
    name: string;
    color?: string | null;
    to?: string;
    labelClass?: string;
  }>(),
  {
    color: null,
    to: '',
    labelClass: '',
  }
);
</script>
