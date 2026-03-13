import {
  buildOrganizationSchema,
  buildWebSiteSchema,
} from '~/composables/schema/public-schema-contract';

export type PublicHomeRouteCard = {
  title: string;
  to: string;
};

export const buildPublicHomeSchemaNodes = (input: {
  siteUrl: string;
  description: string;
  routeCards: PublicHomeRouteCard[];
}) => {
  const { siteUrl, description, routeCards } = input;

  return {
    pageNodes: [
      buildWebSiteSchema({
        siteUrl,
        siteName: 'Marketplace Frontend',
        description,
      }),
      buildOrganizationSchema({
        siteUrl,
        name: 'Marketplace Frontend',
        description:
          'Платформа для каталога направлений, контентных страниц и пользовательских кабинетов.',
      }),
    ],
    sectionNode: {
      '@context': 'https://schema.org',
      '@type': 'ItemList',
      name: 'Базовые public-направления',
      itemListElement: routeCards.map((item, index) => ({
        '@type': 'ListItem',
        position: index + 1,
        name: item.title,
        url: `${siteUrl}${item.to}`,
      })),
    },
  };
};
