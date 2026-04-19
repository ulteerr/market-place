import { expect, test, type Page } from '@playwright/test';
import {
  readGuestPreferencesCookie,
  setGuestPreferencesCookie,
} from '../helpers/guest-preferences';
import { E2E_VIEWPORTS } from '../helpers/viewports';

const mockCategoryTree = async (page: Page) => {
  await page.route('**/api/categories/tree**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: [],
      }),
    });
  });
};

const waitForNuxtHydration = async (page: Page) => {
  await page.waitForFunction(() => {
    const runtimeWindow = window as Window & {
      useNuxtApp?: () => { isHydrating?: boolean };
    };

    if (typeof runtimeWindow.useNuxtApp !== 'function') {
      return false;
    }

    return runtimeWindow.useNuxtApp()?.isHydrating === false;
  });
};

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasHorizontalOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasHorizontalOverflow).toBeFalsy();
};

type MockCity = {
  id: string;
  name: string;
  country_id: string;
  region_id: string | null;
  country: {
    name: string;
  };
  region: {
    name: string;
  } | null;
};

const moscowCity: MockCity = {
  id: 'city-msk',
  name: 'Москва',
  country_id: 'country-ru',
  region_id: 'region-msk',
  country: {
    name: 'Россия',
  },
  region: {
    name: 'Москва',
  },
};

const spbCity: MockCity = {
  id: 'city-spb',
  name: 'Санкт-Петербург',
  country_id: 'country-ru',
  region_id: 'region-spb',
  country: {
    name: 'Россия',
  },
  region: {
    name: 'Санкт-Петербург',
  },
};

const toGuestPublicCity = (city: MockCity, source: 'ip_auto' | 'manual' = 'manual') => ({
  city_id: city.id,
  city_name: city.name,
  source,
  region_id: city.region_id,
  region_name: city.region?.name ?? null,
  country_id: city.country_id,
  country_name: city.country.name,
});

const setManualGuestCity = async (page: Page, city: MockCity) => {
  await setGuestPreferencesCookie(page, {
    v: 1,
    settings: {
      locale: null,
      theme: 'light',
      collapse_menu: false,
      favorites: [],
      public_city: toGuestPublicCity(city, 'manual'),
      admin_crud_preferences: {},
      admin_navigation_sections: {},
    },
  });
};

const mockPublicGeoCities = async (page: Page, cities: MockCity[] = [moscowCity, spbCity]) => {
  await page.route('**/api/public/geo/cities**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: cities,
      }),
    });
  });
};

const mockEmptyPublicActivities = async (page: Page) => {
  await page.route('**/api/activities/featured**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: [],
      }),
    });
  });

  await page.route('**/api/activities/feed**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          items: [],
          next_cursor: null,
        },
      }),
    });
  });
};

const waitForCityRequest = (page: Page, path: string, cityId: string) =>
  page.waitForRequest(
    (request) =>
      request.method() === 'GET' &&
      request.url().includes(path) &&
      new URL(request.url()).searchParams.get('city_id') === cityId,
    { timeout: 10_000 }
  );

const selectCityFromHeader = async (page: Page, cityName: string) => {
  await page.locator('[data-test="public-header-city-picker-toggle"]').last().click();

  const dialog = page.getByRole('dialog', { name: 'Выбор города' });
  await expect(dialog).toBeVisible();

  await dialog.getByRole('combobox', { name: 'Город' }).click();
  await page.getByRole('option', { name: cityName }).click();
};

const expectDetectedCityModal = async (page: Page, cityName: string) => {
  await expect(page.getByRole('dialog', { name: `Ваш город ${cityName}?` })).toBeVisible();
  await expect(page.locator('[data-test="public-header-city-modal"]')).toBeVisible();
};

