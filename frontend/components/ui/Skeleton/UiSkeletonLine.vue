<template>
  <span
    :class="styles.line"
    :style="{
      width,
      height,
      borderRadius,
    }"
    :data-test="dataTest"
    aria-hidden="true"
  />
</template>

<script setup lang="ts">
import styles from './UiSkeleton.module.scss';

withDefaults(
  defineProps<{
    width?: string;
    height?: string;
    borderRadius?: string;
    dataTest?: string;
  }>(),
  {
    width: '100%',
    height: '0.85rem',
    borderRadius: '999px',
    dataTest: 'ui-skeleton-line',
  }
);
</script>
