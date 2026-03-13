import { expect, test } from '@playwright/test';

test.describe('Global error reporting', () => {
  test('builds and sends a report with selected block and attachment', async ({ page }) => {
    let capturedPayload: Record<string, unknown> | null = null;

    await page.route('**/api/reports/ui-errors', async (route) => {
      capturedPayload = (route.request().postDataJSON() ?? {}) as Record<string, unknown>;

      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            reportId: 'rep-e2e-001',
            status: 'received',
          },
        }),
      });
    });

    await page.goto('/');

    await page.locator('[data-test="error-reporter-start"]').click();
    await page.locator('[data-test="home-public-routes"]').last().click();

    await expect(page.locator('[data-test="error-reporter-selected"]')).toBeVisible();

    await page
      .locator('[data-test="error-reporter-description"]')
      .fill('Блок отображается некорректно на главной странице.');
    await page.locator('[data-test="error-reporter-attachments"]').setInputFiles({
      name: 'report.txt',
      mimeType: 'text/plain',
      buffer: Buffer.from('E2E attachment payload'),
    });

    await page.locator('[data-test="error-reporter-build"]').click();
    await expect(page.locator('[data-test="error-reporter-preview"]')).toBeVisible();

    await page.locator('[data-test="error-reporter-send"]').click();
    await expect(page.locator('[data-test="error-reporter-send-result"]')).toContainText(
      'rep-e2e-001'
    );

    expect(capturedPayload).not.toBeNull();
    expect(capturedPayload?.page).toMatchObject({
      path: '/',
    });
    expect(capturedPayload?.attachments).toEqual([
      expect.objectContaining({
        safeName: 'report.txt',
        type: 'text/plain',
      }),
    ]);
  });
});
