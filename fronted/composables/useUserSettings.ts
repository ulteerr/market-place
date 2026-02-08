type ThemeMode = 'light' | 'dark'
export type AdminCrudContentMode = 'table' | 'table-cards' | 'cards'

interface AdminCrudPreference {
  contentMode?: AdminCrudContentMode
  tableOnDesktop?: boolean
}

interface UserSettings {
  theme: ThemeMode
  admin_crud_preferences: Record<string, AdminCrudPreference>
}

const STORAGE_KEY = 'user_settings'
const LEGACY_CRUD_STORAGE_KEY = 'admin_crud_preferences'
const DEFAULT_THEME: ThemeMode = 'light'
const SETTINGS_STREAM_PATH = '/api/me/settings/stream'
const SETTINGS_STREAM_RECONNECT_DELAY = 1500

let settingsStreamController: AbortController | null = null
let settingsStreamPromise: Promise<void> | null = null
let settingsStreamEnabled = false

const isThemeMode = (value: unknown): value is ThemeMode => value === 'light' || value === 'dark'

const isContentMode = (value: unknown): value is AdminCrudContentMode => {
  return value === 'table' || value === 'table-cards' || value === 'cards'
}

const normalizeAdminCrudPreferences = (value: unknown): Record<string, AdminCrudPreference> => {
  if (typeof value !== 'object' || value === null) {
    return {}
  }

  const source = value as Record<string, unknown>
  const normalized: Record<string, AdminCrudPreference> = {}

  for (const [key, preference] of Object.entries(source)) {
    if (typeof preference !== 'object' || preference === null) {
      continue
    }

    const candidate = preference as Record<string, unknown>
    const next: AdminCrudPreference = {}

    if (isContentMode(candidate.contentMode)) {
      next.contentMode = candidate.contentMode
    }

    if (typeof candidate.tableOnDesktop === 'boolean') {
      next.tableOnDesktop = candidate.tableOnDesktop
    }

    if (Object.keys(next).length > 0) {
      normalized[key] = next
    }
  }

  return normalized
}

