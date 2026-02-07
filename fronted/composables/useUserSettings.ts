type ThemeMode = 'light' | 'dark'

interface UserSettings {
  theme: ThemeMode
}

const STORAGE_KEY = 'user_settings'
const DEFAULT_THEME: ThemeMode = 'light'

const isThemeMode = (value: unknown): value is ThemeMode => value === 'light' || value === 'dark'

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

const readSettingsFromStorage = (): Partial<UserSettings> | null => {
  if (!process.client) {
    return null
  }

  try {
    const raw = localStorage.getItem(STORAGE_KEY)

    if (!raw) {
      return null
    }

    const parsed = JSON.parse(raw) as Partial<UserSettings>
    return parsed
  } catch {
    return null
  }
}

export const useUserSettings = () => {
  const settings = useState<UserSettings>('user_settings', () => ({
    theme: DEFAULT_THEME
  }))
  const initialized = useState<boolean>('user_settings_initialized', () => false)

  const theme = computed(() => settings.value.theme)
  const isDark = computed(() => theme.value === 'dark')

  const persist = () => {
    if (!process.client) {
      return
    }

    localStorage.setItem(STORAGE_KEY, JSON.stringify(settings.value))
  }

  const setTheme = (nextTheme: ThemeMode) => {
    settings.value = {
      ...settings.value,
      theme: nextTheme
    }

    applyThemeToDocument(nextTheme)
    persist()
  }

  const toggleTheme = () => {
    setTheme(isDark.value ? 'light' : 'dark')
  }

  const updateSettings = (patch: Partial<UserSettings>) => {
    if (patch.theme && !isThemeMode(patch.theme)) {
      return
    }

    const nextTheme = patch.theme ?? settings.value.theme
    setTheme(nextTheme)
  }

  const initSettings = () => {
    if (!process.client || initialized.value) {
      return
    }

    const stored = readSettingsFromStorage()
    const nextTheme = isThemeMode(stored?.theme) ? stored.theme : getSystemTheme()

    settings.value = {
      ...settings.value,
      theme: nextTheme
    }

    applyThemeToDocument(nextTheme)
    initialized.value = true
    persist()
  }

  if (process.client && !initialized.value) {
    initSettings()
  }

  return {
    settings,
    theme,
    isDark,
    initSettings,
    setTheme,
    toggleTheme,
    updateSettings
  }
}
