import { expect, test } from '@playwright/test';
import {
  extractH1Text,
  extractJsonLdPayloads,
  extractMetaDescription,
  extractTitle,
  fetchServerHtml,
} from '../helpers/ssr';

const cases = [
  {
    path: '/',
    expectedH1: 'Marketplace: публичный контур',
    expectedTitle: 'Главная | Marketplace',
    expectedDescription:
      'Публичная главная страница marketplace с каталогом направлений, контентными страницами и SEO-ready маршрутизацией.',
    expectedJsonLdTypes: ['WebSite', 'Organization'],
  },
  {
    path: '/catalog',
    expectedH1: 'Каталог направлений',
    expectedTitle: 'Каталог направлений | Marketplace',
    expectedDescription:
      'Публичный каталог направлений с быстрыми переходами в категории и карточки контента.',
    expectedJsonLdTypes: ['BreadcrumbList'],
  },
  {
    path: '/content',
    expectedH1: 'Контентные страницы',
    expectedTitle: 'Контентные страницы | Marketplace',
    expectedDescription:
      'Публичный контентный раздел с редакционными страницами, лендингами и SEO-ready маршрутизацией.',
    expectedJsonLdTypes: ['BreadcrumbList'],
  },
];

test.describe('Public SSR SEO smoke', () => {
  for (const entry of cases) {
    test(`renders SSR HTML with SEO contract for ${entry.path}`, async ({ request }) => {
      const html = await fetchServerHtml(request, entry.path);

      expect(extractTitle(html)).toBe(entry.expectedTitle);
      expect(extractMetaDescription(html)).toBe(entry.expectedDescription);
      expect(extractH1Text(html)).toBe(entry.expectedH1);

      const jsonLdPayloads = extractJsonLdPayloads(html);
      expect(jsonLdPayloads.length).toBeGreaterThan(0);

      const normalizedPayload = jsonLdPayloads.join('\n');
      for (const schemaType of entry.expectedJsonLdTypes) {
        expect(normalizedPayload).toContain(`"@type":"${schemaType}"`);
      }
    });
  }
});
