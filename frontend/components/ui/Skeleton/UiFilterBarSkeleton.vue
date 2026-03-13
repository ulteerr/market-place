<template>
  <div :class="styles.filterBar" :data-test="dataTest" aria-hidden="true">
    <UiSkeletonLine v-for="chip in normalizedChips" :key="`chip-${chip}`" :class="styles.chip" />
    <UiSkeletonLine width="2.75rem" />
  </div>
</template>

<script setup lang="ts">
import styles from './UiSkeleton.module.scss';
import UiSkeletonLine from './UiSkeletonLine.vue';

const props = withDefaults(
  defineProps<{
    chips?: number;
    dataTest?: string;
  }>(),
  {
    chips: 4,
    dataTest: 'ui-filter-bar-skeleton',
  }
);

const normalizedChips = computed(() => Math.max(1, props.chips));
</script>
