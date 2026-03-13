<template>
  <div :class="styles.grid" :data-test="dataTest" aria-hidden="true">
    <UiCardSkeleton v-for="item in normalizedItems" :key="`public-skeleton-${item}`" />
  </div>
</template>

<script setup lang="ts">
import UiCardSkeleton from '~/components/ui/Skeleton/UiCardSkeleton.vue';
import styles from './PublicCardGridSkeleton.module.scss';

const props = withDefaults(
  defineProps<{
    items?: number;
    dataTest?: string;
  }>(),
  {
    items: 3,
    dataTest: 'public-card-grid-skeleton',
  }
);

const normalizedItems = computed(() => Math.max(1, props.items));
</script>
