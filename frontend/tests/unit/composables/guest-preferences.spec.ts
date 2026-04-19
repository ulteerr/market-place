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
        public_city: null,
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
          public_city: null,
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
      public_city: null,
      admin_crud_preferences: {},
      admin_navigation_sections: {},
    });
  });

  it('does not let default guest settings clobber account settings on auth sync', () => {
    expect(
      mergeGuestPreferencesIntoAccountSettings(
        {
          locale: 'en',
          theme: 'dark',
          collapse_menu: true,
          favorites: ['account-1'],
          public_city: {
            city_id: 'city-account',
            city_name: 'Москва',
            source: 'manual',
            region_id: null,
            region_name: null,
            country_id: 'country-ru',
            country_name: 'Россия',
          },
          admin_crud_preferences: {
            users: {
              contentMode: 'cards',
            },
          },
          admin_navigation_sections: {
            system: {
              open: true,
            },
          },
        },
        deserializeGuestPreferences(
          serializeGuestPreferences({
            v: 1,
            settings: {},
          })
        )
      )
    ).toEqual({
      locale: 'en',
      theme: 'dark',
      collapse_menu: true,
      favorites: ['account-1'],
      public_city: {
        city_id: 'city-account',
        city_name: 'Москва',
        source: 'manual',
        region_id: null,
        region_name: null,
        country_id: 'country-ru',
        country_name: 'Россия',
      },
      admin_crud_preferences: {
        users: {
          contentMode: 'cards',
        },
      },
      admin_navigation_sections: {
        system: {
          open: true,
        },
      },
    });
  });

  it('merges guest city and favorites without resetting unrelated account settings', () => {
    expect(
      mergeGuestPreferencesIntoAccountSettings(
        {
          locale: 'en',
          theme: 'dark',
          collapse_menu: true,
          favorites: ['account-1'],
          public_city: null,
          admin_crud_preferences: {},
          admin_navigation_sections: {
            system: {
              open: true,
            },
          },
        },
        deserializeGuestPreferences(
          serializeGuestPreferences({
            v: 1,
            settings: {
              public_city: {
                city_id: 'city-guest',
                city_name: 'Санкт-Петербург',
                source: 'manual',
                region_id: null,
                region_name: null,
                country_id: 'country-ru',
                country_name: 'Россия',
              },
            },
            favorites: ['guest-1'],
          })
        )
      )
    ).toEqual({
      locale: 'en',
      theme: 'dark',
      collapse_menu: true,
      favorites: ['guest-1', 'account-1'],
      public_city: {
        city_id: 'city-guest',
        city_name: 'Санкт-Петербург',
        source: 'manual',
        region_id: null,
        region_name: null,
        country_id: 'country-ru',
        country_name: 'Россия',
      },
      admin_crud_preferences: {},
      admin_navigation_sections: {
        system: {
          open: true,
        },
      },
    });
  });
});
