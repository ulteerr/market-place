import type { PaginationPayload } from '~/composables/useAdminCrudCommon';
import { buildPaginationItems, getApiErrorMessage } from '~/composables/useAdminCrudCommon';
import type { SortDirection } from '~/composables/useAdminCrudCommon';
import type { AdminCrudContentMode } from '~/composables/useUserSettings';

interface ListQueryParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}

interface UseAdminCrudIndexOptions<T, P extends ListQueryParams = ListQueryParams> {
  settingsKey: string;
  useViewPreference?: boolean;
  defaultSortBy: string;
  defaultSortDir?: SortDirection;
  allowedSortBy?: string[];
  defaultPerPage?: number;
  perPageOptions?: number[];
  defaultContentMode?: AdminCrudContentMode;
  defaultTableOnDesktop?: boolean;
  listErrorMessage: string;
  deleteErrorMessage: string;
  list: (params: P, context?: { signal?: AbortSignal }) => Promise<PaginationPayload<T>>;
  remove: (id: string) => Promise<void>;
  getItemId: (item: T) => string;
  readCustomStateFromQuery?: () => void;
  buildCustomListQuery?: () => Partial<P>;
  buildCustomQuery?: () => Record<string, string | undefined>;
  resetCustomFilters?: () => void;
}

interface RemoveItemOptions {
  confirmMessage: string;
  canDelete?: boolean;
  confirmTitle?: string;
  confirmLabel?: string;
  cancelLabel?: string;
}

