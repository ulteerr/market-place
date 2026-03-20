import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';
import { E2E_RESPONSIVE_VIEWPORTS } from '../../helpers/viewports';

const leadPermissions = ['admin.activity-leads.read', 'admin.activity-leads.update'];

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );

  expect(hasOverflow).toBeFalsy();
};

const setupAdminActivityLeadsApi = async (page: Page) => {
  await page.route('**/api/admin/activity-leads**', async (route) => {
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
          per_page: 10,
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

test.describe('Admin activity leads responsive page', () => {
  for (const viewport of E2E_RESPONSIVE_VIEWPORTS) {
    test(`activity leads @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page, {
        permissions: [...(defaultAdminUser.permissions ?? []), ...leadPermissions],
        settings: {
          theme: 'dark',
          collapse_menu: false,
          admin_crud_preferences: {},
          admin_navigation_sections: {
            organization: { open: true },
          },
        },
      });
      await setupAdminActivityLeadsApi(page);

      await page.goto('/admin/activity-leads');

      await expect(
        page.getByRole('heading', { level: 2, name: 'Заявки активности' })
      ).toBeVisible();
      await expect(page.getByText('Футбольная секция')).toBeVisible();

      if (viewport.width < 768) {
        await expect(
          page.locator('[data-test="admin-activity-lead-save-lead-1"]').first()
        ).toBeVisible();
      } else {
        await expect(page.locator('.admin-table')).toBeVisible();
      }

      await assertNoHorizontalOverflow(page);
    });
  }
});
