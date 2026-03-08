import { expect, test, type Page } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../helpers/admin-auth';

const installMockRealtimeEcho = async (page: Page, subscribeFailsInitially = false) => {
  await page.addInitScript((initialSubscribeFail: boolean) => {
    const state = {
      subscribeFail: initialSubscribeFail,
      channels: new Map(),
      connectionBindings: new Map(),
    };

    const ensureChannel = (channelName: string) => {
      if (!state.channels.has(channelName)) {
        state.channels.set(channelName, {
          listeners: new Map(),
          subscribedHandlers: new Set(),
          errorHandlers: new Set(),
        });
      }

      return state.channels.get(channelName);
    };

    const emitConnection = (eventName: string, payload?: unknown) => {
      const callbacks = state.connectionBindings.get(eventName);
      if (!callbacks) {
        return;
      }

      for (const callback of callbacks) {
        callback(payload);
      }
    };

    (window as unknown as Record<string, unknown>).__setMeSettingsSubscribeFail = (
      nextValue: boolean
    ) => {
      state.subscribeFail = nextValue;
    };

    (window as unknown as Record<string, unknown>).__emitMeSettingsRealtime = (
      userId: string,
      payload: unknown
    ) => {
      const channelState = ensureChannel(`me-settings.${userId}`);
      const handlers = channelState.listeners.get('.me.settings.updated');
      if (!handlers) {
        return;
      }

      for (const handler of handlers) {
        handler(payload);
      }
    };

    (window as unknown as Record<string, unknown>).__emitRealtimeConnectionState = (
      stateName: string
    ) => {
      emitConnection(stateName);
    };

    (window as unknown as Record<string, unknown>).__E2E_CREATE_ECHO__ = async () => {
      const echo = {
        private: (channelName: string) => {
          const channelState = ensureChannel(channelName);

          const channelApi = {
            listen: (eventName: string, callback: (payload: unknown) => void) => {
              if (!channelState.listeners.has(eventName)) {
                channelState.listeners.set(eventName, new Set());
              }

              channelState.listeners.get(eventName).add(callback);
              return channelApi;
            },
            stopListening: (eventName: string) => {
              channelState.listeners.delete(eventName);
              return channelApi;
            },
            subscribed: (callback: () => void) => {
              channelState.subscribedHandlers.add(callback);
              queueMicrotask(() => {
                if (!state.subscribeFail) {
                  callback();
                }
              });
              return channelApi;
            },
            error: (callback: (error: unknown) => void) => {
              channelState.errorHandlers.add(callback);
              queueMicrotask(() => {
                if (state.subscribeFail) {
                  callback({ type: 'subscription_error' });
                }
              });
              return channelApi;
            },
          };

          return channelApi;
        },
        leave: () => undefined,
        leaveAllChannels: () => undefined,
        disconnect: () => undefined,
        connector: {
          pusher: {
            connection: {
              bind: (eventName: string, callback: (payload?: unknown) => void) => {
                if (!state.connectionBindings.has(eventName)) {
                  state.connectionBindings.set(eventName, new Set());
                }

                state.connectionBindings.get(eventName).add(callback);
                if (eventName === 'connected') {
                  queueMicrotask(() => callback());
                }
              },
            },
          },
        },
      };

      return echo;
    };
  }, subscribeFailsInitially);
};

