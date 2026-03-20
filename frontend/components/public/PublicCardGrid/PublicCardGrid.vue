<template>
  <div :class="styles.grid" :data-test="dataTest">
    <NuxtLink v-for="item in items" :key="item.to" :to="item.to" :class="styles.link">
      <UiCard
        :variant="item.variant ?? variant"
        :padding="padding"
        interactive
        :class="item.imageUrl ? styles.mediaCard : styles.copyCard"
        :data-test="item.dataTest ?? 'public-card-grid-item'"
      >
        <div v-if="item.imageUrl" :class="styles.media">
          <img
            :src="item.imageUrl"
            :alt="item.imageAlt || item.title"
            :class="styles.mediaImage"
            loading="lazy"
          />
          <span v-if="item.badge" :class="styles.badge">{{ item.badge }}</span>
        </div>
        <div :class="styles.copy">
          <p v-if="item.eyebrow" :class="styles.cardEyebrow">{{ item.eyebrow }}</p>
          <p v-if="item.price" :class="styles.cardPrice">{{ item.price }}</p>
          <h3 :class="styles.cardTitle">{{ item.title }}</h3>
          <p :class="styles.cardText">{{ item.description }}</p>
          <p v-if="item.meta" :class="styles.cardMeta">{{ item.meta }}</p>
        </div>
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
  imageUrl?: string | null;
  imageAlt?: string;
  eyebrow?: string;
  price?: string;
  meta?: string;
  badge?: string;
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
