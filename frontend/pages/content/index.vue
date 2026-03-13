<template>
  <div>
    <PageHero
      :eyebrow="t('app.public.content.eyebrow')"
      :title="heroTitle"
      :description="heroDescription"
    />

    <PublicSection :title="t('app.public.content.sectionTitle')" data-test="content-pages">
      <PublicCardGridSkeleton v-if="pageState === 'loading'" data-test="content-pages-loading" />
      <PublicStateMessage
        v-else-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.content.emptyTitle')"
        :description="t('app.public.content.emptyDescription')"
        data-test="content-pages-empty"
      />
      <PublicStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.content.errorTitle')"
        :description="t('app.public.content.errorDescription')"
        data-test="content-pages-error"
      />
      <PublicCardGrid v-else :items="pages" variant="outline" data-test="content-pages-grid" />
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
  h1: computed(() => t('app.public.content.heroTitle')),
  title: computed(() => t('app.public.content.seoTitle')),
  description: computed(() => t('app.public.content.heroDescription')),
});
const heroTitle = seo.h1;
const heroDescription = seo.description;

const pages = computed(() => [
  {
    to: '/content/for-parents',
    title: t('app.public.content.pages.parentsTitle'),
    description: t('app.public.content.pages.parentsDescription'),
    dataTest: 'content-page-for-parents',
  },
  {
    to: '/content/for-organizations',
    title: t('app.public.content.pages.organizationsTitle'),
    description: t('app.public.content.pages.organizationsDescription'),
    dataTest: 'content-page-for-organizations',
  },
  {
    to: '/content/faq',
    title: t('app.public.content.pages.faqTitle'),
    description: t('app.public.content.pages.faqDescription'),
    dataTest: 'content-page-faq',
  },
]);

const siteUrl = config.public.siteUrl;
const pageSchemaNode = computed(() =>
  buildBreadcrumbListSchema(siteUrl, [
    { name: t('app.public.content.breadcrumbs.home'), path: '/' },
    { name: t('app.public.content.breadcrumbs.current'), path: '/content' },
  ])
);
const sectionSchemaNode = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'ItemList',
  name: t('app.public.content.sectionTitle'),
  itemListElement: pages.value.map((item, index) => ({
    '@type': 'ListItem',
    position: index + 1,
    name: item.title,
    url: `${siteUrl}${item.to}`,
  })),
}));

usePublicSchemaNode('page:content-index', pageSchemaNode);
usePublicSchemaNode('section:content-pages', sectionSchemaNode);

useHead({
  title: computed(() => `${t('app.public.content.seoTitle')} | Marketplace`),
});
</script>
