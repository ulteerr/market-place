import { describe, expect, it } from 'vitest';
import { buildPublicHomeSchemaNodes } from '~/composables/schema/public-home-schema';

describe('buildPublicHomeSchemaNodes', () => {
  it('builds home page schema nodes for SEO and reusable route cards', () => {
    const result = buildPublicHomeSchemaNodes({
      siteUrl: 'https://example.test',
      description:
        'Публичная главная страница marketplace с каталогом направлений, контентными страницами и SEO-ready маршрутизацией.',
      routeCards: [
        { title: 'Каталог', to: '/catalog' },
        { title: 'Контент', to: '/content' },
        { title: 'Пример карточки каталога', to: '/catalog/football' },
      ],
    });

    expect(result.pageNodes).toHaveLength(2);
    expect(result.pageNodes[0]).toMatchObject({
      '@type': 'WebSite',
      url: 'https://example.test',
      description:
        'Публичная главная страница marketplace с каталогом направлений, контентными страницами и SEO-ready маршрутизацией.',
    });
    expect(result.pageNodes[1]).toMatchObject({
      '@type': 'Organization',
      url: 'https://example.test',
    });

    expect(result.sectionNode).toMatchObject({
      '@type': 'ItemList',
      name: 'Базовые public-направления',
    });
    expect(result.sectionNode.itemListElement).toEqual([
      {
        '@type': 'ListItem',
        position: 1,
        name: 'Каталог',
        url: 'https://example.test/catalog',
      },
      {
        '@type': 'ListItem',
        position: 2,
        name: 'Контент',
        url: 'https://example.test/content',
      },
      {
        '@type': 'ListItem',
        position: 3,
        name: 'Пример карточки каталога',
        url: 'https://example.test/catalog/football',
      },
    ]);
  });
});