test.describe('Admin settings page', () => {
  test('redirects unauthenticated user from /admin/settings to /login', async ({ page }) => {
    await page.goto('/admin/settings');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows settings switches for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page, {
      ...defaultAdminUser,
      settings: {
        theme: 'light',
        collapse_menu: false,
        admin_crud_preferences: {},
      },
    });

    await page.goto('/admin/settings');

    await expect(
      page.getByRole('heading', { level: 2, name: 'Настройки пользователя' })
    ).toBeVisible();

    const themeSwitch = page.getByRole('switch', { name: /Т[её]мная тема|Dark theme/i });
    const menuSwitch = page.getByRole('switch', { name: /Collapse menu/i });

    await expect(themeSwitch).toHaveAttribute('aria-checked', 'false');
    await expect(menuSwitch).toHaveAttribute('aria-checked', 'false');
  });

  test('coalesces rapid settings updates into a single patch request', async ({ page }) => {
    const settingsPatchPayloads: Array<Record<string, unknown>> = [];

    await page.route('**/api/me/settings', async (route) => {
      const rawBody = route.request().postData() ?? '{}';
      settingsPatchPayloads.push(JSON.parse(rawBody) as Record<string, unknown>);

      await route.fulfill({
        status: 204,
        body: '',
      });
    });

    await setupAdminAuth(page, {
      ...defaultAdminUser,
      settings: {
        locale: 'ru',
        theme: 'light',
        collapse_menu: false,
        admin_crud_preferences: {},
        admin_navigation_sections: {},
      },
    });

    await page.goto('/admin/settings');

    const themeSwitch = page.getByRole('switch', { name: /Т[её]мная тема|Dark theme/i });
    await expect(themeSwitch).toHaveAttribute('aria-checked', 'false');

    for (let index = 0; index < 5; index += 1) {
      await themeSwitch.click();
    }

    await page.waitForTimeout(1200);

    await expect.poll(() => settingsPatchPayloads.length).toBe(1);
    expect(settingsPatchPayloads[0]?.settings).toMatchObject({ theme: 'dark' });
  });

  test('applies remote settings via fallback polling when websocket subscribe fails', async ({
    page,
  }) => {
    await installMockRealtimeEcho(page, true);

    await setupAdminAuth(page, {
      ...defaultAdminUser,
      settings: {
        locale: 'ru',
        theme: 'light',
        collapse_menu: false,
        admin_crud_preferences: {},
        admin_navigation_sections: {},
      },
    });

    let meRequests = 0;
    let remoteTheme: 'light' | 'dark' = 'light';
    await page.route('**/api/me', async (route) => {
      meRequests += 1;

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          user: {
            ...defaultAdminUser,
            settings: {
              locale: 'ru',
              theme: remoteTheme,
              collapse_menu: false,
              admin_crud_preferences: {},
              admin_navigation_sections: {},
            },
          },
        }),
      });
    });

    await page.route('**/broadcasting/auth', async (route) => {
      await route.fulfill({
        status: 403,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'error',
          message: 'Forbidden',
          errors: null,
        }),
      });
    });

    await page.goto('/admin/settings');

    const themeSwitch = page.getByRole('switch', { name: /Т[её]мная тема|Dark theme/i });
    await expect(themeSwitch).toHaveAttribute('aria-checked', 'false');

    remoteTheme = 'dark';
    await page.evaluate(() => {
      Object.defineProperty(document, 'visibilityState', {
        configurable: true,
        get: () => 'visible',
      });
      document.dispatchEvent(new Event('visibilitychange'));
      window.dispatchEvent(new Event('focus'));
    });

    await expect.poll(() => meRequests, { timeout: 20_000 }).toBeGreaterThan(1);
    await expect(themeSwitch).toHaveAttribute('aria-checked', 'true', { timeout: 20_000 });
  });

  test('syncs settings in another session without page refresh', async ({ page, context }) => {
    const secondSessionPage = await context.newPage();
    await installMockRealtimeEcho(page, false);
    await installMockRealtimeEcho(secondSessionPage, false);

    try {
      await setupAdminAuth(page, {
        ...defaultAdminUser,
        settings: {
          locale: 'ru',
          theme: 'light',
          collapse_menu: false,
          admin_crud_preferences: {},
          admin_navigation_sections: {},
        },
      });
      await setupAdminAuth(secondSessionPage, {
        ...defaultAdminUser,
        settings: {
          locale: 'ru',
          theme: 'light',
          collapse_menu: false,
          admin_crud_preferences: {},
          admin_navigation_sections: {},
        },
      });

      let settingsPatchRequests = 0;
      await page.route('**/api/me/settings', async (route) => {
        settingsPatchRequests += 1;
        const payload = JSON.parse(route.request().postData() ?? '{}') as Record<string, unknown>;

        await secondSessionPage.evaluate(
          (incomingPayload) => {
            (window as unknown as Record<string, any>).__emitMeSettingsRealtime?.('1', {
              user_id: '1',
              settings: incomingPayload,
            });
          },
          (payload.settings ?? {}) as Record<string, unknown>
        );

        await route.fulfill({
          status: 204,
          body: '',
        });
      });

      await page.goto('/admin/settings');
      await secondSessionPage.goto('/admin/settings');

      const sessionASwitch = page.getByRole('switch', { name: /Т[её]мная тема|Dark theme/i });
      const sessionBSwitch = secondSessionPage.getByRole('switch', {
        name: /Т[её]мная тема|Dark theme/i,
      });

      await expect(sessionASwitch).toHaveAttribute('aria-checked', 'false');
      await expect(sessionBSwitch).toHaveAttribute('aria-checked', 'false');

      await sessionASwitch.click();

      await expect.poll(() => settingsPatchRequests).toBe(1);
      await expect(sessionBSwitch).toHaveAttribute('aria-checked', 'true', { timeout: 10_000 });
    } finally {
      await secondSessionPage.close();
    }
  });

  test('switches from fallback polling back to websocket after recovery', async ({ page }) => {
    await installMockRealtimeEcho(page, true);

    await setupAdminAuth(page, {
      ...defaultAdminUser,
      settings: {
        locale: 'ru',
        theme: 'light',
        collapse_menu: false,
        admin_crud_preferences: {},
        admin_navigation_sections: {},
      },
    });

    let meRequests = 0;
    let remoteTheme: 'light' | 'dark' = 'light';
    await page.route('**/api/me', async (route) => {
      meRequests += 1;

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          user: {
            ...defaultAdminUser,
            settings: {
              locale: 'ru',
              theme: remoteTheme,
              collapse_menu: false,
              admin_crud_preferences: {},
              admin_navigation_sections: {},
            },
          },
        }),
      });
    });

    await page.goto('/admin/settings');

    const themeSwitch = page.getByRole('switch', { name: /Т[её]мная тема|Dark theme/i });
    await expect(themeSwitch).toHaveAttribute('aria-checked', 'false');

    remoteTheme = 'dark';
    await page.evaluate(() => {
      Object.defineProperty(document, 'visibilityState', {
        configurable: true,
        get: () => 'visible',
      });
      document.dispatchEvent(new Event('visibilitychange'));
      window.dispatchEvent(new Event('focus'));
    });

    await expect.poll(() => meRequests, { timeout: 20_000 }).toBeGreaterThan(1);
    await expect(themeSwitch).toHaveAttribute('aria-checked', 'true', { timeout: 20_000 });

    await page.evaluate(() => {
      (window as unknown as Record<string, any>).__setMeSettingsSubscribeFail?.(false);
    });
    await page.waitForTimeout(2_500);

    const meRequestsAfterRecovery = meRequests;
    await page.evaluate(() => {
      (window as unknown as Record<string, any>).__emitMeSettingsRealtime?.('1', {
        user_id: '1',
        settings: { theme: 'light' },
      });
    });

    await expect(themeSwitch).toHaveAttribute('aria-checked', 'false', { timeout: 10_000 });

    await page.evaluate(() => {
      document.dispatchEvent(new Event('visibilitychange'));
      window.dispatchEvent(new Event('focus'));
    });
    await page.waitForTimeout(400);
    expect(meRequests).toBe(meRequestsAfterRecovery);
  });
});
