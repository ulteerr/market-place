<template>
  <section :class="styles.hero" :data-test="dataTest" aria-hidden="true">
    <UiSkeletonLine width="10rem" />
    <UiSkeletonLine width="62%" height="1.6rem" />
    <UiSkeletonLine width="88%" />
    <UiSkeletonLine width="73%" />
    <div :class="styles.buttonRow">
      <UiSkeletonLine :class="styles.button" />
      <UiSkeletonLine :class="styles.button" />
    </div>
  </section>
</template>

<script setup lang="ts">
import styles from './UiSkeleton.module.scss';
import UiSkeletonLine from './UiSkeletonLine.vue';

withDefaults(
  defineProps<{
    dataTest?: string;
  }>(),
  {
    dataTest: 'ui-hero-skeleton',
  }
);
</script>
