import { computed, toValue, type MaybeRefOrGetter } from 'vue';

type PublicSeoInput = {
  title: MaybeRefOrGetter<string>;
  description: MaybeRefOrGetter<string>;
  image?: MaybeRefOrGetter<string | undefined>;
  type?: MaybeRefOrGetter<'website' | 'article' | undefined>;
  canonicalPath?: MaybeRefOrGetter<string | undefined>;
};

export const usePublicSeo = (input: PublicSeoInput) => {
  const route = useRoute();
  const runtimeConfig = useRuntimeConfig();
  const requestUrl = useRequestURL();

  const siteName = 'Marketplace';
  const siteUrl = runtimeConfig.public.siteUrl || requestUrl.origin || 'http://localhost:3000';

  const canonical = computed(() => {
    const path = toValue(input.canonicalPath) ?? route.path;

    return new URL(path, siteUrl).toString();
  });

  const title = computed(() => toValue(input.title));
  const description = computed(() => toValue(input.description));
  const fullTitle = computed(() => `${title.value} | ${siteName}`);
  const ogImage = computed(() => toValue(input.image) || `${siteUrl}/og-default.jpg`);
  const ogType = computed(() => toValue(input.type) ?? 'website');

  useHead(() => ({
    title: fullTitle.value,
    meta: [
      {
        name: 'description',
        content: description.value,
      },
      {
        property: 'og:title',
        content: fullTitle.value,
      },
      {
        property: 'og:description',
        content: description.value,
      },
      {
        property: 'og:type',
        content: ogType.value,
      },
      {
        property: 'og:site_name',
        content: siteName,
      },
      {
        property: 'og:url',
        content: canonical.value,
      },
      {
        property: 'og:image',
        content: ogImage.value,
      },
      {
        name: 'twitter:card',
        content: 'summary_large_image',
      },
      {
        name: 'twitter:title',
        content: fullTitle.value,
      },
      {
        name: 'twitter:description',
        content: description.value,
      },
      {
        name: 'twitter:image',
        content: ogImage.value,
      },
    ],
    link: [
      {
        rel: 'canonical',
        href: canonical.value,
      },
    ],
  }));
};
