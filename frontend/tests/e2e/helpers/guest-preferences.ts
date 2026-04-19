import type { Page } from '@playwright/test';
import { authOrigin } from './admin-auth';

export const GUEST_PREFERENCES_COOKIE = 'guest_preferences_v2';

export const encodeBase64Url = (value: string): string => {
  return Buffer.from(value, 'utf8').toString('base64url');
};

export const decodeBase64Url = (value: string): string => {
  return Buffer.from(value, 'base64url').toString('utf8');
};

export const serializeGuestPreferencesCookie = (value: Record<string, unknown>): string => {
  return encodeBase64Url(JSON.stringify(value));
};

export const parseGuestPreferencesCookie = (value: string): Record<string, unknown> | null => {
  try {
    return JSON.parse(decodeBase64Url(value)) as Record<string, unknown>;
  } catch {
    return null;
  }
};

export const setGuestPreferencesCookie = async (
  page: Page,
  value: Record<string, unknown>
): Promise<void> => {
  await page.context().addCookies([
    {
      name: GUEST_PREFERENCES_COOKIE,
      value: serializeGuestPreferencesCookie(value),
      url: authOrigin,
    },
  ]);
};

export const readGuestPreferencesCookie = async (
  page: Page
): Promise<Record<string, unknown> | null> => {
  const cookies = await page.context().cookies();
  const cookie = cookies.find((item) => item.name === GUEST_PREFERENCES_COOKIE);

  if (!cookie) {
    return null;
  }

  return parseGuestPreferencesCookie(cookie.value);
};
