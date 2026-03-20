import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';

const leadPermissions = ['admin.activity-leads.read', 'admin.activity-leads.update'];

test.describe('Admin activity leads page', () => {
  test('renders activity leads list and updates status', async ({ page }) => {
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

    await page.goto('/admin/activity-leads');

    await expect(page.getByRole('heading', { level: 2, name: 'Заявки активности' })).toBeVisible();
    await expect(page.locator('.admin-sidebar').getByText('Заявки активности').first()).toHaveCount(
      1
    );
    await expect(page.getByText('Футбольная секция')).toBeVisible();
    await page.locator('[data-test="admin-activity-lead-status-lead-1"] [role="combobox"]').click();
    await page.getByRole('option', { name: 'Связались' }).click();
    await page.locator('[data-test="admin-activity-lead-save-lead-1"]').click();
  });

  test('redirects without read permission', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? [])],
    });

    await page.goto('/admin/activity-leads');

    await expect(page).toHaveURL(/\/admin$/);
  });
});
