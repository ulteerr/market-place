import { describe, expect, it } from 'vitest';
import { buildPublicActivitySchemaNodes } from '~/composables/schema/public-activity-schema';

describe('buildPublicActivitySchemaNodes', () => {
  it('builds breadcrumb and service schema for public activity pages', () => {
    const result = buildPublicActivitySchemaNodes({
      siteUrl: 'https://example.test',
      activity: {
        id: 'activity-uuid-1',
        organization_id: 'org-1',
        location_id: 'loc-1',
        name: 'ФАЗ Зенит Василеостровский',
        slug: 'faz-zenit',
        short_description: 'Короткое описание секции.',
        description: 'Подробное описание секции.',
        is_featured: true,
        status: 'published',
        min_age: 5,
        max_age: 17,
        price_from: 2500,
        price_to: null,
        currency: 'RUB',
        primary_category: {
          id: 'cat-leaf-football',
          name: 'Футбол',
          slug: 'futbol',
          parent: {
            id: 'cat-root-sport',
            name: 'Спорт',
            slug: 'sport',
          },
        },
        organization: {
          id: 'org-1',
          name: 'Академия Зенит',
        },
        location: {
          id: 'loc-1',
          address: 'Ленинский, 10',
          city: {
            id: 'city-1',
            name: 'Санкт-Петербург',
          },
        },
        cover: {
          id: 'file-cover',
          url: 'https://example.test/storage/cover.jpg',
          original_name: 'cover.jpg',
          mime_type: 'image/jpeg',
          size: 1000,
          collection: 'cover',
        },
        gallery: [
          {
            id: 'file-gallery-1',
            url: 'https://example.test/storage/gallery-1.jpg',
            original_name: 'gallery-1.jpg',
            mime_type: 'image/jpeg',
            size: 1100,
            collection: 'gallery',
          },
        ],
      },
    });

    expect(result.breadcrumbNode['@type']).toBe('BreadcrumbList');
    expect(result.activityNode).toMatchObject({
      '@context': 'https://schema.org',
      '@type': 'Service',
      name: 'ФАЗ Зенит Василеостровский',
      category: 'Спорт / Футбол',
      provider: {
        '@type': 'Organization',
        name: 'Академия Зенит',
      },
      areaServed: {
        '@type': 'City',
        name: 'Санкт-Петербург',
      },
      audience: {
        '@type': 'PeopleAudience',
        suggestedMinAge: 5,
        suggestedMaxAge: 17,
      },
      offers: {
        '@type': 'Offer',
        price: 2500,
        priceCurrency: 'RUB',
      },
    });
    expect(result.activityNode.image).toEqual([
      'https://example.test/storage/cover.jpg',
      'https://example.test/storage/gallery-1.jpg',
    ]);
  });
});
