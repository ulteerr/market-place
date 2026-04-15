<template>
  <div>
    <PublicSection
      class="home-page__section"
      :title="t('app.public.home.featuredSectionTitle')"
      data-test="home-featured-activities"
    >
      <PublicCardGridSkeleton
        v-if="featuredSectionState === 'loading'"
        data-test="home-featured-activities-loading"
      />
      <PublicStateMessage
        v-else-if="featuredSectionState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.home.featuredErrorTitle')"
        :description="t('app.public.home.featuredErrorDescription')"
        data-test="home-featured-activities-error"
      />
      <PublicStateMessage
        v-else-if="featuredSectionState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.home.featuredEmptyTitle')"
        :description="t('app.public.home.featuredEmptyDescription')"
        data-test="home-featured-activities-empty"
      />
      <PublicCardGrid
        v-else
        :items="featuredCards"
        show-favorite-button
        data-test="home-featured-activities-grid"
        @toggle-favorite="toggleFavorite"
      />
    </PublicSection>

    <PublicSection
      class="home-page__section"
      :title="t('app.public.home.newSectionTitle')"
      data-test="home-new-activities"
    >
      <PublicCardGridSkeleton
        v-if="feedSectionState === 'loading'"
        data-test="home-new-activities-loading"
      />
      <PublicStateMessage
        v-else-if="feedSectionState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.home.feedErrorTitle')"
        :description="t('app.public.home.feedErrorDescription')"
        data-test="home-new-activities-error"
      />
      <PublicStateMessage
        v-else-if="feedSectionState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.home.feedEmptyTitle')"
        :description="t('app.public.home.feedEmptyDescription')"
        data-test="home-new-activities-empty"
      />
      <PublicCardGrid
        v-else
        :items="newCards"
        show-favorite-button
        data-test="home-new-activities-grid"
        @toggle-favorite="toggleFavorite"
      />
    </PublicSection>

    <PublicSection
      v-if="categorySection"
      class="home-page__section"
      :title="t('app.public.home.byCategorySectionTitle', { name: categorySection.label })"
      data-test="home-category-activities"
    >
      <PublicCardGrid
        :items="categorySection.cards"
        show-favorite-button
        data-test="home-category-activities-grid"
        @toggle-favorite="toggleFavorite"
      />
    </PublicSection>

    <PublicSection
      v-if="citySection"
      class="home-page__section"
      :title="t('app.public.home.byCitySectionTitle', { name: citySection.label })"
      data-test="home-city-activities"
    >
      <PublicCardGrid
        :items="citySection.cards"
        show-favorite-button
        data-test="home-city-activities-grid"
        @toggle-favorite="toggleFavorite"
      />
    </PublicSection>

    <PublicSection
      v-if="organizationSection"
      class="home-page__section"
      :title="t('app.public.home.byOrganizationSectionTitle', { name: organizationSection.label })"
      data-test="home-organization-activities"
    >
      <PublicCardGrid
        :items="organizationSection.cards"
        show-favorite-button
        data-test="home-organization-activities-grid"
        @toggle-favorite="toggleFavorite"
      />
    </PublicSection>
  </div>
</template>

<script setup lang="ts">
import { usePublicPreviewState } from '~/composables/layout/usePublicPreviewState';
import { buildPublicActivityPath, usePublicActivities } from '~/composables/usePublicActivities';
import { useFavorites } from '~/composables/useFavorites';
import { usePublicPageSeo } from '~/composables/seo/usePublicPageSeo';
import { buildPublicHomeSchemaNodes } from '~/composables/schema/public-home-schema';
import { usePublicSchemaNode } from '~/composables/schema/usePublicSchemaRegistry';
import PublicCardGridSkeleton from '~/components/public/PublicCardGridSkeleton/PublicCardGridSkeleton.vue';
import PublicCardGrid from '~/components/public/PublicCardGrid/PublicCardGrid.vue';
import PublicSection from '~/components/public/PublicSection/PublicSection.vue';
import PublicStateMessage from '~/components/public/PublicStateMessage/PublicStateMessage.vue';

const { t } = useI18n();
const previewState = usePublicPreviewState();
const config = useRuntimeConfig();
const publicActivitiesApi = usePublicActivities();
const { isFavorite, toggleFavorite } = useFavorites();

const {
  data: initialHomeData,
  pending: initialHomePending,
  error: initialHomeError,
} = await useAsyncData(
  'public-home-initial',
  async () => {
    if (previewState.value !== 'ready') {
      return {
        featured: [],
        feedItems: [],
      };
    }

    const [featuredResponse, homeFeedResponse] = await Promise.all([
      publicActivitiesApi.featured(6),
      publicActivitiesApi.feed({ limit: 24 }),
    ]);

    return {
      featured: featuredResponse,
      feedItems: homeFeedResponse.items,
    };
  },
  {
    server: previewState.value === 'ready',
    lazy: false,
    default: () => ({
      featured: [],
      feedItems: [],
    }),
  }
);

const featuredActivities = computed(() => initialHomeData.value?.featured ?? []);
const homeFeedActivities = computed(() => initialHomeData.value?.feedItems ?? []);

const seo = usePublicPageSeo({
  h1: computed(() => t('app.public.home.heroTitle')),
  title: computed(() => t('app.public.home.heroTitle')),
  description: computed(() => t('app.public.home.heroDescription')),
});

