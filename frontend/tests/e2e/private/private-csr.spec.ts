import { expect, test } from '@playwright/test';
import { setupAppAuth } from '../helpers/app-auth';
import { fetchServerHtml } from '../helpers/ssr';

test.describe('Private CSR routes', () => {
  test('serve private routes without SSR page content or schema', async ({ request }) => {
    const accountHtml = await fetchServerHtml(request, '/account');
    const organizationsHtml = await fetchServerHtml(request, '/organizations');

    expect(accountHtml).not.toContain('Личный кабинет');
    expect(accountHtml).not.toMatch(/<h1[^>]*>/i);
    expect(accountHtml).not.toContain('application/ld+json');

    expect(organizationsHtml).not.toContain('Кабинет организаций');
    expect(organizationsHtml).not.toMatch(/<h1[^>]*>/i);
    expect(organizationsHtml).not.toContain('application/ld+json');
  });

  test('applies dark theme from user settings before private UI becomes visible', async ({
    page,
  }) => {
    await setupAppAuth(page, {
      settings: {
        theme: 'dark',
        collapse_menu: true,
        locale: 'ru',
      },
    });

    await page.goto('/account/profile', { waitUntil: 'domcontentloaded' });

    await expect
      .poll(async () =>
        page.evaluate(() => ({
          theme: document.documentElement.getAttribute('data-theme'),
          uiReady: document.documentElement.getAttribute('data-ui-ready'),
          darkClass: document.documentElement.classList.contains('dark'),
        }))
      )
      .toEqual({
        theme: 'dark',
        uiReady: '0',
        darkClass: true,
      });

    await expect(page.locator('[data-test="account-profile-page"]')).toBeVisible();

    await expect
      .poll(async () =>
        page.evaluate(() => ({
          theme: document.documentElement.getAttribute('data-theme'),
          uiReady: document.documentElement.getAttribute('data-ui-ready'),
          darkClass: document.documentElement.classList.contains('dark'),
        }))
      )
      .toEqual({
        theme: 'dark',
        uiReady: '1',
        darkClass: true,
      });

    await expect(page.locator('script[type="application/ld+json"]')).toHaveCount(0);
  });

  test('renders organizations private route as CSR and keeps schema disabled', async ({ page }) => {
    await setupAppAuth(page, {
      permissions: ['org.company.profile.read', 'org.members.read', 'org.children.read'],
      roles: ['participant', 'manager'],
      settings: {
        theme: 'dark',
      },
    });

    await page.goto('/organizations');

    await expect(page.locator('[data-test="organizations-overview-page"]')).toBeVisible();
    await expect(page.locator('script[type="application/ld+json"]')).toHaveCount(0);
    await expect
      .poll(async () => page.evaluate(() => document.documentElement.getAttribute('data-theme')))
      .toBe('dark');
  });
});
