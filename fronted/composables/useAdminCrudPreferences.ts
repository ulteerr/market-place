import type { AdminCrudContentMode } from '~/composables/useUserSettings'

interface AdminCrudViewPreference {
  contentMode: AdminCrudContentMode
  tableOnDesktop: boolean
}

type AdminCrudPreferencesState = Record<string, Partial<AdminCrudViewPreference>>

const isContentMode = (value: unknown): value is AdminCrudContentMode => {
  return value === 'table' || value === 'table-cards' || value === 'cards'
}

export const useAdminCrudPreferences = () => {
  const { settings, updateSettings, initSettings } = useUserSettings()

  const preferences = computed<AdminCrudPreferencesState>(() => {
    return settings.value.admin_crud_preferences ?? {}
  })

  const getViewPreference = (key: string): Partial<AdminCrudViewPreference> => {
    initSettings()
    return preferences.value[key] ?? {}
  }

  const updateViewPreference = (key: string, patch: Partial<AdminCrudViewPreference>) => {
    initSettings()

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

    updateSettings({
      admin_crud_preferences: {
        ...preferences.value,
        [key]: next
      }
    })
  }

  return {
    preferences,
    init: initSettings,
    getViewPreference,
    updateViewPreference
  }
}
