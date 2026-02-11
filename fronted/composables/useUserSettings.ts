import {
  SETTINGS_STREAM_PATH,
  SETTINGS_SYNC_DEBOUNCE_MS,
  createDefaultSettings,
} from '~/composables/user-settings/constants';
import {
  mergeIncomingSettings,
  mergePatchWithSettings,
  mergeSettings,
} from '~/composables/user-settings/normalize';
import {
  applyThemeToDocument,
  buildApiUrl,
  cloneSettings,
  settingsAreSame,
} from '~/composables/user-settings/runtime';
import { createSettingsStreamController } from '~/composables/user-settings/stream';
import type {
  AdminCrudContentMode,
  LocaleCode,
  ThemeMode,
  UserSettings,
} from '~/composables/user-settings/types';

export type { AdminCrudContentMode, LocaleCode };

let lastCollapseToggleAt = 0;
let syncInFlight = false;
let syncQueued = false;
let lastSyncedSettingsSnapshot = '';
let pendingSettingsSnapshot: string | null = null;

export const useUserSettings = () => {
  const { user, token, isAuthenticated, updateSettings: updateRemoteSettings } = useAuth();
  const config = useRuntimeConfig();

  const settings = useState<UserSettings>('user_settings', createDefaultSettings);

  const initialized = useState<boolean>('user_settings_initialized', () => false);
  const streamWatcherReady = useState<boolean>('user_settings_stream_watcher_ready', () => false);
  const remoteSettingsWatcherReady = useState<boolean>(
    'user_settings_remote_settings_watcher_ready',
    () => false
  );
  let syncTimer: ReturnType<typeof setTimeout> | null = null;

  const theme = computed(() => settings.value.theme);
  const isDark = computed(() => theme.value === 'dark');
  const settingsStream = createSettingsStreamController({
    streamUrl: buildApiUrl(config.public.apiBase, SETTINGS_STREAM_PATH),
    getIsAuthenticated: () => isAuthenticated.value,
    getToken: () => token.value,
    onSettings: (remoteSettings) => {
      applyRemoteSettings(remoteSettings);
    },
  });

  const persist = () => {
    // server is the source of truth for settings
  };

  const toSettingsSnapshot = (value: UserSettings): string => JSON.stringify(cloneSettings(value));

  const pushSettingsToServer = async () => {
    if (!process.client || !isAuthenticated.value) {
      return;
    }

    const payload = cloneSettings(settings.value);
    const snapshot = JSON.stringify(payload);

    if (snapshot === lastSyncedSettingsSnapshot) {
      return;
    }

    if (syncInFlight) {
      syncQueued = true;
      return;
    }

    syncInFlight = true;

    try {
      await updateRemoteSettings(payload);
      lastSyncedSettingsSnapshot = snapshot;
    } catch {
      // keep local settings and retry on next update
    } finally {
      syncInFlight = false;

      if (syncQueued) {
        syncQueued = false;
        await pushSettingsToServer();
      }
    }
  };

  const queueSyncToServer = () => {
    if (!process.client || !isAuthenticated.value) {
      return;
    }

    if (syncTimer) {
      return;
    }

    syncTimer = setTimeout(async () => {
      syncTimer = null;
      await pushSettingsToServer();
    }, SETTINGS_SYNC_DEBOUNCE_MS);
  };

  const applySettings = (nextSettings: UserSettings, syncRemote = true) => {
    const hasChanges = !settingsAreSame(settings.value, nextSettings);

    if (hasChanges) {
      settings.value = {
        locale: nextSettings.locale,
        theme: nextSettings.theme,
        collapse_menu: nextSettings.collapse_menu,
        admin_crud_preferences: { ...nextSettings.admin_crud_preferences },
        admin_navigation_sections: { ...nextSettings.admin_navigation_sections },
      };
    }

    // During SSR hydration values can already match, but theme still must be
    // applied to the real document on client.
    applyThemeToDocument(nextSettings.theme);
    persist();

    if (syncRemote && hasChanges) {
      if (process.client && isAuthenticated.value) {
        pendingSettingsSnapshot = toSettingsSnapshot(nextSettings);
      }
      queueSyncToServer();
    }
  };

  const applyRemoteSettings = (remote: unknown) => {
    const normalized = mergeIncomingSettings(settings.value, remote);
    const remoteSnapshot = toSettingsSnapshot(normalized);
    const localSnapshot = toSettingsSnapshot(settings.value);

    if (pendingSettingsSnapshot) {
      if (remoteSnapshot === pendingSettingsSnapshot) {
        pendingSettingsSnapshot = null;
      } else if (remoteSnapshot !== localSnapshot) {
        return;
      }
    }

    applySettings(normalized, false);
    lastSyncedSettingsSnapshot = remoteSnapshot;
  };

  const applyServerSettings = (remote: Partial<UserSettings> | null) => {
    const normalized = mergeSettings(remote);
    applySettings(normalized, false);
    lastSyncedSettingsSnapshot = toSettingsSnapshot(normalized);
    pendingSettingsSnapshot = null;
  };

  const updateSettings = (patch: Partial<UserSettings>, syncRemote = true) => {
    const nextSettings = mergePatchWithSettings(settings.value, patch);
    applySettings(nextSettings, syncRemote);
  };

  const setTheme = (nextTheme: ThemeMode) => {
    updateSettings({ theme: nextTheme });
  };

  const toggleTheme = () => {
    setTheme(isDark.value ? 'light' : 'dark');
  };

  const setCollapseMenu = (value: boolean) => {
    const now = Date.now();
    if (now - lastCollapseToggleAt < 180) {
      return;
    }

    lastCollapseToggleAt = now;
    updateSettings({ collapse_menu: value });
  };

  const toggleCollapseMenu = () => {
    setCollapseMenu(!settings.value.collapse_menu);
  };

  const initSettings = () => {
    if (initialized.value) {
      return;
    }

    const remoteSettings = (user.value?.settings ?? null) as Partial<UserSettings> | null;
    const mergedSettings = mergeSettings(remoteSettings);

    applySettings(mergedSettings, false);
    lastSyncedSettingsSnapshot = toSettingsSnapshot(mergedSettings);
    pendingSettingsSnapshot = null;
    initialized.value = true;
  };

  if (!initialized.value) {
    initSettings();
  }

  const startRemoteSync = () => {
    settingsStream.start();
  };

  const stopRemoteSync = () => {
    settingsStream.stop();
  };

  if (process.client && !streamWatcherReady.value) {
    watch(
      [isAuthenticated, token],
      ([authenticated, nextToken]) => {
        if (authenticated && nextToken) {
          startRemoteSync();
          return;
        }

        stopRemoteSync();
      },
      { immediate: true }
    );

    streamWatcherReady.value = true;
  }

  if (process.client && !remoteSettingsWatcherReady.value) {
    watch(
      () => user.value,
      (nextUser) => {
        if (!initialized.value || !nextUser) {
          return;
        }

        const mergedSettings = mergeSettings(
          (nextUser.settings ?? null) as Partial<UserSettings> | null
        );

        applySettings(mergedSettings, false);
      },
      { immediate: true }
    );

    remoteSettingsWatcherReady.value = true;
  }

  return {
    settings,
    theme,
    isDark,
    initSettings,
    applyServerSettings,
    setTheme,
    toggleTheme,
    setCollapseMenu,
    toggleCollapseMenu,
    updateSettings,
    startRemoteSync,
    stopRemoteSync,
  };
};
