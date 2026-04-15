import { describe, expect, it } from 'vitest';
import {
  deserializeGuestPreferences,
  mergeGuestPreferencesIntoAccountSettings,
  serializeGuestPreferences,
} from '~/composables/guest-preferences';

describe('guest preferences runtime', () => {
  it('serializes and deserializes normalized guest preferences', () => {
    const serialized = serializeGuestPreferences({
      v: 1,
      settings: {
        theme: 'dark',
        locale: 'ru',
      },
      favorites: ['activity-1', 'activity-1', 'activity-2'],
    });

    expect(typeof serialized).toBe('string');
    expect(serialized).not.toContain('{');

    expect(deserializeGuestPreferences(serialized)).toEqual({
      v: 1,
      settings: {
        locale: 'ru',
        theme: 'dark',
        collapse_menu: false,
        favorites: ['activity-1', 'activity-2'],
        admin_crud_preferences: {},
        admin_navigation_sections: {},
      },
      favorites: ['activity-1', 'activity-2'],
    });
  });

  it('merges guest preferences over account settings for auth sync', () => {
    expect(
      mergeGuestPreferencesIntoAccountSettings(
        {
          locale: 'en',
          theme: 'light',
          collapse_menu: false,
          favorites: ['account-1'],
        },
        {
          v: 1,
          settings: { theme: 'dark' },
          favorites: ['guest-1'],
        }
      )
    ).toEqual({
      locale: 'en',
      theme: 'dark',
      collapse_menu: false,
      favorites: ['guest-1', 'account-1'],
      admin_crud_preferences: {},
      admin_navigation_sections: {},
    });
  });
});
