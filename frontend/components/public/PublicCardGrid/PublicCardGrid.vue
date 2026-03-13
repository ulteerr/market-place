<template>
  <div :class="styles.grid" :data-test="dataTest">
    <NuxtLink v-for="item in items" :key="item.to" :to="item.to" :class="styles.link">
      <UiCard
        :variant="item.variant ?? variant"
        :padding="padding"
        interactive
        :data-test="item.dataTest ?? 'public-card-grid-item'"
      >
        <h3 :class="styles.cardTitle">{{ item.title }}</h3>
        <p :class="styles.cardText">{{ item.description }}</p>
      </UiCard>
    </NuxtLink>
  </div>
</template>

<script setup lang="ts">
import UiCard from '~/components/ui/Card/UiCard.vue';
import styles from './PublicCardGrid.module.scss';

type CardVariant = 'default' | 'elevated' | 'outline';

type PublicCardGridItem = {
  title: string;
  description: string;
  to: string;
  variant?: CardVariant;
  dataTest?: string;
};

withDefaults(
  defineProps<{
    items: PublicCardGridItem[];
    variant?: CardVariant;
    padding?: 'sm' | 'md' | 'lg';
    dataTest?: string;
  }>(),
  {
    variant: 'default',
    padding: 'lg',
    dataTest: 'public-card-grid',
  }
);
</script>
