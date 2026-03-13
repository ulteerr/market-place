import { describe, expect, it } from 'vitest';
import { buildUiErrorReportPayload } from '~/composables/error-reporting/report-payload';

describe('buildUiErrorReportPayload', () => {
  it('builds payload with required page, block and context fields', () => {
    const payload = buildUiErrorReportPayload({
      selectedBlock: {
        blockId: 'public-header',
        strategy: 'data-test',
        queryPath: 'header:nth-of-type(1)',
        selectedAt: '2026-03-10T19:00:00.000Z',
      },
      description: '  Не открывается каталог при клике  ',
      attachments: [
        {
          name: 'screen.png',
          safeName: 'screen.png',
          type: 'image/png',
          size: 12345,
        },
      ],
      route: {
        fullPath: '/catalog/football',
        path: '/catalog/football',
        name: 'catalog-slug',
      },
      locale: 'ru',
      now: new Date('2026-03-10T19:01:00.000Z'),
      clientSnapshot: {
        href: 'https://example.test/catalog/football',
        userAgent: 'UnitTestAgent/1.0',
        viewport: {
          width: 1366,
          height: 900,
        },
        theme: 'dark',
      },
    });

    expect(payload.page).toEqual({
      url: 'https://example.test/catalog/football',
      path: '/catalog/football',
      routeName: 'catalog-slug',
    });
    expect(payload.block).toEqual({
      id: 'public-header',
      strategy: 'data-test',
      queryPath: 'header:nth-of-type(1)',
      selectedAt: '2026-03-10T19:00:00.000Z',
    });
    expect(payload.description).toBe('Не открывается каталог при клике');
    expect(payload.attachments).toEqual([
      {
        name: 'screen.png',
        safeName: 'screen.png',
        type: 'image/png',
        size: 12345,
      },
    ]);
    expect(payload.context).toEqual({
      userAgent: 'UnitTestAgent/1.0',
      viewport: {
        width: 1366,
        height: 900,
      },
      theme: 'dark',
      locale: 'ru',
      timestamp: '2026-03-10T19:01:00.000Z',
    });
  });
});
