export type AdminCrudContentMode = 'table' | 'table-cards' | 'cards'

interface AdminCrudViewPreference {
  contentMode: AdminCrudContentMode
  tableOnDesktop: boolean
}

type AdminCrudPreferencesState = Record<string, Partial<AdminCrudViewPreference>>

const STORAGE_KEY = 'admin_crud_preferences'

const readFromStorage = (): AdminCrudPreferencesState | null => {
  if (!process.client) {
    return null
  }

  try {
    const raw = localStorage.getItem(STORAGE_KEY)

    if (!raw) {
      return null
    }

    const parsed = JSON.parse(raw) as AdminCrudPreferencesState
    return typeof parsed === 'object' && parsed !== null ? parsed : null
  } catch {
    return null
  }
}

const isContentMode = (value: unknown): value is AdminCrudContentMode => {
  return value === 'table' || value === 'table-cards' || value === 'cards'
}

export const useAdminCrudPreferences = () => {
  const preferences = useState<AdminCrudPreferencesState>('admin_crud_preferences', () => ({}))
  const initialized = useState<boolean>('admin_crud_preferences_initialized', () => false)
  const storageListenerReady = useState<boolean>('admin_crud_preferences_listener_ready', () => false)

  const persist = () => {
    if (!process.client) {
      return
    }

    localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences.value))
  }

  const init = () => {
    if (!process.client || initialized.value) {
      return
    }

    preferences.value = readFromStorage() ?? {}
    initialized.value = true
  }

  const syncFromStorage = () => {
    const stored = readFromStorage()

    if (stored) {
      preferences.value = stored
    }
  }

  const getViewPreference = (key: string): Partial<AdminCrudViewPreference> => {
    init()
    return preferences.value[key] ?? {}
  }

  const updateViewPreference = (key: string, patch: Partial<AdminCrudViewPreference>) => {
    init()

    const current = preferences.value[key] ?? {}
    const next: Partial<AdminCrudViewPreference> = {
      ...current
    }

    if (patch.contentMode !== undefined && isContentMode(patch.contentMode)) {
      next.contentMode = patch.contentMode
    }

    if (patch.tableOnDesktop !== undefined) {
      next.tableOnDesktop = Boolean(patch.tableOnDesktop)
    }

    preferences.value = {
      ...preferences.value,
      [key]: next
    }

    persist()
  }

  if (process.client && !initialized.value) {
    init()
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

  return {
    preferences,
    init,
    getViewPreference,
    updateViewPreference
  }
}
