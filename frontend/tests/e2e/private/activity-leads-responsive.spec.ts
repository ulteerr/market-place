import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAppAuth } from '../helpers/app-auth';
import { E2E_RESPONSIVE_VIEWPORTS } from '../helpers/viewports';

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );

  expect(hasOverflow).toBeFalsy();
};

const setupOrganizationActivityLeadsApi = async (page: Page) => {
  await page.route('**/api/organizations/org-1/activity-leads**', async (route) => {
    if (route.request().method() === 'PATCH') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            id: 'lead-1',
            activity_id: 'act-1',
            user_id: 'user-1',
            child_id: null,
            request_for_type: 'self',
            status: 'contacted',
            contact_channels: ['phone'],
            contact_payload: { phone: '+79990000000' },
            message: 'Перезвоните после 18:00',
            created_at: '2026-03-15T12:00:00Z',
            updated_at: '2026-03-15T13:00:00Z',
            activity: {
              id: 'act-1',
              name: 'Футбольная секция',
              slug: 'futbol',
              organization: { id: 'org-1', name: 'Организация спорта' },
            },
            subject: {
              type: 'self',
              id: 'user-1',
              first_name: 'Иван',
              last_name: 'Иванов',
              label: 'Иванов Иван',
              email: 'ivan@example.com',
            },
          },
        }),
      });
      return;
    }

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          current_page: 1,
          last_page: 1,
          per_page: 20,
          total: 1,
          data: [
            {
              id: 'lead-1',
              activity_id: 'act-1',
              user_id: 'user-1',
              child_id: null,
              request_for_type: 'self',
              status: 'new',
              contact_channels: ['phone'],
              contact_payload: { phone: '+79990000000' },
              message: 'Перезвоните после 18:00',
              created_at: '2026-03-15T12:00:00Z',
              updated_at: '2026-03-15T12:00:00Z',
              activity: {
                id: 'act-1',
                name: 'Футбольная секция',
                slug: 'futbol',
                organization: { id: 'org-1', name: 'Организация спорта' },
              },
              subject: {
                type: 'self',
                id: 'user-1',
                first_name: 'Иван',
                last_name: 'Иванов',
                label: 'Иванов Иван',
                email: 'ivan@example.com',
              },
            },
          ],
        },
      }),
    });
  });
};

test.describe('Organizations activity leads responsive page', () => {
  for (const viewport of E2E_RESPONSIVE_VIEWPORTS) {
    test(`activity leads @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAppAuth(page, {
        permissions: [
          'org.company.profile.read',
          'org.activity-leads.read',
          'org.activity-leads.update',
        ],
        roles: ['participant', 'manager'],
      });
      await setupOrganizationActivityLeadsApi(page);

      await page.goto('/organizations/activity-leads?organization_id=org-1');

      await expect(page.locator('[data-test="organizations-activity-leads-page"]')).toBeVisible();
      await expect(page.locator('[data-test="organizations-activity-leads-inbox"]')).toBeVisible();
      await expect(page.getByText('Футбольная секция')).toBeVisible();
      await expect(
        page.locator('[data-test="organizations-activity-lead-save-lead-1"]')
      ).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });
  }
});
