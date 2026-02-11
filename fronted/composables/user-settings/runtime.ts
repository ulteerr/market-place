import type { ThemeMode, UserSettings } from '~/composables/user-settings/types';

export const applyThemeToDocument = (theme: ThemeMode) => {
  if (!process.client) {
    return;
  }

  document.documentElement.setAttribute('data-theme', theme);
  document.documentElement.classList.toggle('dark', theme === 'dark');
  document.documentElement.style.colorScheme = theme;
};

export const settingsAreSame = (left: UserSettings, right: UserSettings): boolean => {
  return (
    left.locale === right.locale &&
    left.theme === right.theme &&
    left.collapse_menu === right.collapse_menu &&
    JSON.stringify(left.admin_crud_preferences) === JSON.stringify(right.admin_crud_preferences) &&
    JSON.stringify(left.admin_navigation_sections) ===
      JSON.stringify(right.admin_navigation_sections)
  );
};

export const cloneSettings = (value: UserSettings): UserSettings => ({
  locale: value.locale,
  theme: value.theme,
  collapse_menu: value.collapse_menu,
  admin_crud_preferences: { ...value.admin_crud_preferences },
  admin_navigation_sections: { ...value.admin_navigation_sections },
});

export const sleep = (ms: number): Promise<void> => {
  return new Promise((resolve) => {
    setTimeout(resolve, ms);
  });
};

export const buildApiUrl = (baseUrl: string, path: string): string => {
  try {
    return new URL(path, baseUrl).toString();
  } catch {
    return path;
  }
};
