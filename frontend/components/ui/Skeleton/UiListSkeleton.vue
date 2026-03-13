<template>
  <div :class="styles.list" :data-test="dataTest" aria-hidden="true">
    <div v-for="item in normalizedItems" :key="`item-${item}`" :class="styles.listItem">
      <UiSkeletonLine width="40%" />
      <UiSkeletonLine />
    </div>
  </div>
</template>

<script setup lang="ts">
import styles from './UiSkeleton.module.scss';
import UiSkeletonLine from './UiSkeletonLine.vue';

const props = withDefaults(
  defineProps<{
    items?: number;
    dataTest?: string;
  }>(),
  {
    items: 6,
    dataTest: 'ui-list-skeleton',
  }
);

const normalizedItems = computed(() => Math.max(1, props.items));
</script>
