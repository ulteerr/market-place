<template>
  <article :class="styles.card" :data-test="dataTest" aria-hidden="true">
    <UiSkeletonLine :class="styles.title" />
    <UiSkeletonLine />
    <UiSkeletonLine :class="styles.subtitle" />
    <UiSkeletonLine :class="styles.meta" />
  </article>
</template>

<script setup lang="ts">
import styles from './UiSkeleton.module.scss';
import UiSkeletonLine from './UiSkeletonLine.vue';

withDefaults(
  defineProps<{
    dataTest?: string;
  }>(),
  {
    dataTest: 'ui-card-skeleton',
  }
);
</script>
