<template>
  <div>
    <PageHero
      :eyebrow="t('app.public.content.eyebrow')"
      :title="heroTitle"
      :description="heroDescription"
    />

    <PublicSection data-test="content-item-section">
      <UiCardSkeleton v-if="pageState === 'loading'" data-test="content-item-summary-loading" />
      <PublicStateMessage
        v-else-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.content.item.emptyTitle')"
        :description="t('app.public.content.item.emptyDescription')"
        data-test="content-item-summary-empty"
      />
      <PublicStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.content.item.errorTitle')"
        :description="t('app.public.content.item.errorDescription')"
        data-test="content-item-summary-error"
      />
      <UiCard v-else variant="default" padding="lg" data-test="content-item-summary">
        <h2 class="item-page__title">{{ t('app.public.content.item.summaryTitle', { slug }) }}</h2>
        <p class="item-page__text">
          {{ t('app.public.content.item.summaryText', { slug }) }}
        </p>
      </UiCard>

      <PublicActionLinks
        v-if="pageState !== 'loading'"
        :links="links"
        data-test="content-item-actions"
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
const slug = computed(() => String(route.params.slug || 'page'));
const title = computed(() =>
  slug.value.replace(/[-_]/g, ' ').replace(/\b\w/g, (s) => s.toUpperCase())
);
const links = computed(() => [
  { label: t('app.public.content.item.allPages'), to: '/content' },
  { label: t('app.public.content.item.openCatalog'), to: '/catalog' },
]);

const seo = usePublicPageSeo({
  h1: title,
  title,
  description: computed(() => t('app.public.content.item.seoDescription', { title: title.value })),
  type: 'article',
});
const heroTitle = seo.h1;
const heroDescription = seo.description;

const pageSchemaNode = computed(() =>
  buildBreadcrumbListSchema(config.public.siteUrl, [
    { name: t('app.public.content.breadcrumbs.home'), path: '/' },
    { name: t('app.public.content.breadcrumbs.current'), path: '/content' },
    { name: title.value, path: route.path },
  ])
);

usePublicSchemaNode(
  computed(() => `page:content:${slug.value}`),
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
