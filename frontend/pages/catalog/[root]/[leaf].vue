<template>
  <div>
    <PageHero
      :eyebrow="t('app.public.catalog.categoryPage.leafEyebrow')"
      :title="heroTitle"
      :description="heroDescription"
    />

    <PublicSection
      class="catalog-category-page"
      :title="t('app.public.catalog.categoryPage.activitiesTitle')"
      data-test="catalog-leaf-category-page"
    >
      <PublicCardGridSkeleton
        v-if="pageState === 'loading'"
        data-test="catalog-leaf-category-loading"
      />
      <PublicStateMessage
        v-else-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.categoryPage.emptyTitle')"
        :description="t('app.public.catalog.categoryPage.emptyDescription')"
        data-test="catalog-leaf-category-empty"
      />
      <PublicStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.categoryPage.errorTitle')"
        :description="t('app.public.catalog.categoryPage.errorDescription')"
        data-test="catalog-leaf-category-error"
      />
      <template v-else>
        <div class="catalog-category-page__chips" data-test="catalog-leaf-category-siblings">
          <NuxtLink :to="rootPath" class="catalog-category-page__chip">
            {{ rootCategory?.name }}
          </NuxtLink>
          <NuxtLink
            v-for="link in siblingLinks"
            :key="link.to"
            :to="link.to"
            class="catalog-category-page__chip"
            :class="{ 'catalog-category-page__chip--active': link.active }"
          >
            {{ link.label }}
          </NuxtLink>
        </div>

        <PublicCardGrid
          :items="activityCards"
          show-favorite-button
          data-test="catalog-leaf-category-activities-grid"
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
  buildPublicRootCategoryPath,
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
const { isFavorite, toggleFavorite } = useFavorites();
const rootSlug = computed(() => String(route.params.root || ''));
const leafSlug = computed(() => String(route.params.leaf || ''));

const { data, pending, error } = await useAsyncData(
  () => `catalog-leaf-category:${rootSlug.value}:${leafSlug.value}`,
  async () => {
    const tree = await publicCategoriesApi.tree();
    const resolved = findPublicCategoryBySlugs(tree, rootSlug.value, leafSlug.value);

    if (!resolved || !resolved.leaf) {
      return {
        root: null,
        leaf: null,
        siblings: [],
        activities: [],
      };
    }

    const feed = await publicActivitiesApi.feed({
      limit: 12,
      category_id: resolved.leaf.id,
      root_category_id: resolved.root.id,
    });

    return {
      root: resolved.root,
      leaf: resolved.leaf,
      siblings: resolved.root.children ?? [],
      activities: feed.items,
    };
  }
);

const rootCategory = computed(() => data.value?.root ?? null);
const leafCategory = computed(() => data.value?.leaf ?? null);
const siblingCategories = computed(() => data.value?.siblings ?? []);
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

  if (!rootCategory.value || !leafCategory.value || !activities.value.length) {
    return 'empty';
  }

  return 'ready';
});

const rootPath = computed(() =>
  rootCategory.value ? buildPublicRootCategoryPath(rootCategory.value) : '/catalog'
);
const canonicalPath = computed(() =>
  rootCategory.value && leafCategory.value
    ? buildPublicLeafCategoryPath(rootCategory.value, leafCategory.value)
    : `/catalog/${rootSlug.value}/${leafSlug.value}`
);

const seo = usePublicPageSeo({
  h1: computed(
    () => leafCategory.value?.name || t('app.public.catalog.categoryPage.fallbackTitle')
  ),
  title: computed(() => {
    const title = leafCategory.value?.name || t('app.public.catalog.categoryPage.fallbackTitle');
    return t('app.public.catalog.categoryPage.leafSeoTitle', { title });
  }),
  description: computed(() => {
    const title = leafCategory.value?.name || t('app.public.catalog.categoryPage.fallbackTitle');
    const root = rootCategory.value?.name || '';
    return t('app.public.catalog.categoryPage.leafSeoDescription', { title, root });
  }),
  canonicalPath,
});
const heroTitle = seo.h1;
const heroDescription = seo.description;

const siblingLinks = computed(() =>
  siblingCategories.value.map((category) => ({
    label: category.name,
    to: buildPublicLeafCategoryPath(rootCategory.value!, category),
    active: leafCategory.value?.id === category.id,
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
    dataTest: `catalog-leaf-category-activity-${activity.id}`,
    favoriteKey: activity.public_key,
    isFavorite: isFavorite(activity.public_key),
  }))
);

const siteUrl = config.public.siteUrl;
const pageSchemaNode = computed(() =>
  buildBreadcrumbListSchema(siteUrl, [
    { name: t('app.public.catalog.breadcrumbs.home'), path: '/' },
    { name: t('app.public.catalog.breadcrumbs.current'), path: '/catalog' },
    { name: rootCategory.value?.name || rootSlug.value, path: rootPath.value },
    { name: leafCategory.value?.name || leafSlug.value, path: canonicalPath.value },
  ])
);
const sectionSchemaNode = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'ItemList',
  name: leafCategory.value?.name || t('app.public.catalog.categoryPage.activitiesTitle'),
  itemListElement: activityCards.value.map((item, index) => ({
    '@type': 'ListItem',
    position: index + 1,
    name: item.title,
    url: `${siteUrl}${item.to}`,
  })),
}));

usePublicSchemaNode(
  computed(() => `page:catalog-leaf:${rootSlug.value}:${leafSlug.value}`),
  pageSchemaNode
);
usePublicSchemaNode(
  computed(() => `section:catalog-leaf:${rootSlug.value}:${leafSlug.value}`),
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

.catalog-category-page__chip--active {
  border-color: color-mix(in srgb, var(--accent) 50%, var(--border));
  background: color-mix(in srgb, var(--accent) 14%, var(--surface-elevated));
  color: var(--accent);
}
</style>