export const useAdminCrudIndex = <T, P extends ListQueryParams = ListQueryParams>(
  options: UseAdminCrudIndexOptions<T, P>
) => {
  const crudPreferences = useAdminCrudPreferences();
  const useViewPreference = options.useViewPreference !== false;

  const listState = useAdminListState({
    defaultSortBy: options.defaultSortBy,
    defaultSortDir: options.defaultSortDir,
    allowedSortBy: options.allowedSortBy,
    defaultPerPage: options.defaultPerPage,
    perPageOptions: options.perPageOptions,
  });

  const items = ref<T[]>([]);
  const loading = ref(false);
  const loadError = ref('');
  const deletingId = ref<string | null>(null);
  const removeConfirmOpen = ref(false);
  const removeConfirmTitle = ref('');
  const removeConfirmMessage = ref('');
  const removeConfirmLabel = ref('');
  const removeCancelLabel = ref('');
  const pendingRemoveItem = ref<T | null>(null);
  const latestRequestId = ref(0);
  let activeController: AbortController | null = null;

  const contentMode = ref<AdminCrudContentMode>(options.defaultContentMode ?? 'table');
  const tableOnDesktop = ref(options.defaultTableOnDesktop ?? true);
  const viewPreference = computed(() => {
    if (!useViewPreference) {
      return {};
    }

    return crudPreferences.preferences.value[options.settingsKey] ?? {};
  });

  const pagination = reactive<PaginationPayload<T>>({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 0,
    total: 0,
  });

  const showPagination = computed(() => pagination.total > pagination.per_page);
  const paginationItems = computed(() =>
    buildPaginationItems(pagination.current_page, pagination.last_page, 5)
  );

  const isAbortError = (error: unknown): boolean => {
    if (error instanceof DOMException) {
      return error.name === 'AbortError';
    }

    return (
      typeof error === 'object' &&
      error !== null &&
      'name' in error &&
      (error as { name?: string }).name === 'AbortError'
    );
  };

  const fetchItems = async (page = 1) => {
    const requestId = latestRequestId.value + 1;
    latestRequestId.value = requestId;

    if (activeController) {
      activeController.abort();
    }

    const controller = new AbortController();
    activeController = controller;

    loading.value = true;
    if (requestId === latestRequestId.value) {
      loadError.value = '';
    }

    try {
      const query = listState.createListQuery(page);
      const customListQuery = options.buildCustomListQuery?.() ?? {};
      const response = await options.list(
        {
          ...query,
          ...customListQuery,
          page: query.page > 1 ? query.page : undefined,
        } as P,
        { signal: controller.signal }
      );

      if (requestId !== latestRequestId.value) {
        return;
      }

      items.value = response.data;
      pagination.current_page = response.current_page;
      pagination.last_page = response.last_page;
      pagination.per_page = response.per_page;
      pagination.total = response.total;

      await listState.syncQuery(response.current_page, options.buildCustomQuery?.() ?? {});
    } catch (error) {
      if (requestId !== latestRequestId.value || isAbortError(error)) {
        return;
      }

      loadError.value = getApiErrorMessage(error, options.listErrorMessage);
    } finally {
      if (requestId === latestRequestId.value) {
        loading.value = false;
      }

      if (activeController === controller) {
        activeController = null;
      }
    }
  };

  const applyAndFetch = async (page = pagination.current_page || 1) => {
    await fetchItems(page);
  };

  const onToggleSort = (column: string) => {
    void applyAndFetch(listState.toggleSort(column));
  };

  const onApplySearch = () => {
    void applyAndFetch(listState.applySearch());
  };

  const onResetFilters = () => {
    options.resetCustomFilters?.();
    void applyAndFetch(listState.resetFilters());
  };

  const onUpdatePerPage = (value: number) => {
    listState.perPage.value = value;
    void applyAndFetch(listState.onPerPageChange());
  };

  const closeRemoveConfirm = () => {
    removeConfirmOpen.value = false;
    removeConfirmTitle.value = '';
    removeConfirmMessage.value = '';
    removeConfirmLabel.value = '';
    removeCancelLabel.value = '';
    pendingRemoveItem.value = null;
  };

  const removeItem = (item: T, removeOptions: RemoveItemOptions) => {
    if (removeOptions.canDelete === false) {
      return;
    }

    pendingRemoveItem.value = item;
    removeConfirmOpen.value = true;
    removeConfirmTitle.value = removeOptions.confirmTitle ?? '';
    removeConfirmMessage.value = removeOptions.confirmMessage;
    removeConfirmLabel.value = removeOptions.confirmLabel ?? '';
    removeCancelLabel.value = removeOptions.cancelLabel ?? '';
  };

  const confirmRemoveItem = async () => {
    if (!pendingRemoveItem.value) {
      return;
    }

    const item = pendingRemoveItem.value;
    const id = options.getItemId(item);
    deletingId.value = id;

    try {
      await options.remove(id);
      await fetchItems(pagination.current_page);

      if (!items.value.length && pagination.current_page > 1) {
        await fetchItems(pagination.current_page - 1);
      }
    } catch (error) {
      loadError.value = getApiErrorMessage(error, options.deleteErrorMessage);
    } finally {
      deletingId.value = null;
      closeRemoveConfirm();
    }
  };

  const cancelRemoveItem = () => {
    closeRemoveConfirm();
  };

  const applyPreference = (
    preference: Partial<{ contentMode: AdminCrudContentMode; tableOnDesktop: boolean }>
  ) => {
    if (preference.contentMode && preference.contentMode !== contentMode.value) {
      contentMode.value = preference.contentMode;
    }

    if (
      preference.tableOnDesktop !== undefined &&
      preference.tableOnDesktop !== tableOnDesktop.value
    ) {
      tableOnDesktop.value = preference.tableOnDesktop;
    }
  };

  onMounted(async () => {
    if (useViewPreference) {
      applyPreference(crudPreferences.getViewPreference(options.settingsKey));
    }

    const page = listState.readStateFromQuery();
    options.readCustomStateFromQuery?.();
    await applyAndFetch(page);
  });

  watch(viewPreference, (nextPreference) => {
    applyPreference(nextPreference);
  });

  watch([contentMode, tableOnDesktop], ([mode, desktop]) => {
    if (!useViewPreference) {
      return;
    }

    const currentPreference = viewPreference.value;
    const sameMode = currentPreference.contentMode === mode;
    const sameDesktop = currentPreference.tableOnDesktop === desktop;

    if (sameMode && sameDesktop) {
      return;
    }

    crudPreferences.updateViewPreference(options.settingsKey, {
      contentMode: mode,
      tableOnDesktop: desktop,
    });
  });

  onBeforeUnmount(() => {
    activeController?.abort();
    activeController = null;
  });

  return {
    listState,
    items,
    loading,
    loadError,
    deletingId,
    removeConfirmOpen,
    removeConfirmTitle,
    removeConfirmMessage,
    removeConfirmLabel,
    removeCancelLabel,
    contentMode,
    tableOnDesktop,
    pagination,
    showPagination,
    paginationItems,
    fetchItems,
    applyAndFetch,
    onToggleSort,
    onApplySearch,
    onResetFilters,
    onUpdatePerPage,
    removeItem,
    confirmRemoveItem,
    cancelRemoveItem,
  };
};
