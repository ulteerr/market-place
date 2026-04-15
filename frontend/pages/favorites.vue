<template>
  <div>
    <PageHero
      :eyebrow="t('app.public.favorites.eyebrow')"
      :title="heroTitle"
      :description="heroDescription"
    />

    <PublicSection
      class="favorites-page"
      :title="t('app.public.favorites.sectionTitle')"
      data-test="favorites-page"
    >
      <PublicCardGridSkeleton v-if="pageState === 'loading'" data-test="favorites-loading" />
      <PublicStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.favorites.errorTitle')"
        :description="t('app.public.favorites.errorDescription')"
        data-test="favorites-error"
      />
      <PublicStateMessage
        v-else-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.favorites.emptyTitle')"
        :description="t('app.public.favorites.emptyDescription')"
        data-test="favorites-empty"
      />
      <PublicCardGrid
        v-else
        :items="favoriteCards"
        show-favorite-button
        data-test="favorites-grid"
        @toggle-favorite="toggleFavorite"
      />
    </PublicSection>
  </div>
</template>

<script setup lang="ts">
import PageHero from '~/components/ui/PageHero/PageHero.vue';
import PublicCardGrid from '~/components/public/PublicCardGrid/PublicCardGrid.vue';
import PublicCardGridSkeleton from '~/components/public/PublicCardGridSkeleton/PublicCardGridSkeleton.vue';
import PublicSection from '~/components/public/PublicSection/PublicSection.vue';
import PublicStateMessage from '~/components/public/PublicStateMessage/PublicStateMessage.vue';
import { usePublicPreviewState } from '~/composables/layout/usePublicPreviewState';
import { buildPublicActivityPath, usePublicActivities } from '~/composables/usePublicActivities';
import { usePublicPageSeo } from '~/composables/seo/usePublicPageSeo';
import { useFavorites } from '~/composables/useFavorites';

definePageMeta({
  layout: 'default',
});

const { t } = useI18n();
const previewState = usePublicPreviewState();
const publicActivitiesApi = usePublicActivities();
const { favoriteKeys, isFavorite, toggleFavorite } = useFavorites();

const seo = usePublicPageSeo({
  h1: computed(() => t('app.public.favorites.heroTitle')),
  title: computed(() => t('app.public.favorites.seoTitle')),
  description: computed(() => t('app.public.favorites.heroDescription')),
});
const heroTitle = seo.h1;
const heroDescription = seo.description;

const {
  data: favoriteActivities,
  pending,
  error,
} = await useAsyncData(
  () => `public-favorites:${favoriteKeys.value.join(',')}`,
  async () => {
    if (previewState.value !== 'ready' || favoriteKeys.value.length === 0) {
      return [];
    }

    const responses = await Promise.allSettled(
      favoriteKeys.value.map(async (publicKey) => ({
        publicKey,
        activity: await publicActivitiesApi.show(publicKey),
      }))
    );

    return responses.flatMap((entry) => (entry.status === 'fulfilled' ? [entry.value] : []));
  },
  {
    server: previewState.value === 'ready',
    lazy: false,
    default: () => [],
    watch: [favoriteKeys],
  }
);

const pageState = computed<'loading' | 'ready' | 'empty' | 'error'>(() => {
  if (previewState.value === 'loading') {
    return 'loading';
  }

  if (previewState.value === 'error') {
    return 'error';
  }

  if (pending.value) {
    return 'loading';
  }

  if (error.value) {
    return 'error';
  }

  if (!favoriteActivities.value.length) {
    return 'empty';
  }

  return 'ready';
});

const favoriteCards = computed(() =>
  favoriteActivities.value.map(({ publicKey, activity }) => ({
    to: buildPublicActivityPath({
      public_key: publicKey,
      primary_category: activity.primary_category,
    }),
    title: activity.name,
    description: activity.short_description,
    imageUrl: activity.cover?.url || null,
    imageAlt: activity.name,
    eyebrow: [activity.primary_category?.parent?.name, activity.primary_category?.name]
      .filter(Boolean)
      .join(' / '),
    price:
      activity.price_from != null
        ? `${activity.price_from} ${activity.currency || ''}`.trim()
        : undefined,
    meta: [activity.organization?.name, activity.location?.city?.name].filter(Boolean).join(' · '),
    dataTest: `favorite-activity-${activity.id}`,
    favoriteKey: publicKey,
    isFavorite: isFavorite(publicKey),
  }))
);
</script>

<style scoped lang="scss">
.favorites-page {
  max-width: 88rem;
  padding-inline: 1.25rem;
}
</style>
