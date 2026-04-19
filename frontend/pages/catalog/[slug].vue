<template>
  <div>
    <PageHero
      :eyebrow="t('app.public.catalog.categoryPage.rootEyebrow')"
      :title="heroTitle"
      :description="heroDescription"
    />

    <PublicSection
      class="catalog-category-page"
      :title="t('app.public.catalog.categoryPage.activitiesTitle')"
      data-test="catalog-root-category-page"
    >
      <PublicCardGridSkeleton
        v-if="pageState === 'loading'"
        data-test="catalog-root-category-loading"
      />
      <PublicStateMessage
        v-else-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.categoryPage.emptyTitle')"
        :description="t('app.public.catalog.categoryPage.emptyDescription')"
        data-test="catalog-root-category-empty"
      />
      <PublicStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.categoryPage.errorTitle')"
        :description="t('app.public.catalog.categoryPage.errorDescription')"
        data-test="catalog-root-category-error"
      />
      <template v-else>
        <div
          v-if="childLinks.length"
          class="catalog-category-page__chips"
          data-test="catalog-root-category-children"
        >
          <NuxtLink
            v-for="link in childLinks"
            :key="link.to"
            :to="link.to"
            class="catalog-category-page__chip"
          >
            {{ link.label }}
          </NuxtLink>
        </div>

        <PublicCardGrid
          :items="activityCards"
          show-favorite-button
          data-test="catalog-root-category-activities-grid"
          @toggle-favorite="toggleFavorite"
        />
      </template>
    </PublicSection>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePublicPreviewState } from '~/composables/layout/usePublicPreviewState';
import {
  buildPublicLeafCategoryPath,
  findPublicCategoryBySlugs,
  usePublicCategories,
} from '~/composables/usePublicCategories';
import { buildPublicActivityPath, usePublicActivities } from '~/composables/usePublicActivities';
import { useFavorites } from '~/composables/useFavorites';
import { usePublicPageSeo } from '~/composables/seo/usePublicPageSeo';
import { buildBreadcrumbListSchema } from '~/composables/schema/public-schema-contract';
import { usePublicSchemaNode } from '~/composables/schema/usePublicSchemaRegistry';
import PublicCardGrid from '~/components/public/PublicCardGrid/PublicCardGrid.vue';
import PublicCardGridSkeleton from '~/components/public/PublicCardGridSkeleton/PublicCardGridSkeleton.vue';
import PublicSection from '~/components/public/PublicSection/PublicSection.vue';
import PublicStateMessage from '~/components/public/PublicStateMessage/PublicStateMessage.vue';
import PageHero from '~/components/ui/PageHero/PageHero.vue';

definePageMeta({
  layout: 'default',
});

const { t } = useI18n();
const route = useRoute();
const config = useRuntimeConfig();
const previewState = usePublicPreviewState();
const publicCategoriesApi = usePublicCategories();
const publicActivitiesApi = usePublicActivities();
const { selectedCityId } = usePublicCity();
const { isFavorite, toggleFavorite } = useFavorites();
const rootSlug = computed(() => String(route.params.slug || ''));

const { data, pending, error } = await useAsyncData(
  () => `catalog-root-category:${rootSlug.value}:${selectedCityId.value || 'all'}`,
  async () => {
    const tree = await publicCategoriesApi.tree();
    const resolved = findPublicCategoryBySlugs(tree, rootSlug.value);

    if (!resolved) {
      return {
        category: null,
        children: [],
        activities: [],
      };
    }

    const feed = await publicActivitiesApi.feed({
      limit: 12,
      root_category_id: resolved.root.id,
    });

    return {
      category: resolved.root,
      children: resolved.root.children ?? [],
      activities: feed.items,
    };
  },
  {
    watch: [selectedCityId],
  }
);

const category = computed(() => data.value?.category ?? null);
const childCategories = computed(() => data.value?.children ?? []);
const activities = computed(() => data.value?.activities ?? []);

const pageState = computed<'loading' | 'ready' | 'empty' | 'error'>(() => {
  if (previewState.value === 'loading') {
    return 'loading';
  }

  if (previewState.value === 'error') {
    return 'error';
  }

  if (previewState.value === 'empty') {
    return 'empty';
  }

  if (pending.value) {
    return 'loading';
  }

  if (error.value) {
    return 'error';
  }

  if (!category.value || !activities.value.length) {
    return 'empty';
  }

  return 'ready';
});

const canonicalPath = computed(() => `/catalog/${rootSlug.value}`);

const seo = usePublicPageSeo({
  h1: computed(() => category.value?.name || t('app.public.catalog.categoryPage.fallbackTitle')),
  title: computed(() => {
    const title = category.value?.name || t('app.public.catalog.categoryPage.fallbackTitle');
    return t('app.public.catalog.categoryPage.rootSeoTitle', { title });
  }),
  description: computed(() => {
    const title = category.value?.name || t('app.public.catalog.categoryPage.fallbackTitle');
    return t('app.public.catalog.categoryPage.rootSeoDescription', { title });
  }),
  canonicalPath,
});
const heroTitle = seo.h1;
const heroDescription = seo.description;

const childLinks = computed(() =>
  childCategories.value.map((child) => ({
    label: child.name,
    to: buildPublicLeafCategoryPath(category.value!, child),
  }))
);

const activityCards = computed(() =>
  activities.value.map((activity) => ({
    to: buildPublicActivityPath(activity),
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
    dataTest: `catalog-root-category-activity-${activity.id}`,
    favoriteKey: activity.public_key,
    isFavorite: isFavorite(activity.public_key),
  }))
);

const siteUrl = config.public.siteUrl;
const pageSchemaNode = computed(() =>
  buildBreadcrumbListSchema(siteUrl, [
    { name: t('app.public.catalog.breadcrumbs.home'), path: '/' },
    { name: t('app.public.catalog.breadcrumbs.current'), path: '/catalog' },
    { name: category.value?.name || rootSlug.value, path: canonicalPath.value },
  ])
);
const sectionSchemaNode = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'ItemList',
  name: category.value?.name || t('app.public.catalog.categoryPage.activitiesTitle'),
  itemListElement: activityCards.value.map((item, index) => ({
    '@type': 'ListItem',
    position: index + 1,
    name: item.title,
    url: `${siteUrl}${item.to}`,
  })),
}));

usePublicSchemaNode(
  computed(() => `page:catalog-root:${rootSlug.value}`),
  pageSchemaNode
);
usePublicSchemaNode(
  computed(() => `section:catalog-root:${rootSlug.value}`),
  sectionSchemaNode
);
</script>

<style scoped lang="scss">
.catalog-category-page {
  max-width: 88rem;
  padding-inline: 1.25rem;
}

.catalog-category-page__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.catalog-category-page__chip {
  border: 1px solid color-mix(in srgb, var(--border) 88%, transparent);
  border-radius: 999px;
  background: var(--surface-elevated);
  color: var(--text);
  padding: 0.65rem 1rem;
  font-weight: 600;
  text-decoration: none;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    background-color 0.2s ease;
}

.catalog-category-page__chip:hover,
.catalog-category-page__chip:focus-visible {
  outline: none;
  border-color: color-mix(in srgb, var(--accent) 35%, var(--border));
  color: var(--accent);
  background: color-mix(in srgb, var(--accent) 12%, var(--surface-elevated));
}
</style>