const getSystemTheme = (): ThemeMode => {
  if (!process.client || !window.matchMedia) {
    return DEFAULT_THEME
  }

  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

const applyThemeToDocument = (theme: ThemeMode) => {
  if (!process.client) {
    return
  }

  document.documentElement.setAttribute('data-theme', theme)
  document.documentElement.style.colorScheme = theme
}

const readStorageObject = (key: string): Record<string, unknown> | null => {
  if (!process.client) {
    return null
  }

  try {
    const raw = localStorage.getItem(key)

    if (!raw) {
      return null
    }

    const parsed = JSON.parse(raw) as unknown

    if (typeof parsed !== 'object' || parsed === null) {
      return null
    }

    return parsed as Record<string, unknown>
  } catch {
    return null
  }
}

const createStableString = (value: unknown): string => {
  if (Array.isArray(value)) {
    return `[${value.map((item) => createStableString(item)).join(',')}]`
  }

  if (typeof value === 'object' && value !== null) {
    const source = value as Record<string, unknown>
    const keys = Object.keys(source).sort()
    return `{${keys.map((key) => `${JSON.stringify(key)}:${createStableString(source[key])}`).join(',')}}`
  }

  return JSON.stringify(value)
}

const areSettingsEqual = (left: UserSettings, right: UserSettings): boolean => {
  return createStableString(left) === createStableString(right)
}

const sleep = (ms: number): Promise<void> => {
  return new Promise((resolve) => {
    setTimeout(resolve, ms)
  })
}

const buildApiUrl = (baseUrl: string, path: string): string => {
  try {
    return new URL(path, baseUrl).toString()
  } catch {
    return path
  }
}

const mergeSettings = (
  remoteSettings: Partial<UserSettings> | null,
  localSettings: Partial<UserSettings> | null,
  legacyCrudSettings: Record<string, AdminCrudPreference>
): UserSettings => {
  const remoteTheme = isThemeMode(remoteSettings?.theme) ? remoteSettings.theme : undefined
  const localTheme = isThemeMode(localSettings?.theme) ? localSettings.theme : undefined

  return {
    theme: localTheme ?? remoteTheme ?? getSystemTheme(),
    admin_crud_preferences: {
      ...normalizeAdminCrudPreferences(remoteSettings?.admin_crud_preferences),
      ...legacyCrudSettings,
      ...normalizeAdminCrudPreferences(localSettings?.admin_crud_preferences)
    }
  }
}

export const useUserSettings = () => {
  const { user, token, isAuthenticated, updateSettings: updateRemoteSettings } = useAuth()
  const config = useRuntimeConfig()

  const settings = useState<UserSettings>('user_settings', () => ({
    theme: DEFAULT_THEME,
    admin_crud_preferences: {}
  }))

  const initialized = useState<boolean>('user_settings_initialized', () => false)
  const storageListenerReady = useState<boolean>('user_settings_storage_listener_ready', () => false)
  const streamWatcherReady = useState<boolean>('user_settings_stream_watcher_ready', () => false)
  let syncTimer: ReturnType<typeof setTimeout> | null = null

  const theme = computed(() => settings.value.theme)
  const isDark = computed(() => theme.value === 'dark')

  const persist = () => {
    if (!process.client) {
      return
    }

    localStorage.setItem(STORAGE_KEY, JSON.stringify(settings.value))
  }

  const queueSyncToServer = () => {
    if (!process.client || !isAuthenticated.value) {
      return
    }

    if (syncTimer) {
      clearTimeout(syncTimer)
    }

    syncTimer = setTimeout(async () => {
      syncTimer = null

      try {
        await updateRemoteSettings({ ...settings.value })
      } catch {
        // keep local settings and retry on next update
      }
    }, 350)
  }

  const applySettings = (nextSettings: UserSettings, syncRemote = true) => {
    settings.value = {
      theme: nextSettings.theme,
      admin_crud_preferences: { ...nextSettings.admin_crud_preferences }
    }

    applyThemeToDocument(nextSettings.theme)
    persist()

    if (syncRemote) {
      queueSyncToServer()
    }
  }

  const applyRemoteSettings = (remote: unknown) => {
    const payload = (remote ?? {}) as Partial<UserSettings>
    const normalized: UserSettings = {
      theme: isThemeMode(payload.theme) ? payload.theme : settings.value.theme,
      admin_crud_preferences: {
        ...settings.value.admin_crud_preferences,
        ...normalizeAdminCrudPreferences(payload.admin_crud_preferences)
      }
    }

    applySettings(normalized, false)

    if (user.value) {
      user.value = {
        ...user.value,
        settings: normalized
      }
    }
  }

  const updateSettings = (patch: Partial<UserSettings>, syncRemote = true) => {
    const nextTheme = isThemeMode(patch.theme) ? patch.theme : settings.value.theme

    const nextCrud = patch.admin_crud_preferences
      ? {
          ...settings.value.admin_crud_preferences,
          ...normalizeAdminCrudPreferences(patch.admin_crud_preferences)
        }
      : settings.value.admin_crud_preferences

    applySettings(
      {
        theme: nextTheme,
        admin_crud_preferences: nextCrud
      },
      syncRemote
    )
  }

  const setTheme = (nextTheme: ThemeMode) => {
    updateSettings({ theme: nextTheme })
  }

  const toggleTheme = () => {
    setTheme(isDark.value ? 'light' : 'dark')
  }

  const syncFromStorage = () => {
    const localSettings = readStorageObject(STORAGE_KEY)

    if (!localSettings) {
      return
    }

    updateSettings(
      {
        theme: localSettings.theme as ThemeMode,
        admin_crud_preferences: normalizeAdminCrudPreferences(localSettings.admin_crud_preferences)
      },
      false
    )
  }

  const initSettings = () => {
    if (!process.client || initialized.value) {
      return
    }

    const localSettings = readStorageObject(STORAGE_KEY) as Partial<UserSettings> | null
    const remoteSettings = (user.value?.settings ?? null) as Partial<UserSettings> | null

    const legacyRaw = readStorageObject(LEGACY_CRUD_STORAGE_KEY)
    const legacyCrudSettings = normalizeAdminCrudPreferences(legacyRaw)

    const mergedSettings = mergeSettings(remoteSettings, localSettings, legacyCrudSettings)
    const normalizedRemote = mergeSettings(remoteSettings, null, {})

    applySettings(mergedSettings, false)
    initialized.value = true

    if (!areSettingsEqual(mergedSettings, normalizedRemote)) {
      queueSyncToServer()
    }
  }

  if (process.client && !initialized.value) {
    initSettings()
  }

  if (process.client && !storageListenerReady.value) {
    window.addEventListener('storage', (event) => {
      if (event.key !== STORAGE_KEY) {
        return
      }

      syncFromStorage()
    })

    storageListenerReady.value = true
  }

  const processStreamMessage = (rawMessage: string) => {
    const lines = rawMessage
      .split('\n')
      .map((line) => line.trimEnd())
      .filter((line) => line.length > 0)

    if (lines.length === 0) {
      return
    }

    let eventName = 'message'
    const dataChunks: string[] = []

    for (const line of lines) {
      if (line.startsWith('event:')) {
        eventName = line.slice(6).trim()
        continue
      }

      if (line.startsWith('data:')) {
        dataChunks.push(line.slice(5).trim())
      }
    }

    if (eventName !== 'settings' || dataChunks.length === 0) {
      return
    }

    try {
      const payload = JSON.parse(dataChunks.join('\n')) as {
        settings?: Partial<UserSettings>
      }

      if (payload.settings) {
        applyRemoteSettings(payload.settings)
      }
    } catch {
      // ignore malformed stream payload and keep the connection alive
    }
  }

  const connectSettingsStream = async (): Promise<void> => {
    const streamUrl = buildApiUrl(config.public.apiBase, SETTINGS_STREAM_PATH)

    while (settingsStreamEnabled && isAuthenticated.value && token.value) {
      settingsStreamController = new AbortController()

      try {
        const response = await fetch(streamUrl, {
          method: 'GET',
          credentials: 'include',
          cache: 'no-store',
          headers: {
            Accept: 'text/event-stream',
            Authorization: `Bearer ${token.value}`
          },
          signal: settingsStreamController.signal
        })

        if (!response.ok || !response.body) {
          throw new Error('Settings stream is unavailable')
        }

        const reader = response.body.getReader()
        const decoder = new TextDecoder()
        let buffer = ''

        while (settingsStreamEnabled && isAuthenticated.value) {
          const { done, value } = await reader.read()

          if (done) {
            break
          }

          buffer += decoder.decode(value, { stream: true })
          const messages = buffer.split('\n\n')
          buffer = messages.pop() ?? ''

          for (const message of messages) {
            processStreamMessage(message)
          }
        }
      } catch {
        // reconnect below
      } finally {
        if (settingsStreamController) {
          settingsStreamController.abort()
          settingsStreamController = null
        }
      }

      if (settingsStreamEnabled && isAuthenticated.value && token.value) {
        await sleep(SETTINGS_STREAM_RECONNECT_DELAY)
      }
    }
  }

  const startRemoteSync = () => {
    if (!process.client || !isAuthenticated.value || !token.value || settingsStreamPromise) {
      return
    }

    settingsStreamEnabled = true
    settingsStreamPromise = connectSettingsStream().finally(() => {
      settingsStreamPromise = null
    })
  }

  const stopRemoteSync = () => {
    settingsStreamEnabled = false

    if (settingsStreamController) {
      settingsStreamController.abort()
      settingsStreamController = null
    }
  }

  if (process.client && !streamWatcherReady.value) {
    watch(
      [isAuthenticated, token],
      ([authenticated, nextToken]) => {
        if (authenticated && nextToken) {
          startRemoteSync()
          return
        }

        stopRemoteSync()
      },
      { immediate: true }
    )

    streamWatcherReady.value = true
  }

  return {
    settings,
    theme,
    isDark,
    initSettings,
    setTheme,
    toggleTheme,
    updateSettings,
    startRemoteSync,
    stopRemoteSync
  }
}
