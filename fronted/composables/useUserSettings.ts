type ThemeMode = 'light' | 'dark';
export type AdminCrudContentMode = 'table' | 'table-cards' | 'cards';

interface AdminCrudPreference {
  contentMode?: AdminCrudContentMode;
  tableOnDesktop?: boolean;
}

interface UserSettings {
  theme: ThemeMode;
  collapse_menu: boolean;
  admin_crud_preferences: Record<string, AdminCrudPreference>;
}

const DEFAULT_THEME: ThemeMode = 'light';
const DEFAULT_COLLAPSE_MENU = false;
const SETTINGS_STREAM_PATH = '/api/me/settings/stream';
const SETTINGS_STREAM_RECONNECT_DELAY = 1500;
const SETTINGS_SYNC_DEBOUNCE_MS = 900;

let settingsStreamController: AbortController | null = null;
let settingsStreamPromise: Promise<void> | null = null;
let settingsStreamEnabled = false;
let lastCollapseToggleAt = 0;
let syncInFlight = false;
let syncQueued = false;
let lastSyncedSettingsSnapshot = '';

const isThemeMode = (value: unknown): value is ThemeMode => value === 'light' || value === 'dark';

const isContentMode = (value: unknown): value is AdminCrudContentMode => {
  return value === 'table' || value === 'table-cards' || value === 'cards';
};

const normalizeAdminCrudPreferences = (value: unknown): Record<string, AdminCrudPreference> => {
  if (typeof value !== 'object' || value === null) {
    return {};
  }

  const source = value as Record<string, unknown>;
  const normalized: Record<string, AdminCrudPreference> = {};

  for (const [key, preference] of Object.entries(source)) {
    if (typeof preference !== 'object' || preference === null) {
      continue;
    }

    const candidate = preference as Record<string, unknown>;
    const next: AdminCrudPreference = {};

    if (isContentMode(candidate.contentMode)) {
      next.contentMode = candidate.contentMode;
    }

    if (typeof candidate.tableOnDesktop === 'boolean') {
      next.tableOnDesktop = candidate.tableOnDesktop;
    }

    if (Object.keys(next).length > 0) {
      normalized[key] = next;
    }
  }

  return normalized;
};

const resolveCollapseMenu = (value: unknown): boolean | undefined => {
  return typeof value === 'boolean' ? value : undefined;
};

