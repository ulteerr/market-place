<template>
  <div>
    <PageHero
      :eyebrow="t('app.public.catalog.eyebrow')"
      :title="heroTitle"
      :description="heroDescription"
    />

    <PublicSection
      :title="t('app.public.catalog.sectionTitle')"
      data-test="catalog-popular-categories"
    >
      <PublicCardGridSkeleton
        v-if="pageState === 'loading'"
        data-test="catalog-popular-categories-loading"
      />
      <PublicStateMessage
        v-else-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.emptyTitle')"
        :description="t('app.public.catalog.emptyDescription')"
        data-test="catalog-popular-categories-empty"
      />
      <PublicStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.errorTitle')"
        :description="t('app.public.catalog.errorDescription')"
        data-test="catalog-popular-categories-error"
      />
      <PublicCardGrid v-else :items="categories" data-test="catalog-popular-categories-grid" />
    </PublicSection>
  </div>
</template>

<script setup lang="ts">
import { usePublicPreviewState } from '~/composables/layout/usePublicPreviewState';
import { usePublicPageSeo } from '~/composables/seo/usePublicPageSeo';
import { buildBreadcrumbListSchema } from '~/composables/schema/public-schema-contract';
import { usePublicSchemaNode } from '~/composables/schema/usePublicSchemaRegistry';
import PublicCardGridSkeleton from '~/components/public/PublicCardGridSkeleton/PublicCardGridSkeleton.vue';
import PublicCardGrid from '~/components/public/PublicCardGrid/PublicCardGrid.vue';
import PublicSection from '~/components/public/PublicSection/PublicSection.vue';
import PublicStateMessage from '~/components/public/PublicStateMessage/PublicStateMessage.vue';
import PageHero from '~/components/ui/PageHero/PageHero.vue';

definePageMeta({
  layout: 'default',
});

const { t } = useI18n();
const pageState = usePublicPreviewState();
const config = useRuntimeConfig();

const seo = usePublicPageSeo({
  h1: computed(() => t('app.public.catalog.heroTitle')),
  title: computed(() => t('app.public.catalog.seoTitle')),
  description: computed(() => t('app.public.catalog.heroDescription')),
});
const heroTitle = seo.h1;
const heroDescription = seo.description;

const categories = computed(() => [
  {
    to: '/catalog/football',
    title: t('app.public.catalog.categories.footballTitle'),
    description: t('app.public.catalog.categories.footballDescription'),
    dataTest: 'catalog-category-football',
  },
  {
    to: '/catalog/volleyball',
    title: t('app.public.catalog.categories.volleyballTitle'),
    description: t('app.public.catalog.categories.volleyballDescription'),
    dataTest: 'catalog-category-volleyball',
  },
  {
    to: '/catalog/drawing',
    title: t('app.public.catalog.categories.drawingTitle'),
    description: t('app.public.catalog.categories.drawingDescription'),
    dataTest: 'catalog-category-drawing',
  },
]);

const siteUrl = config.public.siteUrl;
const pageSchemaNode = computed(() =>
  buildBreadcrumbListSchema(siteUrl, [
    { name: t('app.public.catalog.breadcrumbs.home'), path: '/' },
    { name: t('app.public.catalog.breadcrumbs.current'), path: '/catalog' },
  ])
);
const sectionSchemaNode = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'ItemList',
  name: t('app.public.catalog.sectionTitle'),
  itemListElement: categories.value.map((item, index) => ({
    '@type': 'ListItem',
    position: index + 1,
    name: item.title,
    url: `${siteUrl}${item.to}`,
  })),
}));

usePublicSchemaNode('page:catalog-index', pageSchemaNode);
usePublicSchemaNode('section:catalog-categories', sectionSchemaNode);

useHead({
  title: computed(() => `${t('app.public.catalog.seoTitle')} | Marketplace`),
});
</script>
