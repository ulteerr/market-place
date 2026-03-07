import type { Locator, Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../helpers/admin-auth';
import { E2E_VIEWPORTS } from '../helpers/viewports';

const monitoringPayload = {
  summary: {
    updated_at: '2026-03-07T10:00:00.000Z',
    domains: {
      presence: {
        events_total: 520,
        errors_total: 35,
        duration_total_ms: 4680,
        duration_count: 52,
        events: {
          heartbeat: { ok: 485, error: 35 },
        },
        last_event_at: '2026-03-07T10:00:00.000Z',
      },
      auth: {
        events_total: 180,
        errors_total: 12,
        duration_total_ms: 1800,
        duration_count: 18,
        events: {
          login: { ok: 168, error: 12 },
        },
        last_event_at: '2026-03-07T09:59:00.000Z',
      },
    },
  },
  incidents: [],
  alerts: [],
};

const setupMonitoringPage = async (page: Page) => {
  await setupAdminAuth(page, {
    permissions: ['admin.panel.access', 'admin.monitoring.read'],
  });

  await page.route('**/api/admin/observability**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: monitoringPayload,
      }),
    });
  });

  await page.goto('/admin/monitoring');
};

const yPositions = async (cards: Locator): Promise<number[]> => {
  const boxes = await cards.evaluateAll((elements) =>
    elements.map((element) => element.getBoundingClientRect().y)
  );

  return boxes.map((value) => Math.round(value));
};

test.describe('Admin monitoring KPI responsive', () => {
  test('mobile 390: cards are stacked in one column', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.mobile390);
    await setupMonitoringPage(page);

    const grid = page.locator('[data-test="metric-kpi-grid"]');
    const cards = grid.locator('[data-test="metric-kpi-card"]');

    await expect(grid).toBeVisible();
    await expect(cards).toHaveCount(3);

    const [y1, y2, y3] = await yPositions(cards);
    expect(y1).toBeLessThan(y2);
    expect(y2).toBeLessThan(y3);
  });

  test('tablet 768: first two cards in first row, third on second row', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.tablet768);
    await setupMonitoringPage(page);

    const cards = page.locator('[data-test="metric-kpi-grid"] [data-test="metric-kpi-card"]');
    await expect(cards).toHaveCount(3);

    const [y1, y2, y3] = await yPositions(cards);
    expect(Math.abs(y1 - y2)).toBeLessThan(6);
    expect(y3).toBeGreaterThan(y1 + 12);
  });

  test('desktop 1366: all three cards in one row', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.desktop1366);
    await setupMonitoringPage(page);

    const cards = page.locator('[data-test="metric-kpi-grid"] [data-test="metric-kpi-card"]');
    await expect(cards).toHaveCount(3);

    const [y1, y2, y3] = await yPositions(cards);
    expect(Math.abs(y1 - y2)).toBeLessThan(6);
    expect(Math.abs(y2 - y3)).toBeLessThan(6);
  });
});
