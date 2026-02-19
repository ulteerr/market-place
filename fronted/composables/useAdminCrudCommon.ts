import type { Ref } from 'vue';

export interface PaginationPayload<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface IndexResponse<T> {
  status: string;
  data: PaginationPayload<T>;
}

export interface ApiErrorPayload {
  message?: string;
  errors?: Record<string, string[]>;
}

export const getApiErrorPayload = (error: unknown): ApiErrorPayload => {
  if (typeof error !== 'object' || error === null) {
    return {};
  }

  return ((error as { data?: ApiErrorPayload }).data ?? {}) as ApiErrorPayload;
};

export const getApiErrorMessage = (error: unknown, fallback: string): string => {
  return getApiErrorPayload(error).message || fallback;
};

export const getFieldError = (
  errors: Record<string, string[]> | undefined,
  field: string
): string => {
  return errors?.[field]?.[0] ?? '';
};

export type SortDirection = 'asc' | 'desc';
export type PaginationItem = number | '...';

type SortValue = string | number | boolean | null | undefined;

type SortGetter<T> = (item: T) => SortValue;

const normalizeSortValue = (value: SortValue): string | number => {
  if (typeof value === 'number') {
    return value;
  }

  if (typeof value === 'boolean') {
    return value ? 1 : 0;
  }

  return String(value ?? '').toLowerCase();
};

export const useClientSort = <T>(
  items: Ref<T[]>,
  sortGetters: Record<string, SortGetter<T>>,
  defaultSortBy: string
) => {
  const sortBy = ref(defaultSortBy);
  const sortDirection = ref<SortDirection>('asc');

  const sortedItems = computed(() => {
    const getter = sortGetters[sortBy.value];

    if (!getter) {
      return [...items.value];
    }

    return [...items.value].sort((a, b) => {
      const left = normalizeSortValue(getter(a));
      const right = normalizeSortValue(getter(b));

      if (left < right) {
        return sortDirection.value === 'asc' ? -1 : 1;
      }

      if (left > right) {
        return sortDirection.value === 'asc' ? 1 : -1;
      }

      return 0;
    });
  });

  const toggleSort = (column: string) => {
    if (sortBy.value === column) {
      sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
      return;
    }

    sortBy.value = column;
    sortDirection.value = 'asc';
  };

  return {
    sortBy,
    sortDirection,
    sortedItems,
    toggleSort,
  };
};

export const buildPaginationItems = (
  currentPage: number,
  lastPage: number,
  maxVisiblePages = 5
): PaginationItem[] => {
  if (lastPage <= maxVisiblePages) {
    return Array.from({ length: lastPage }, (_, index) => index + 1);
  }

  const pages = new Set<number>([1, lastPage, currentPage]);

  if (currentPage <= 3) {
    pages.add(2);
    pages.add(3);
    pages.add(4);
  } else if (currentPage >= lastPage - 2) {
    pages.add(lastPage - 1);
    pages.add(lastPage - 2);
    pages.add(lastPage - 3);
  } else {
    pages.add(currentPage - 1);
    pages.add(currentPage + 1);
  }

  const normalizedPages = [...pages]
    .filter((page) => page >= 1 && page <= lastPage)
    .sort((left, right) => left - right);

  const result: PaginationItem[] = [];

  for (let index = 0; index < normalizedPages.length; index += 1) {
    const page = normalizedPages[index] ?? 1;
    const previous = normalizedPages[index - 1];

    if (index > 0 && previous !== undefined && page - previous > 1) {
      result.push('...');
    }

    result.push(page);
  }

  return result;
};
