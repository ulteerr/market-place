import type { WatchSource } from 'vue';

export interface AsyncSelectOption {
  value: string;
  label: string;
  color?: string | null;
}

interface UseDebouncedSearchOptions {
  delay?: number;
  skipInitial?: boolean;
}

export const sortAsyncSelectOptions = <T extends AsyncSelectOption>(options: T[]): T[] => {
  return [...options].sort((left, right) => left.label.localeCompare(right.label, 'ru'));
};

export const upsertAsyncSelectOptions = <T extends AsyncSelectOption>(
  current: T[],
  incoming: T[]
): T[] => {
  const next = [...current];

  for (const option of incoming) {
    const index = next.findIndex((item) => item.value === option.value);
    if (index >= 0) {
      next[index] = option;
      continue;
    }

    next.push(option);
  }

  return sortAsyncSelectOptions(next);
};

export const useAsyncSelectOptionCache = <T extends AsyncSelectOption>() => {
  const cache = new Map<string, T>();

  const putMany = (options: T[]) => {
    for (const option of options) {
      cache.set(option.value, option);
    }
  };

  const get = (value: string): T | undefined => cache.get(value);
  const values = (): T[] => Array.from(cache.values());

  return {
    putMany,
    get,
    values,
  };
};

export function useDebouncedSearch(debounceMs?: number): {
  schedule: (callback: () => void) => void;
  clear: () => void;
};
export function useDebouncedSearch<T>(
  source: WatchSource<T> | WatchSource<T>[],
  callback: (value: T, oldValue: T | undefined) => void | Promise<void>,
  options?: UseDebouncedSearchOptions
): void;
export function useDebouncedSearch<T>(
  sourceOrDebounce: number | WatchSource<T> | WatchSource<T>[] = 250,
  callback?: (value: T, oldValue: T | undefined) => void | Promise<void>,
  options: UseDebouncedSearchOptions = {}
) {
  if (typeof sourceOrDebounce === 'number') {
    let timer: ReturnType<typeof setTimeout> | null = null;
    const debounceMs = sourceOrDebounce;

    const schedule = (scheduleCallback: () => void) => {
      if (timer) {
        clearTimeout(timer);
      }

      timer = setTimeout(() => {
        scheduleCallback();
        timer = null;
      }, debounceMs);
    };

    const clear = () => {
      if (!timer) {
        return;
      }

      clearTimeout(timer);
      timer = null;
    };

    return {
      schedule,
      clear,
    };
  }

  const delay = options.delay ?? 300;
  const skipInitial = options.skipInitial ?? false;
  const ready = ref(!skipInitial);
  const source = sourceOrDebounce as WatchSource<T> | WatchSource<T>[];
  const debouncedCallback = callback;
  let timer: ReturnType<typeof setTimeout> | null = null;

  watch(source, (value, oldValue) => {
    if (!ready.value || !debouncedCallback) {
      return;
    }

    if (timer) {
      clearTimeout(timer);
    }

    timer = setTimeout(() => {
      void debouncedCallback(value as T, oldValue as T | undefined);
    }, delay);
  });

  onMounted(() => {
    ready.value = true;
  });

  onBeforeUnmount(() => {
    if (timer) {
      clearTimeout(timer);
      timer = null;
    }
  });
}

export const useIndexedDebouncedSearch = (debounceMs = 250) => {
  const timers = new Map<number, ReturnType<typeof setTimeout>>();

  const schedule = (index: number, callback: () => void) => {
    const timer = timers.get(index);
    if (timer) {
      clearTimeout(timer);
    }

    timers.set(
      index,
      setTimeout(() => {
        callback();
        timers.delete(index);
      }, debounceMs)
    );
  };

  const clearAll = () => {
    for (const timer of timers.values()) {
      clearTimeout(timer);
    }
    timers.clear();
  };

  return {
    schedule,
    clearAll,
  };
};
