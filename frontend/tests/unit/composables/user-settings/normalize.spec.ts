import { describe, expect, it } from 'vitest';
import {
  isContentMode,
  isLocaleCode,
  isThemeMode,
  mergeIncomingSettings,
  mergePatchWithSettings,
  mergeSettings,
} from '~/composables/user-settings/normalize';
import type { UserSettings } from '~/composables/user-settings/types';

const baseSettings: UserSettings = {
  locale: 'ru',
  theme: 'dark',
  collapse_menu: true,
  admin_crud_preferences: {
    users: {
      contentMode: 'cards',
      tableOnDesktop: false,
    },
  },
  admin_navigation_sections: {
    system: {
      open: true,
    },
  },
};

describe('user settings normalize', () => {
  it('validates locale/theme/contentMode guards', () => {
    expect(isLocaleCode('ru')).toBe(true);
    expect(isLocaleCode('en')).toBe(true);
    expect(isLocaleCode('de')).toBe(false);

    expect(isThemeMode('dark')).toBe(true);
    expect(isThemeMode('light')).toBe(true);
    expect(isThemeMode('system')).toBe(false);

    expect(isContentMode('table')).toBe(true);
    expect(isContentMode('table-cards')).toBe(true);
    expect(isContentMode('cards')).toBe(true);
    expect(isContentMode('grid')).toBe(false);
  });

  it('merges full settings payload and drops invalid values', () => {
    const merged = mergeSettings({
      locale: 'en',
      theme: 'dark',
      collapse_menu: false,
      admin_crud_preferences: {
        users: { contentMode: 'table-cards', tableOnDesktop: true },
        roles: { contentMode: 'cards' },
        invalid: { contentMode: 'grid' },
      },
      admin_navigation_sections: {
        system: { open: false },
        invalid: { open: 'yes' },
      },
    } as unknown as Partial<UserSettings>);

    expect(merged.locale).toBe('en');
    expect(merged.theme).toBe('dark');
    expect(merged.collapse_menu).toBe(false);
    expect(merged.admin_crud_preferences).toEqual({
      users: { contentMode: 'table-cards', tableOnDesktop: true },
      roles: { contentMode: 'cards' },
    });
    expect(merged.admin_navigation_sections).toEqual({
      system: { open: false },
    });
  });

  it('applies incoming partial settings over current state', () => {
    const next = mergeIncomingSettings(baseSettings, {
      locale: 'en',
      admin_crud_preferences: {
        users: { tableOnDesktop: true },
        roles: { contentMode: 'table' },
      },
      admin_navigation_sections: {
        system: { open: false },
      },
    });

    expect(next).toEqual({
      locale: 'en',
      theme: 'dark',
      collapse_menu: true,
      admin_crud_preferences: {
        users: { tableOnDesktop: true },
        roles: { contentMode: 'table' },
      },
      admin_navigation_sections: {
        system: { open: false },
      },
    });
  });

  it('applies local patch and preserves existing invalid values', () => {
    const next = mergePatchWithSettings(baseSettings, {
      locale: null,
      theme: 'light',
      collapse_menu: false,
      admin_crud_preferences: {
        users: { contentMode: 'table' },
      },
      admin_navigation_sections: {
        system: { open: false },
      },
    });

    expect(next).toEqual({
      locale: null,
      theme: 'light',
      collapse_menu: false,
      admin_crud_preferences: {
        users: { contentMode: 'table' },
      },
      admin_navigation_sections: {
        system: { open: false },
      },
    });
  });
});
