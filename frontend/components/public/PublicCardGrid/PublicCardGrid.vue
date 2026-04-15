<template>
  <div :class="styles.grid" :data-test="dataTest">
    <div v-for="item in items" :key="item.to" :class="styles.item">
      <UiCard
        :variant="item.variant ?? variant"
        :padding="padding"
        :class="item.imageUrl ? styles.mediaCard : styles.copyCard"
        :data-test="item.dataTest ?? 'public-card-grid-item'"
      >
        <NuxtLink :to="item.to" :class="styles.stretchedLink" :aria-label="item.title" />
        <div v-if="item.imageUrl" :class="styles.media">
          <img
            :src="item.imageUrl"
            :alt="item.imageAlt || item.title"
            :class="styles.mediaImage"
            loading="lazy"
          />
          <span v-if="item.badge" :class="styles.badge">{{ item.badge }}</span>
          <button
            v-if="showFavoriteButton && item.favoriteKey"
            type="button"
            :class="[styles.favoriteButton, item.isFavorite ? styles.favoriteButtonActive : '']"
            :aria-pressed="item.isFavorite ? 'true' : 'false'"
            :aria-label="
              item.isFavorite
                ? item.favoriteActiveLabel || item.favoriteLabel || item.title
                : item.favoriteLabel || item.title
            "
            @click.stop.prevent="emit('toggleFavorite', item.favoriteKey)"
          >
            <span aria-hidden="true">{{ item.isFavorite ? '♥' : '♡' }}</span>
          </button>
        </div>
        <div :class="styles.copy">
          <p v-if="item.eyebrow" :class="styles.cardEyebrow">{{ item.eyebrow }}</p>
          <p v-if="item.price" :class="styles.cardPrice">{{ item.price }}</p>
          <h3 :class="styles.cardTitle">{{ item.title }}</h3>
          <p :class="styles.cardText">{{ item.description }}</p>
          <p v-if="item.meta" :class="styles.cardMeta">{{ item.meta }}</p>
        </div>
      </UiCard>
    </div>
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
  favoriteKey?: string;
  isFavorite?: boolean;
  favoriteLabel?: string;
  favoriteActiveLabel?: string;
};

const emit = defineEmits<{
  toggleFavorite: [favoriteKey: string];
}>();

withDefaults(
  defineProps<{
    items: PublicCardGridItem[];
    variant?: CardVariant;
    padding?: 'sm' | 'md' | 'lg';
    dataTest?: string;
    showFavoriteButton?: boolean;
  }>(),
  {
    variant: 'default',
    padding: 'lg',
    dataTest: 'public-card-grid',
    showFavoriteButton: false,
  }
);
</script>
