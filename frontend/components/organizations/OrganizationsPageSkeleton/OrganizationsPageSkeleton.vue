<template>
  <section :class="styles.page" :data-test="dataTest" aria-hidden="true">
    <UiHeroSkeleton data-test="organizations-page-hero-skeleton" />

    <div v-if="showMetrics" :class="styles.metrics">
      <UiCardSkeleton
        v-for="index in 3"
        :key="`metric-${index}`"
        :data-test="`organizations-metric-skeleton-${index}`"
      />
    </div>

    <div :class="styles.grid">
      <UiCardSkeleton
        v-for="index in normalizedCards"
        :key="`card-${index}`"
        :data-test="`organizations-card-skeleton-${index}`"
      />
    </div>

    <UiListSkeleton :items="listItems" data-test="organizations-page-list-skeleton" />
  </section>
</template>

<script setup lang="ts">
import UiCardSkeleton from '~/components/ui/Skeleton/UiCardSkeleton.vue';
import UiHeroSkeleton from '~/components/ui/Skeleton/UiHeroSkeleton.vue';
import UiListSkeleton from '~/components/ui/Skeleton/UiListSkeleton.vue';
import styles from './OrganizationsPageSkeleton.module.scss';

const props = withDefaults(
  defineProps<{
    cards?: number;
    listItems?: number;
    showMetrics?: boolean;
    dataTest?: string;
  }>(),
  {
    cards: 2,
    listItems: 4,
    showMetrics: false,
    dataTest: 'organizations-page-skeleton',
  }
);

const normalizedCards = computed(() => Math.max(1, props.cards));
</script>
