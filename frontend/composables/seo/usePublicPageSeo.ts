import { computed, toValue, type MaybeRefOrGetter } from 'vue';
import { usePublicSeo } from '~/composables/seo/usePublicSeo';

type PublicPageSeoInput = {
  h1: MaybeRefOrGetter<string>;
  title?: MaybeRefOrGetter<string>;
  description: MaybeRefOrGetter<string>;
  image?: MaybeRefOrGetter<string | undefined>;
  type?: MaybeRefOrGetter<'website' | 'article' | undefined>;
  canonicalPath?: MaybeRefOrGetter<string | undefined>;
};

export const usePublicPageSeo = (input: PublicPageSeoInput) => {
  const h1 = computed(() => toValue(input.h1));
  const title = computed(() => toValue(input.title) || h1.value);
  const description = computed(() => toValue(input.description));
  const image = computed(() => toValue(input.image));
  const type = computed(() => toValue(input.type));
  const canonicalPath = computed(() => toValue(input.canonicalPath));

  usePublicSeo({
    title,
    description,
    image,
    type,
    canonicalPath,
  });

  return {
    h1,
    title,
    description,
  };
};
