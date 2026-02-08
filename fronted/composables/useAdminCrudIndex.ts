import type { PaginationPayload } from '~/composables/useAdminCrudCommon'
import { buildPaginationItems, getApiErrorMessage } from '~/composables/useAdminCrudCommon'
import type { AdminCrudContentMode } from '~/composables/useAdminCrudPreferences'

interface ListQueryParams {
  page?: number
  per_page?: number
  search?: string
  sort_by?: string
  sort_dir?: 'asc' | 'desc'
}

interface UseAdminCrudIndexOptions<T, P extends ListQueryParams = ListQueryParams> {
  settingsKey: string
  defaultSortBy: string
  defaultPerPage?: number
  perPageOptions?: number[]
  defaultContentMode?: AdminCrudContentMode
  defaultTableOnDesktop?: boolean
  listErrorMessage: string
  deleteErrorMessage: string
  list: (params: P) => Promise<PaginationPayload<T>>
  remove: (id: string) => Promise<void>
  getItemId: (item: T) => string
}

interface RemoveItemOptions {
  confirmMessage: string
  canDelete?: boolean
}

export const useAdminCrudIndex = <T, P extends ListQueryParams = ListQueryParams>(
  options: UseAdminCrudIndexOptions<T, P>
) => {
  const crudPreferences = useAdminCrudPreferences()

  const listState = useAdminListState({
    defaultSortBy: options.defaultSortBy,
    defaultPerPage: options.defaultPerPage,
    perPageOptions: options.perPageOptions
  })

  const items = ref<T[]>([])
  const loading = ref(false)
  const loadError = ref('')
  const deletingId = ref<string | null>(null)

  const contentMode = ref<AdminCrudContentMode>(options.defaultContentMode ?? 'table')
  const tableOnDesktop = ref(options.defaultTableOnDesktop ?? true)

  const pagination = reactive<PaginationPayload<T>>({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 0,
    total: 0
  })

  const showPagination = computed(() => pagination.total > pagination.per_page)
  const paginationItems = computed(() => buildPaginationItems(pagination.current_page, pagination.last_page, 5))

  const fetchItems = async (page = 1) => {
    loading.value = true
    loadError.value = ''

    try {
      const query = listState.createListQuery(page)
      const response = await options.list({
        ...query,
        page: query.page > 1 ? query.page : undefined
      } as P)

      items.value = response.data
      pagination.current_page = response.current_page
      pagination.last_page = response.last_page
      pagination.per_page = response.per_page
      pagination.total = response.total

      await listState.syncQuery(response.current_page)
    } catch (error) {
      loadError.value = getApiErrorMessage(error, options.listErrorMessage)
    } finally {
      loading.value = false
    }
  }

  const onToggleSort = (column: string) => {
    fetchItems(listState.toggleSort(column))
  }

  const onApplySearch = () => {
    fetchItems(listState.applySearch())
  }

  const onResetFilters = () => {
    fetchItems(listState.resetFilters())
  }

  const onUpdatePerPage = (value: number) => {
    listState.perPage.value = value
    fetchItems(listState.onPerPageChange())
  }

  const removeItem = async (item: T, removeOptions: RemoveItemOptions) => {
    if (removeOptions.canDelete === false) {
      return
    }

    const approved = window.confirm(removeOptions.confirmMessage)
    if (!approved) {
      return
    }

    const id = options.getItemId(item)
    deletingId.value = id

    try {
      await options.remove(id)
      await fetchItems(pagination.current_page)

      if (!items.value.length && pagination.current_page > 1) {
        await fetchItems(pagination.current_page - 1)
      }
    } catch (error) {
      loadError.value = getApiErrorMessage(error, options.deleteErrorMessage)
    } finally {
      deletingId.value = null
    }
  }

  onMounted(async () => {
    const viewPreference = crudPreferences.getViewPreference(options.settingsKey)

    if (viewPreference.contentMode) {
      contentMode.value = viewPreference.contentMode
    }

    if (viewPreference.tableOnDesktop !== undefined) {
      tableOnDesktop.value = viewPreference.tableOnDesktop
    }

    const page = listState.readStateFromQuery()
    await fetchItems(page)
  })

  watch([contentMode, tableOnDesktop], ([mode, desktop]) => {
    crudPreferences.updateViewPreference(options.settingsKey, {
      contentMode: mode,
      tableOnDesktop: desktop
    })
  })

  return {
    listState,
    items,
    loading,
    loadError,
    deletingId,
    contentMode,
    tableOnDesktop,
    pagination,
    showPagination,
    paginationItems,
    fetchItems,
    onToggleSort,
    onApplySearch,
    onResetFilters,
    onUpdatePerPage,
    removeItem
  }
}