type HomeCard = {
  title: string;
  description: string;
  to: string;
  imageUrl: string | null;
  imageAlt: string;
  eyebrow?: string;
  price?: string;
  meta?: string;
  badge?: string;
  dataTest: string;
  favoriteKey: string;
  isFavorite: boolean;
};

type HomeGroupedSection = {
  label: string;
  cards: HomeCard[];
};

const mapActivityToCard = (
  activity: Awaited<ReturnType<typeof publicActivitiesApi.feed>>['items'][number],
  options: {
    badge?: string;
    dataTestPrefix: string;
  }
): HomeCard => ({
  title: activity.name,
  description: activity.short_description,
  to: buildPublicActivityPath(activity),
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
  badge: options.badge,
  dataTest: `${options.dataTestPrefix}-${activity.id}`,
  favoriteKey: activity.public_key,
  isFavorite: isFavorite(activity.public_key),
});

const pickGroupedSection = (
  items: Awaited<ReturnType<typeof publicActivitiesApi.feed>>['items'],
  resolveLabel: (
    activity: Awaited<ReturnType<typeof publicActivitiesApi.feed>>['items'][number]
  ) => string,
  dataTestPrefix: string
): HomeGroupedSection | null => {
  const groups = new Map<string, Awaited<ReturnType<typeof publicActivitiesApi.feed>>['items']>();

  for (const item of items) {
    const label = resolveLabel(item).trim();

    if (!label) {
      continue;
    }

    const existing = groups.get(label) ?? [];
    existing.push(item);
    groups.set(label, existing);
  }

  const ordered = [...groups.entries()].sort((left, right) => right[1].length - left[1].length);
  const selected = ordered.find((entry) => entry[1].length >= 2) ?? ordered[0] ?? null;

  if (!selected) {
    return null;
  }

  return {
    label: selected[0],
    cards: selected[1].slice(0, 6).map((item) => mapActivityToCard(item, { dataTestPrefix })),
  };
};

const featuredSectionState = computed<'loading' | 'ready' | 'empty' | 'error'>(() => {
  if (previewState.value === 'loading') {
    return 'loading';
  }

  if (previewState.value === 'error') {
    return 'error';
  }

  if (previewState.value === 'empty') {
    return 'empty';
  }

  if (initialHomePending.value) {
    return 'loading';
  }

  if (initialHomeError.value) {
    return 'error';
  }

  if (!(featuredActivities.value ?? []).length) {
    return 'empty';
  }

  return 'ready';
});

const feedSectionState = computed<'loading' | 'ready' | 'empty' | 'error'>(() => {
  if (previewState.value === 'loading') {
    return 'loading';
  }

  if (previewState.value === 'error') {
    return 'error';
  }

  if (previewState.value === 'empty') {
    return 'empty';
  }

  if (initialHomePending.value) {
    return 'loading';
  }

  if (initialHomeError.value) {
    return 'error';
  }

  if (!(homeFeedActivities.value ?? []).length) {
    return 'empty';
  }

  return 'ready';
});

const featuredCards = computed(() =>
  featuredActivities.value.map((activity) =>
    mapActivityToCard(activity, {
      badge: t('app.public.home.featuredBadge'),
      dataTestPrefix: 'home-featured-activity',
    })
  )
);

const newCards = computed(() =>
  homeFeedActivities.value.slice(0, 8).map((activity) =>
    mapActivityToCard(activity, {
      badge: t('app.public.home.newBadge'),
      dataTestPrefix: 'home-new-activity',
    })
  )
);

const categorySection = computed(() =>
  pickGroupedSection(
    homeFeedActivities.value ?? [],
    (activity) =>
      [activity.primary_category?.parent?.name, activity.primary_category?.name]
        .filter(Boolean)
        .join(' / '),
    'home-category-activity'
  )
);

const citySection = computed(() =>
  pickGroupedSection(
    homeFeedActivities.value ?? [],
    (activity) => activity.location?.city?.name || '',
    'home-city-activity'
  )
);

const organizationSection = computed(() =>
  pickGroupedSection(
    homeFeedActivities.value ?? [],
    (activity) => activity.organization?.name || '',
    'home-organization-activity'
  )
);

const siteUrl = config.public.siteUrl;
const homeSchemaCards = computed(() => {
  const cards = [
    featuredCards.value[0],
    newCards.value[0],
    categorySection.value?.cards[0],
    citySection.value?.cards[0],
    organizationSection.value?.cards[0],
  ].filter((item): item is HomeCard => Boolean(item));

  return cards.length
    ? cards.map((item) => ({ title: item.title, to: item.to }))
    : [{ title: t('app.public.catalog.breadcrumbs.current'), to: '/catalog' }];
});

const schemaNodes = computed(() =>
  buildPublicHomeSchemaNodes({
    siteUrl,
    description: seo.description.value,
    routeCards: homeSchemaCards.value,
  })
);
const pageSchemaNode = computed(() => schemaNodes.value.pageNodes);
const sectionSchemaNode = computed(() => schemaNodes.value.sectionNode);

usePublicSchemaNode('page:home', pageSchemaNode);
usePublicSchemaNode('section:home-routes', sectionSchemaNode);
</script>

<style scoped lang="scss">
.home-page__section {
  margin-top: 1.5rem;
  max-width: 88rem;
  padding-inline: 1.25rem;
}

@media (min-width: 960px) {
  .home-page__section {
    margin-top: 2rem;
    padding-inline: 1.5rem;
  }
}
</style>