test.describe('Public city selection', () => {
  test('applies ip-detected city and shows modal', async ({ page }) => {
    await mockCategoryTree(page);

    await page.route('**/api/public/geo/detect-city**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            resolved_by: 'ip',
            city: {
              id: 'city-ekb',
              name: 'Екатеринбург',
              country_id: 'country-ru',
              region_id: 'region-sverdlovsk',
              country: {
                name: 'Россия',
              },
              region: {
                name: 'Свердловская область',
              },
            },
          },
        }),
      });
    });

    await page.goto('/?state=loading');
    await waitForNuxtHydration(page);

    await expectDetectedCityModal(page, 'Екатеринбург');

    const cityButton = page.locator('[data-test="public-header-city-picker-toggle"]').last();
    await expect(cityButton).toContainText('Екатеринбург');

    await page.locator('[data-test="public-header-city-modal-confirm"]').click();
    await expect(page.locator('[data-test="public-header-city-modal"]')).toHaveCount(0);
    await expect(cityButton).toContainText('Екатеринбург');

    const preferences = await readGuestPreferencesCookie(page);
    const publicCity = (preferences?.settings as Record<string, unknown> | undefined)
      ?.public_city as Record<string, unknown> | null | undefined;

    expect(publicCity).toMatchObject({
      city_id: 'city-ekb',
      city_name: 'Екатеринбург',
      source: 'ip_auto',
      country_id: 'country-ru',
      country_name: 'Россия',
    });
  });

  test('dismisses detected city modal without clearing selected city', async ({ page }) => {
    await mockCategoryTree(page);

    await page.route('**/api/public/geo/detect-city**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            resolved_by: 'ip',
            city: moscowCity,
          },
        }),
      });
    });

    await page.goto('/?state=loading');
    await waitForNuxtHydration(page);

    await expectDetectedCityModal(page, 'Москва');

    await page.locator('[data-test="public-header-city-modal-dismiss"]').click();
    await expect(page.locator('[data-test="public-header-city-modal"]')).toHaveCount(0);
    await expect(
      page.locator('[data-test="public-header-city-picker-toggle"]').last()
    ).toContainText('Москва');

    const preferences = await readGuestPreferencesCookie(page);
    const publicCity = (preferences?.settings as Record<string, unknown> | undefined)
      ?.public_city as Record<string, unknown> | null | undefined;

    expect(publicCity).toMatchObject({
      city_id: 'city-msk',
      city_name: 'Москва',
      source: 'ip_auto',
    });

    await page.reload();
    await waitForNuxtHydration(page);

    await expect(page.locator('[data-test="public-header-city-modal"]')).toHaveCount(0);
    await expect(
      page.locator('[data-test="public-header-city-picker-toggle"]').last()
    ).toContainText('Москва');
  });

  test('shows modal for fallback city default', async ({ page }) => {
    await mockCategoryTree(page);

    await page.route('**/api/public/geo/detect-city**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            resolved_by: 'fallback',
            city: moscowCity,
          },
        }),
      });
    });

    await page.goto('/?state=loading');
    await waitForNuxtHydration(page);

    await expectDetectedCityModal(page, 'Москва');
    await expect(
      page.locator('[data-test="public-header-city-picker-toggle"]').last()
    ).toContainText('Москва');

    const preferences = await readGuestPreferencesCookie(page);
    const publicCity = (preferences?.settings as Record<string, unknown> | undefined)
      ?.public_city as Record<string, unknown> | null | undefined;

    expect(publicCity).toMatchObject({
      city_id: 'city-msk',
      city_name: 'Москва',
      source: 'ip_auto',
    });
  });

  test('supports detected city modal and city picker on mobile header', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.mobile390);
    await mockCategoryTree(page);
    await mockPublicGeoCities(page);

    await page.route('**/api/public/geo/detect-city**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            resolved_by: 'ip',
            city: moscowCity,
          },
        }),
      });
    });

    await page.goto('/?state=loading');
    await waitForNuxtHydration(page);

    await expectDetectedCityModal(page, 'Москва');
    await assertNoHorizontalOverflow(page);

    await page.locator('[data-test="public-header-city-modal-dismiss"]').click();
    await expect(page.locator('[data-test="public-header-city-modal"]')).toHaveCount(0);

    const mobileMenuToggle = page.locator('[data-test="public-header-mobile-menu-toggle"]').last();
    const mobileMenu = page.locator('[data-test="public-header-mobile-menu"]').last();
    await expect(mobileMenuToggle).toBeVisible();
    await mobileMenuToggle.click();
    await expect(mobileMenu).toBeVisible();
    await expect(page.locator('[data-test="public-header-mobile-search"]').last()).toBeVisible();

    await page.locator('[data-test="public-header-mobile-city-picker-toggle"]').last().click();

    const cityPicker = page.getByRole('dialog', { name: 'Выбор города' });
    await expect(cityPicker).toBeVisible();

    await cityPicker.getByRole('combobox', { name: 'Город' }).click();
    await page.getByRole('option', { name: 'Санкт-Петербург' }).click();

    await expect(cityPicker).toHaveCount(0);

    if ((await mobileMenu.count()) === 0) {
      await mobileMenuToggle.click();
    }

    await expect(mobileMenu).toBeVisible();
    await expect(
      page.locator('[data-test="public-header-mobile-city-picker-toggle"]').last()
    ).toContainText('Санкт-Петербург');
    await expect(page.locator('[data-test="public-header-mobile-search"]').last()).toBeVisible();
    await expect(
      page.locator('[data-test="public-header-mobile-locale-select"]').last()
    ).toBeVisible();
    await assertNoHorizontalOverflow(page);
  });

  test('allows replacing detected city with a manual choice', async ({ page }) => {
    await mockCategoryTree(page);

    await page.route('**/api/public/geo/detect-city**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            resolved_by: 'ip',
            city: {
              id: 'city-msk',
              name: 'Москва',
              country_id: 'country-ru',
              region_id: 'region-msk',
              country: {
                name: 'Россия',
              },
              region: {
                name: 'Москва',
              },
            },
          },
        }),
      });
    });

    await page.route('**/api/public/geo/cities**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: [
            {
              id: 'city-msk',
              name: 'Москва',
              country_id: 'country-ru',
              region_id: 'region-msk',
              country: {
                name: 'Россия',
              },
              region: {
                name: 'Москва',
              },
            },
            {
              id: 'city-spb',
              name: 'Санкт-Петербург',
              country_id: 'country-ru',
              region_id: 'region-spb',
              country: {
                name: 'Россия',
              },
              region: {
                name: 'Санкт-Петербург',
              },
            },
          ],
        }),
      });
    });

    await page.goto('/?state=loading');
    await waitForNuxtHydration(page);

    await expectDetectedCityModal(page, 'Москва');

    await page.locator('[data-test="public-header-city-modal-change"]').click();

    const dialog = page.getByRole('dialog', { name: 'Выбор города' });
    await expect(dialog).toBeVisible();

    const citySelect = dialog.getByRole('combobox', { name: 'Город' });
    await citySelect.click();
    await page.getByRole('option', { name: 'Санкт-Петербург' }).click();

    await expect(dialog).toHaveCount(0);
    await expect(page.locator('[data-test="public-header-city-modal"]')).toHaveCount(0);
    await expect(
      page.locator('[data-test="public-header-city-picker-toggle"]').last()
    ).toContainText('Санкт-Петербург');

    const preferences = await readGuestPreferencesCookie(page);
    const publicCity = (preferences?.settings as Record<string, unknown> | undefined)
      ?.public_city as Record<string, unknown> | null | undefined;

    expect(publicCity).toMatchObject({
      city_id: 'city-spb',
      city_name: 'Санкт-Петербург',
      source: 'manual',
      country_id: 'country-ru',
      country_name: 'Россия',
    });
  });

  test('refreshes home content requests after manual city change', async ({ page }) => {
    await setManualGuestCity(page, spbCity);
    await mockCategoryTree(page);
    await mockPublicGeoCities(page);
    await mockEmptyPublicActivities(page);

    await page.goto('/');
    await waitForNuxtHydration(page);

    await expect(
      page.locator('[data-test="public-header-city-picker-toggle"]').last()
    ).toContainText('Санкт-Петербург');

    const featuredRequestPromise = waitForCityRequest(page, '/api/activities/featured', 'city-msk');
    const feedRequestPromise = waitForCityRequest(page, '/api/activities/feed', 'city-msk');

    await selectCityFromHeader(page, 'Москва');

    await Promise.all([featuredRequestPromise, feedRequestPromise]);
    await expect(
      page.locator('[data-test="public-header-city-picker-toggle"]').last()
    ).toContainText('Москва');
  });

  test('refreshes catalog feed requests after manual city change', async ({ page }) => {
    await setManualGuestCity(page, spbCity);
    await mockCategoryTree(page);
    await mockPublicGeoCities(page);
    await mockEmptyPublicActivities(page);

    await page.goto('/catalog');
    await waitForNuxtHydration(page);

    await expect(
      page.locator('[data-test="public-header-city-picker-toggle"]').last()
    ).toContainText('Санкт-Петербург');

    const feedRequestPromise = waitForCityRequest(page, '/api/activities/feed', 'city-msk');

    await selectCityFromHeader(page, 'Москва');

    await feedRequestPromise;
    await expect(
      page.locator('[data-test="public-header-city-picker-toggle"]').last()
    ).toContainText('Москва');
  });

  test('keeps manual city selection after reload', async ({ page }) => {
    await mockCategoryTree(page);

    await page.route('**/api/public/geo/detect-city**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            resolved_by: 'ip',
            city: {
              id: 'city-msk',
              name: 'Москва',
              country_id: 'country-ru',
              region_id: 'region-msk',
              country: {
                name: 'Россия',
              },
              region: {
                name: 'Москва',
              },
            },
          },
        }),
      });
    });

    await page.route('**/api/public/geo/cities**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: [
            {
              id: 'city-spb',
              name: 'Санкт-Петербург',
              country_id: 'country-ru',
              region_id: 'region-spb',
              country: {
                name: 'Россия',
              },
              region: {
                name: 'Санкт-Петербург',
              },
            },
          ],
        }),
      });
    });

    await page.goto('/?state=loading');
    await waitForNuxtHydration(page);

    await page.locator('[data-test="public-header-city-modal-change"]').click();

    const dialog = page.getByRole('dialog', { name: 'Выбор города' });
    await expect(dialog).toBeVisible();

    await dialog.getByRole('combobox', { name: 'Город' }).click();
    await page.getByRole('option', { name: 'Санкт-Петербург' }).click();

    await expect(
      page.locator('[data-test="public-header-city-picker-toggle"]').last()
    ).toContainText('Санкт-Петербург');

    await page.reload();
    await waitForNuxtHydration(page);

    await expect(
      page.locator('[data-test="public-header-city-picker-toggle"]').last()
    ).toContainText('Санкт-Петербург');
    await expect(page.locator('[data-test="public-header-city-modal"]')).toHaveCount(0);
  });

  test('includes selected city in search suggest requests', async ({ page }) => {
    await mockCategoryTree(page);
    await setGuestPreferencesCookie(page, {
      v: 1,
      settings: {
        locale: null,
        theme: 'light',
        collapse_menu: false,
        favorites: [],
        public_city: {
          city_id: 'city-spb',
          city_name: 'Санкт-Петербург',
          source: 'manual',
          region_id: null,
          region_name: null,
          country_id: 'country-ru',
          country_name: 'Россия',
        },
        admin_crud_preferences: {},
        admin_navigation_sections: {},
      },
    });

    await page.route('**/api/search/suggest**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            queries: ['Футбол'],
            entities: [],
            recent: [],
          },
        }),
      });
    });

    await page.goto('/?state=loading');
    await waitForNuxtHydration(page);

    const searchInput = page.locator('[data-test="public-header-search"]').last();
    const suggestRequestPromise = page.waitForRequest(
      (request) => request.method() === 'GET' && request.url().includes('/api/search/suggest'),
      { timeout: 10_000 }
    );

    await searchInput.click();
    await searchInput.fill('фут');

    const suggestRequest = await suggestRequestPromise;
    const suggestUrl = new URL(suggestRequest.url());

    expect(suggestUrl.searchParams.get('city_id')).toBe('city-spb');
  });
});
