import { usePublicSchemaRegistry } from '~/composables/schema/usePublicSchemaRegistry';

export const usePublicSchemaJsonLd = () => {
  const { aggregatedNodes, canRenderSchema } = usePublicSchemaRegistry();

  const jsonLd = computed(() => JSON.stringify(aggregatedNodes.value));

  useHead(() => ({
    script: canRenderSchema.value
      ? [
          {
            key: 'public-schema-json-ld',
            type: 'application/ld+json',
            innerHTML: jsonLd.value,
            tagPosition: 'bodyClose',
          },
        ]
      : [],
  }));
};
