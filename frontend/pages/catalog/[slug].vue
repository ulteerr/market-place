<template>
  <div>
    <PageHero
      :eyebrow="t('app.public.catalog.eyebrow')"
      :title="heroTitle"
      :description="heroDescription"
    />

    <PublicSection data-test="catalog-item-section">
      <UiCardSkeleton v-if="pageState === 'loading'" data-test="catalog-item-summary-loading" />
      <PublicStateMessage
        v-else-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.item.emptyTitle')"
        :description="t('app.public.catalog.item.emptyDescription')"
        data-test="catalog-item-summary-empty"
      />
      <PublicStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.item.errorTitle')"
        :description="t('app.public.catalog.item.errorDescription')"
        data-test="catalog-item-summary-error"
      />
      <UiCard v-else variant="default" padding="lg" data-test="catalog-item-summary">
        <h2 class="item-page__title">{{ t('app.public.catalog.item.summaryTitle') }}</h2>
        <p class="item-page__text">
          {{ t('app.public.catalog.item.summaryText', { slug }) }}
        </p>
      </UiCard>

      <PublicActionLinks
        v-if="pageState !== 'loading'"
        :links="links"
        data-test="catalog-item-actions"
      />
    </PublicSection>
  </div>
</template>

<script setup lang="ts">
import { usePublicPreviewState } from '~/composables/layout/usePublicPreviewState';
import { usePublicPageSeo } from '~/composables/seo/usePublicPageSeo';
import { buildBreadcrumbListSchema } from '~/composables/schema/public-schema-contract';
import { usePublicSchemaNode } from '~/composables/schema/usePublicSchemaRegistry';
import PublicActionLinks from '~/components/public/PublicActionLinks/PublicActionLinks.vue';
import PublicSection from '~/components/public/PublicSection/PublicSection.vue';
import PublicStateMessage from '~/components/public/PublicStateMessage/PublicStateMessage.vue';
import PageHero from '~/components/ui/PageHero/PageHero.vue';
import UiCard from '~/components/ui/Card/UiCard.vue';
import UiCardSkeleton from '~/components/ui/Skeleton/UiCardSkeleton.vue';

definePageMeta({
  layout: 'default',
});

const { t } = useI18n();
const pageState = usePublicPreviewState();
const route = useRoute();
const config = useRuntimeConfig();
const slug = computed(() => String(route.params.slug || 'item'));
const title = computed(() =>
  slug.value.replace(/[-_]/g, ' ').replace(/\b\w/g, (s) => s.toUpperCase())
);
const links = computed(() => [
  { label: t('app.public.catalog.item.backToCatalog'), to: '/catalog' },
  { label: t('app.public.catalog.item.openContent'), to: '/content' },
]);

const seo = usePublicPageSeo({
  h1: computed(() => t('app.public.catalog.item.seoH1', { title: title.value })),
  title: computed(() => t('app.public.catalog.item.seoTitle', { title: title.value })),
  description: computed(() => t('app.public.catalog.item.seoDescription', { title: title.value })),
});
const heroTitle = seo.h1;
const heroDescription = seo.description;

const pageSchemaNode = computed(() =>
  buildBreadcrumbListSchema(config.public.siteUrl, [
    { name: t('app.public.catalog.breadcrumbs.home'), path: '/' },
    { name: t('app.public.catalog.breadcrumbs.current'), path: '/catalog' },
    { name: title.value, path: route.path },
  ])
);

usePublicSchemaNode(
  computed(() => `page:catalog:${slug.value}`),
  pageSchemaNode
);
</script>

<style scoped lang="scss">
.item-page__title {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 700;
}

.item-page__text {
  margin: 0.8rem 0 0;
  color: var(--muted);
}
</style>
