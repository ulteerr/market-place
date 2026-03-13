import type { PublicSchemaNode } from '~/composables/schema/usePublicSchemaRegistry';

type BreadcrumbItem = {
  name: string;
  path: string;
};

export const buildWebSiteSchema = (input: {
  siteUrl: string;
  siteName: string;
  description: string;
}): PublicSchemaNode => ({
  '@context': 'https://schema.org',
  '@type': 'WebSite',
  name: input.siteName,
  description: input.description,
  url: input.siteUrl,
});

export const buildOrganizationSchema = (input: {
  siteUrl: string;
  name: string;
  description: string;
}): PublicSchemaNode => ({
  '@context': 'https://schema.org',
  '@type': 'Organization',
  name: input.name,
  description: input.description,
  url: input.siteUrl,
});

export const buildBreadcrumbListSchema = (
  siteUrl: string,
  items: BreadcrumbItem[]
): PublicSchemaNode => ({
  '@context': 'https://schema.org',
  '@type': 'BreadcrumbList',
  itemListElement: items.map((item, index) => ({
    '@type': 'ListItem',
    position: index + 1,
    name: item.name,
    item: `${siteUrl}${item.path}`,
  })),
});
