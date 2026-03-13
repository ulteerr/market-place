<template>
  <div>
    <PageHero
      :eyebrow="t('app.public.home.eyebrow')"
      :title="heroTitle"
      :description="heroDescription"
    />

    <PublicSection :title="t('app.public.home.sectionTitle')" data-test="home-public-routes">
      <PublicCardGridSkeleton
        v-if="pageState === 'loading'"
        data-test="home-public-routes-loading"
      />
      <PublicStateMessage
        v-else-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.home.emptyTitle')"
        :description="t('app.public.home.emptyDescription')"
        data-test="home-public-routes-empty"
      />
      <PublicStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.home.errorTitle')"
        :description="t('app.public.home.errorDescription')"
        data-test="home-public-routes-error"
      />
      <PublicCardGrid v-else :items="routeCards" data-test="home-public-routes-grid" />
    </PublicSection>
  </div>
</template>

<script setup lang="ts">
import { usePublicPreviewState } from '~/composables/layout/usePublicPreviewState';
import { usePublicPageSeo } from '~/composables/seo/usePublicPageSeo';
import { buildPublicHomeSchemaNodes } from '~/composables/schema/public-home-schema';
import { usePublicSchemaNode } from '~/composables/schema/usePublicSchemaRegistry';
import PublicCardGridSkeleton from '~/components/public/PublicCardGridSkeleton/PublicCardGridSkeleton.vue';
import PageHero from '~/components/ui/PageHero/PageHero.vue';
import PublicCardGrid from '~/components/public/PublicCardGrid/PublicCardGrid.vue';
import PublicSection from '~/components/public/PublicSection/PublicSection.vue';
import PublicStateMessage from '~/components/public/PublicStateMessage/PublicStateMessage.vue';

const { t } = useI18n();
const pageState = usePublicPreviewState();
const config = useRuntimeConfig();

const seo = usePublicPageSeo({
  h1: computed(() => t('app.public.home.heroTitle')),
  title: computed(() => t('app.public.home.heroTitle')),
  description: computed(() => t('app.public.home.heroDescription')),
});
const heroTitle = seo.h1;
const heroDescription = seo.description;

const routeCards = computed(() => [
  {
    title: t('app.public.home.cards.catalogTitle'),
    description: t('app.public.home.cards.catalogDescription'),
    to: '/catalog',
    dataTest: 'home-route-catalog',
  },
  {
    title: t('app.public.home.cards.contentTitle'),
    description: t('app.public.home.cards.contentDescription'),
    to: '/content',
    dataTest: 'home-route-content',
  },
  {
    title: t('app.public.home.cards.itemTitle'),
    description: t('app.public.home.cards.itemDescription'),
    to: '/catalog/football',
    variant: 'outline' as const,
    dataTest: 'home-route-catalog-item',
  },
]);

const siteUrl = config.public.siteUrl;
const schemaNodes = computed(() =>
  buildPublicHomeSchemaNodes({
    siteUrl,
    description: seo.description.value,
    routeCards: routeCards.value.map((item) => ({
      title: item.title,
      to: item.to,
    })),
  })
);
const pageSchemaNode = computed(() => schemaNodes.value.pageNodes);
const sectionSchemaNode = computed(() => schemaNodes.value.sectionNode);

usePublicSchemaNode('page:home', pageSchemaNode);
usePublicSchemaNode('section:home-routes', sectionSchemaNode);
</script>
