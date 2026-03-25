import { describe, expect, it } from 'vitest';
import {
  deserializeGuestPreferences,
  mergeGuestSettingsIntoAccountSettings,
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
        admin_crud_preferences: {},
        admin_navigation_sections: {},
      },
      favorites: ['activity-1', 'activity-2'],
    });
  });

  it('merges guest settings over account settings for auth sync', () => {
    expect(
      mergeGuestSettingsIntoAccountSettings(
        {
          locale: 'en',
          theme: 'light',
          collapse_menu: false,
        },
        {
          theme: 'dark',
        }
      )
    ).toEqual({
      locale: 'en',
      theme: 'dark',
      collapse_menu: false,
      admin_crud_preferences: {},
      admin_navigation_sections: {},
    });
  });
});
