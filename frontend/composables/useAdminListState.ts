import type { SortDirection } from '~/composables/useAdminCrudCommon';

interface UseAdminListStateOptions {
  defaultSortBy: string;
  defaultPerPage?: number;
  perPageOptions?: number[];
}

interface ListQueryState {
  page: number;
  per_page: number;
  search?: string;
  sort_by: string;
  sort_dir: SortDirection;
}

export const useAdminListState = (options: UseAdminListStateOptions) => {
  const route = useRoute();
  const router = useRouter();

  const perPageOptions = options.perPageOptions ?? [10, 20, 50, 100];
  const defaultPerPage = options.defaultPerPage ?? perPageOptions[0] ?? 10;

  const perPage = ref(defaultPerPage);
  const searchInput = ref('');
  const search = ref('');
  const sortBy = ref(options.defaultSortBy);
  const sortDir = ref<SortDirection>('asc');

  const parsePositiveInt = (value: unknown, fallback: number): number => {
    const parsed = Number(value);

    if (!Number.isFinite(parsed) || parsed < 1) {
      return fallback;
    }

    return Math.floor(parsed);
  };

  const normalizeSortDir = (value: unknown): SortDirection => {
    return value === 'desc' ? 'desc' : 'asc';
  };

  const readStateFromQuery = (): number => {
    const nextPage = parsePositiveInt(route.query.page, 1);
    const nextPerPage = parsePositiveInt(route.query.per_page, defaultPerPage);
    const nextSearch = String(route.query.search ?? '').trim();
    const nextSortBy = String(route.query.sort_by ?? options.defaultSortBy);
    const nextSortDir = normalizeSortDir(route.query.sort_dir);

    perPage.value = perPageOptions.includes(nextPerPage) ? nextPerPage : defaultPerPage;
    searchInput.value = nextSearch;
    search.value = nextSearch;
    sortBy.value = nextSortBy;
    sortDir.value = nextSortDir;

    return nextPage;
  };

  const createListQuery = (page: number): ListQueryState => {
    return {
      page,
      per_page: perPage.value,
      search: search.value || undefined,
      sort_by: sortBy.value,
      sort_dir: sortDir.value,
    };
  };

  const syncQuery = async (page: number) => {
    await router.replace({
      query: {
        ...route.query,
        page: page > 1 ? String(page) : undefined,
        per_page: String(perPage.value),
        sort_by: sortBy.value,
        sort_dir: sortDir.value,
        search: search.value || undefined,
      },
    });
  };

  const sortMark = (column: string): string => {
    if (sortBy.value !== column) {
      return '';
    }

    return sortDir.value === 'asc' ? '↑' : '↓';
  };

  const toggleSort = (column: string): number => {
    if (sortBy.value === column) {
      sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortBy.value = column;
      sortDir.value = 'asc';
    }

    return 1;
  };

  const applySearch = (): number => {
    search.value = searchInput.value.trim();
    return 1;
  };

  const resetFilters = (): number => {
    searchInput.value = '';
    search.value = '';
    perPage.value = defaultPerPage;
    sortBy.value = options.defaultSortBy;
    sortDir.value = 'asc';
    return 1;
  };

  const onPerPageChange = (): number => {
    return 1;
  };

  return {
    perPage,
    perPageOptions,
    searchInput,
    search,
    sortBy,
    sortDir,
    readStateFromQuery,
    createListQuery,
    syncQuery,
    sortMark,
    toggleSort,
    applySearch,
    resetFilters,
    onPerPageChange,
  };
};
