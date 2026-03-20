import { expect, test } from '@playwright/test';
import { setupAppAuth } from '../helpers/app-auth';

test.describe('Organizations activity leads page', () => {
  test('renders activity leads page and updates status for allowed user', async ({ page }) => {
    await setupAppAuth(page, {
      permissions: [
        'org.company.profile.read',
        'org.activity-leads.read',
        'org.activity-leads.update',
      ],
      roles: ['participant', 'manager'],
    });

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

    await page.goto('/organizations/activity-leads?organization_id=org-1');

    await expect(page.locator('[data-test="organizations-activity-leads-page"]')).toBeVisible();
    await expect(page.locator('[data-test="organizations-activity-leads-inbox"]')).toBeVisible();
    await expect(page.getByText('Футбольная секция')).toBeVisible();
    await page
      .locator('[data-test="organizations-activity-lead-status-lead-1"] [role="combobox"]')
      .click();
    await page.getByRole('option', { name: 'Связались' }).click();
    await page.locator('[data-test="organizations-activity-lead-save-lead-1"]').click();
  });

  test('redirects user without activity leads permission to overview', async ({ page }) => {
    await setupAppAuth(page, {
      permissions: ['org.company.profile.read'],
      roles: ['participant', 'member'],
    });

    await page.goto('/organizations/activity-leads?organization_id=org-1');

    await expect(page).toHaveURL(/\/organizations$/);
  });
});
