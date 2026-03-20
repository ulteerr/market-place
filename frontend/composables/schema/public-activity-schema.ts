import type { PublicActivity } from '~/composables/usePublicActivities';
import { buildPublicActivityPath } from '~/composables/usePublicActivities';
import { buildBreadcrumbListSchema } from '~/composables/schema/public-schema-contract';
import type { PublicSchemaNode } from '~/composables/schema/usePublicSchemaRegistry';

const buildAbsoluteUrl = (siteUrl: string, path: string): string => `${siteUrl}${path}`;

export const buildPublicActivitySchemaNodes = (input: {
  siteUrl: string;
  activity: PublicActivity;
}): {
  breadcrumbNode: PublicSchemaNode;
  activityNode: PublicSchemaNode;
} => {
  const canonicalPath = buildPublicActivityPath({
    public_key: `${input.activity.slug}-${input.activity.id}`,
    id: input.activity.id,
    slug: input.activity.slug,
    primary_category: input.activity.primary_category,
  });

  const categoryName = [
    input.activity.primary_category?.parent?.name,
    input.activity.primary_category?.name,
  ]
    .filter(Boolean)
    .join(' / ');

  const cityName = input.activity.location?.city?.name ?? undefined;
  const imageUrls = [
    input.activity.cover?.url,
    ...(input.activity.gallery || []).map((item) => item.url),
  ].filter((value): value is string => Boolean(value));

  return {
    breadcrumbNode: buildBreadcrumbListSchema(input.siteUrl, [
      { name: 'Главная', path: '/' },
      { name: 'Каталог', path: '/catalog' },
      { name: input.activity.name, path: canonicalPath },
    ]),
    activityNode: {
      '@context': 'https://schema.org',
      '@type': 'Service',
      name: input.activity.name,
      description: input.activity.description || input.activity.short_description,
      url: buildAbsoluteUrl(input.siteUrl, canonicalPath),
      image: imageUrls.length ? imageUrls : undefined,
      category: categoryName || undefined,
      provider: input.activity.organization
        ? {
            '@type': 'Organization',
            name: input.activity.organization.name,
          }
        : undefined,
      areaServed: cityName
        ? {
            '@type': 'City',
            name: cityName,
          }
        : undefined,
      audience:
        input.activity.min_age != null || input.activity.max_age != null
          ? {
              '@type': 'PeopleAudience',
              suggestedMinAge: input.activity.min_age ?? undefined,
              suggestedMaxAge: input.activity.max_age ?? undefined,
            }
          : undefined,
      offers:
        input.activity.price_from != null
          ? {
              '@type': 'Offer',
              price: input.activity.price_from,
              priceCurrency: input.activity.currency || 'RUB',
              availability: 'https://schema.org/InStock',
            }
          : undefined,
    },
  };
};
