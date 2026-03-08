import {
  SETTINGS_FALLBACK_POLL_INTERVAL_MS,
  SETTINGS_FALLBACK_POLL_MAX_BACKOFF_MS,
  SETTINGS_SYNC_DEBOUNCE_MS,
  createDefaultSettings,
} from '~/composables/user-settings/constants';
import { useRealtimeEcho } from '~/composables/realtime-echo/useRealtimeEcho';
import { useRealtimeEchoState } from '~/composables/realtime-echo/useRealtimeEchoState';
import { createSettingsFallbackPollingController } from '~/composables/user-settings/fallback';
import {
  mergeIncomingSettings,
  mergePatchWithSettings,
  mergeSettings,
} from '~/composables/user-settings/normalize';
import {
  applyThemeToDocument,
  cloneSettings,
  settingsAreSame,
} from '~/composables/user-settings/runtime';
import { subscribeToMeSettingsRealtime } from '~/composables/user-settings/realtime';
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
  const {
    user,
    token,
    isAuthenticated,
    refreshUser,
    updateSettings: updateRemoteSettings,
  } = useAuth();
  const { reportRealtimeEvent } = useRealtimeObservability();

  const settings = useState<UserSettings>('user_settings', createDefaultSettings);

  const initialized = useState<boolean>('user_settings_initialized', () => false);
  const realtimeWatcherReady = useState<boolean>(
    'user_settings_realtime_watcher_ready',
    () => false
  );
  const fallbackEventsReady = useState<boolean>('user_settings_fallback_events_ready', () => false);
  const remoteSettingsWatcherReady = useState<boolean>(
    'user_settings_remote_settings_watcher_ready',
    () => false
  );
  const fallbackPollingEnabled = useState<boolean>(
    'user_settings_fallback_polling_enabled',
    () => false
  );
  let stopRealtimeSubscription: (() => void) | null = null;
  let realtimeRetryTimer: ReturnType<typeof setTimeout> | null = null;
  let syncTimer: ReturnType<typeof setTimeout> | null = null;

  const theme = computed(() => settings.value.theme);
  const isDark = computed(() => theme.value === 'dark');

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
      await updateRemoteSettings(payload as unknown as Record<string, unknown>);
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

  const fallbackPolling = createSettingsFallbackPollingController({
    baseIntervalMs: SETTINGS_FALLBACK_POLL_INTERVAL_MS,
    maxBackoffMs: SETTINGS_FALLBACK_POLL_MAX_BACKOFF_MS,
    isPageVisible: () => document.visibilityState === 'visible',
    refresh: async () => {
      if (!isAuthenticated.value || !token.value) {
        return;
      }

      try {
        const refreshedUser = await refreshUser();
        applyRemoteSettings((refreshedUser?.settings ?? null) as Partial<UserSettings> | null);
      } catch {
        // backoff is handled by polling controller
      }
    },
  });

  const setFallbackPollingEnabled = (enabled: boolean) => {
    if (fallbackPollingEnabled.value === enabled) {
      return;
    }

    fallbackPollingEnabled.value = enabled;
    if (enabled) {
      void reportRealtimeEvent('settings_realtime_fallback_enabled', 'ok', 'info', {
        channel: 'me-settings',
        user_id: String(user.value?.id ?? ''),
      });
      fallbackPolling.start();
      return;
    }

    void reportRealtimeEvent('settings_realtime_fallback_disabled', 'ok', 'info', {
      channel: 'me-settings',
      user_id: String(user.value?.id ?? ''),
    });
    fallbackPolling.stop();
  };

  const clearRealtimeRetryTimer = () => {
    if (realtimeRetryTimer === null) {
      return;
    }

    clearTimeout(realtimeRetryTimer);
    realtimeRetryTimer = null;
  };

  const connectRealtimeSync = async () => {
    if (!isAuthenticated.value || !token.value || !user.value?.id || stopRealtimeSubscription) {
      return;
    }

    const realtimeEcho = useRealtimeEcho();
    if (!realtimeEcho) {
      setFallbackPollingEnabled(true);
      scheduleRealtimeRetry();
      return;
    }

    const echo = await realtimeEcho.connect();
    if (!echo) {
      void reportRealtimeEvent('websocket_subscribe_error', 'error', 'warning', {
        channel: 'me-settings',
        user_id: String(user.value?.id ?? ''),
      });
      setFallbackPollingEnabled(true);
      scheduleRealtimeRetry();
      return;
    }

    clearRealtimeRetryTimer();
    const userId = String(user.value.id);

    stopRealtimeSubscription = subscribeToMeSettingsRealtime(
      echo,
      userId,
      (payload) => {
        if (payload.user_id !== userId) {
          return;
        }

        applyRemoteSettings(payload.settings);
      },
      {
        onSubscribed: () => {
          void reportRealtimeEvent('websocket_subscribe_ok', 'ok', 'info', {
            channel: 'me-settings',
            user_id: userId,
          });
          clearRealtimeRetryTimer();
          setFallbackPollingEnabled(false);
        },
        onError: () => {
          stopRealtimeSubscription?.();
          stopRealtimeSubscription = null;
          void reportRealtimeEvent('websocket_subscribe_error', 'error', 'warning', {
            channel: 'me-settings',
            user_id: userId,
          });
          setFallbackPollingEnabled(true);
          scheduleRealtimeRetry();
        },
      }
    );
  };

  const scheduleRealtimeRetry = () => {
    if (
      realtimeRetryTimer !== null ||
      stopRealtimeSubscription !== null ||
      !isAuthenticated.value ||
      !token.value ||
      !user.value?.id
    ) {
      return;
    }

    realtimeRetryTimer = setTimeout(() => {
      realtimeRetryTimer = null;
      void connectRealtimeSync();
    }, 2_000);
  };

  const startRemoteSync = () => {
    void connectRealtimeSync();
  };

  const stopRemoteSync = () => {
    clearRealtimeRetryTimer();
    stopRealtimeSubscription?.();
    stopRealtimeSubscription = null;
    setFallbackPollingEnabled(false);
  };

  if (process.client && !realtimeWatcherReady.value) {
    watch(
      [isAuthenticated, token, () => user.value?.id],
      ([authenticated, nextToken, userId]) => {
        if (authenticated && nextToken && userId) {
          startRemoteSync();
          return;
        }

        stopRemoteSync();
      },
      { immediate: true }
    );

    const realtimeEchoState = useRealtimeEchoState();
    if (realtimeEchoState) {
      watch(realtimeEchoState, (state) => {
        if (!isAuthenticated.value || !token.value || !user.value?.id) {
          stopRemoteSync();
          return;
        }

        if (state === 'connected') {
          if (!stopRealtimeSubscription) {
            void connectRealtimeSync();
          }
          return;
        }

        if (state === 'disconnected' || state === 'unavailable' || state === 'error') {
          stopRealtimeSubscription?.();
          stopRealtimeSubscription = null;
          setFallbackPollingEnabled(true);
          scheduleRealtimeRetry();
        }
      });
    }

    realtimeWatcherReady.value = true;
  }

  if (process.client && !fallbackEventsReady.value) {
    const handleVisibilityChange = () => {
      void fallbackPolling.handleVisibilityChange();
    };

    const handleWindowFocus = () => {
      void fallbackPolling.handleWindowFocus();
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('focus', handleWindowFocus);
    fallbackEventsReady.value = true;
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
