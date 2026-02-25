import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupRoleEditApi } from '../../helpers/crud/roles';

const existingRole = {
  id: 'r-2',
  code: 'manager',
  label: 'Менеджер',
  is_system: false,
};

test.describe('Admin roles form pages', () => {
  test('shows create form on /admin/roles/new', async ({ page }) => {
    await setupAdminAuth(page);

    await page.goto('/admin/roles/new');
    await expect(page.getByRole('heading', { level: 2, name: 'Новая роль' })).toBeVisible();
    await expect(page.getByLabel('Code')).toBeVisible();
    await expect(page.getByLabel('Label')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();
  });

  test('updates role on /admin/roles/[id]/edit', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedUpdatePayload: Record<string, unknown> | null = null;

    await setupRoleEditApi(page, existingRole, (payload) => {
      capturedUpdatePayload = payload;
    });

    await page.goto('/admin/roles/r-2/edit');
    await expect(
      page.getByRole('heading', { level: 2, name: 'Редактирование роли' })
    ).toBeVisible();
    await expect(page.getByLabel('Code')).toHaveValue('manager');
    await expect(page.getByLabel('Label')).toHaveValue('Менеджер');

    await page.getByLabel('Code').fill('  support ');
    await page.getByLabel('Label').fill(' Служба поддержки ');

    await page.getByRole('button', { name: 'Сохранить' }).click();

    await expect(page).toHaveURL(/\/admin\/roles\/r-2$/);
    expect(capturedUpdatePayload).toEqual({
      code: 'support',
      label: 'Служба поддержки',
    });
  });
});