const getSystemTheme = (): ThemeMode => {
  if (!process.client || !window.matchMedia) {
    return DEFAULT_THEME;
  }

  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const applyThemeToDocument = (theme: ThemeMode) => {
  if (!process.client) {
    return;
  }

  document.documentElement.setAttribute('data-theme', theme);
  document.documentElement.style.colorScheme = theme;
};

const sleep = (ms: number): Promise<void> => {
  return new Promise((resolve) => {
    setTimeout(resolve, ms);
  });
};

const buildApiUrl = (baseUrl: string, path: string): string => {
  try {
    return new URL(path, baseUrl).toString();
  } catch {
    return path;
  }
};

const mergeSettings = (remoteSettings: Partial<UserSettings> | null): UserSettings => ({
  theme: isThemeMode(remoteSettings?.theme) ? remoteSettings.theme : getSystemTheme(),
  collapse_menu: resolveCollapseMenu(remoteSettings?.collapse_menu) ?? DEFAULT_COLLAPSE_MENU,
  admin_crud_preferences: normalizeAdminCrudPreferences(remoteSettings?.admin_crud_preferences),
});

const settingsAreSame = (left: UserSettings, right: UserSettings): boolean => {
  return (
    left.theme === right.theme &&
    left.collapse_menu === right.collapse_menu &&
    JSON.stringify(left.admin_crud_preferences) === JSON.stringify(right.admin_crud_preferences)
  );
};

const cloneSettings = (value: UserSettings): UserSettings => ({
  theme: value.theme,
  collapse_menu: value.collapse_menu,
  admin_crud_preferences: { ...value.admin_crud_preferences },
});

export const useUserSettings = () => {
  const { user, token, isAuthenticated, updateSettings: updateRemoteSettings } = useAuth();
  const config = useRuntimeConfig();

  const settings = useState<UserSettings>('user_settings', () => ({
    theme: DEFAULT_THEME,
    collapse_menu: DEFAULT_COLLAPSE_MENU,
    admin_crud_preferences: {},
  }));

  const initialized = useState<boolean>('user_settings_initialized', () => false);
  const streamWatcherReady = useState<boolean>('user_settings_stream_watcher_ready', () => false);
  const remoteSettingsWatcherReady = useState<boolean>(
    'user_settings_remote_settings_watcher_ready',
    () => false
  );
  let syncTimer: ReturnType<typeof setTimeout> | null = null;

  const theme = computed(() => settings.value.theme);
  const isDark = computed(() => theme.value === 'dark');

  const persist = () => {
    // server is the source of truth for settings
  };

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
        queueSyncToServer();
      }
    }
  };

  const queueSyncToServer = () => {
    if (!process.client || !isAuthenticated.value) {
      return;
    }

    if (syncTimer) {
      clearTimeout(syncTimer);
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
        theme: nextSettings.theme,
        collapse_menu: nextSettings.collapse_menu,
        admin_crud_preferences: { ...nextSettings.admin_crud_preferences },
      };
    }

    // During SSR hydration values can already match, but theme still must be
    // applied to the real document on client.
    applyThemeToDocument(nextSettings.theme);
    persist();

    if (syncRemote && hasChanges) {
      queueSyncToServer();
    }
  };

  const applyRemoteSettings = (remote: unknown) => {
    const payload = (remote ?? {}) as Partial<UserSettings>;
    const normalized: UserSettings = {
      theme: isThemeMode(payload.theme) ? payload.theme : settings.value.theme,
      collapse_menu: resolveCollapseMenu(payload.collapse_menu) ?? settings.value.collapse_menu,
      admin_crud_preferences: {
        ...settings.value.admin_crud_preferences,
        ...normalizeAdminCrudPreferences(payload.admin_crud_preferences),
      },
    };

    applySettings(normalized, false);
    lastSyncedSettingsSnapshot = JSON.stringify(cloneSettings(normalized));
  };

  const applyServerSettings = (remote: Partial<UserSettings> | null) => {
    const normalized = mergeSettings(remote);
    applySettings(normalized, false);
    lastSyncedSettingsSnapshot = JSON.stringify(cloneSettings(normalized));
  };

  const updateSettings = (patch: Partial<UserSettings>, syncRemote = true) => {
    const nextTheme = isThemeMode(patch.theme) ? patch.theme : settings.value.theme;
    const nextCollapseMenu =
      resolveCollapseMenu(patch.collapse_menu) ?? settings.value.collapse_menu;

    const nextCrud = patch.admin_crud_preferences
      ? {
          ...settings.value.admin_crud_preferences,
          ...normalizeAdminCrudPreferences(patch.admin_crud_preferences),
        }
      : settings.value.admin_crud_preferences;

    applySettings(
      {
        theme: nextTheme,
        collapse_menu: nextCollapseMenu,
        admin_crud_preferences: nextCrud,
      },
      syncRemote
    );
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
    lastSyncedSettingsSnapshot = JSON.stringify(cloneSettings(mergedSettings));
    initialized.value = true;
  };

  if (!initialized.value) {
    initSettings();
  }

  const processStreamMessage = (rawMessage: string) => {
    const lines = rawMessage
      .split('\n')
      .map((line) => line.trimEnd())
      .filter((line) => line.length > 0);

    if (lines.length === 0) {
      return;
    }

    let eventName = 'message';
    const dataChunks: string[] = [];

    for (const line of lines) {
      if (line.startsWith('event:')) {
        eventName = line.slice(6).trim();
        continue;
      }

      if (line.startsWith('data:')) {
        dataChunks.push(line.slice(5).trim());
      }
    }

    if (eventName !== 'settings' || dataChunks.length === 0) {
      return;
    }

    try {
      const payload = JSON.parse(dataChunks.join('\n')) as {
        settings?: Partial<UserSettings>;
      };

      if (payload.settings) {
        applyRemoteSettings(payload.settings);
      }
    } catch {
      // ignore malformed stream payload and keep the connection alive
    }
  };

  const connectSettingsStream = async (): Promise<void> => {
    const streamUrl = buildApiUrl(config.public.apiBase, SETTINGS_STREAM_PATH);

    while (settingsStreamEnabled && isAuthenticated.value && token.value) {
      settingsStreamController = new AbortController();

      try {
        const response = await fetch(streamUrl, {
          method: 'GET',
          credentials: 'include',
          cache: 'no-store',
          headers: {
            Accept: 'text/event-stream',
            Authorization: `Bearer ${token.value}`,
          },
          signal: settingsStreamController.signal,
        });

        if (!response.ok || !response.body) {
          throw new Error('Settings stream is unavailable');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (settingsStreamEnabled && isAuthenticated.value) {
          const { done, value } = await reader.read();

          if (done) {
            break;
          }

          buffer += decoder.decode(value, { stream: true });
          const messages = buffer.split('\n\n');
          buffer = messages.pop() ?? '';

          for (const message of messages) {
            processStreamMessage(message);
          }
        }
      } catch {
        // reconnect below
      } finally {
        if (settingsStreamController) {
          settingsStreamController.abort();
          settingsStreamController = null;
        }
      }

      if (settingsStreamEnabled && isAuthenticated.value && token.value) {
        await sleep(SETTINGS_STREAM_RECONNECT_DELAY);
      }
    }
  };

  const startRemoteSync = () => {
    if (!process.client || !isAuthenticated.value || !token.value || settingsStreamPromise) {
      return;
    }

    settingsStreamEnabled = true;
    settingsStreamPromise = connectSettingsStream().finally(() => {
      settingsStreamPromise = null;
    });
  };

  const stopRemoteSync = () => {
    settingsStreamEnabled = false;

    if (settingsStreamController) {
      settingsStreamController.abort();
      settingsStreamController = null;
    }
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
