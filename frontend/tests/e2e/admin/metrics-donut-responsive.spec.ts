import type { Page } from '@playwright/test';
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

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasOverflow).toBeFalsy();
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

test.describe('Admin monitoring donut responsive', () => {
  test('renders donut card on mobile 390', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.mobile390);
    await setupMonitoringPage(page);

    const donutCard = page.locator('[data-test="metric-donut-card"]');
    await expect(donutCard).toBeVisible();
    await expect(donutCard.locator('[data-test="metric-donut-total"]')).toHaveText('700');
    await expect(donutCard.locator('[data-test="metric-donut-legend-item"]')).toHaveCount(2);

    await assertNoHorizontalOverflow(page);
  });

  test('renders donut card on tablet 768', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.tablet768);
    await setupMonitoringPage(page);

    const donutCard = page.locator('[data-test="metric-donut-card"]');
    await expect(donutCard).toBeVisible();
    await expect(donutCard.locator('[data-test="metric-donut-total"]')).toHaveText('700');
    await expect(donutCard.locator('[data-test="metric-donut-legend-item"]')).toHaveCount(2);

    await assertNoHorizontalOverflow(page);
  });

  test('renders donut card on desktop 1366', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.desktop1366);
    await setupMonitoringPage(page);

    const donutCard = page.locator('[data-test="metric-donut-card"]');
    await expect(donutCard).toBeVisible();
    await expect(donutCard.locator('[data-test="metric-donut-total"]')).toHaveText('700');
    await expect(donutCard.locator('[data-test="metric-donut-legend-item"]')).toHaveCount(2);

    await assertNoHorizontalOverflow(page);
  });
});
