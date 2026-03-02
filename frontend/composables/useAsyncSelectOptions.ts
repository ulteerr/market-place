export interface AsyncSelectOption {
  value: string;
  label: string;
  color?: string | null;
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

export const useDebouncedSearch = (debounceMs = 250) => {
  let timer: ReturnType<typeof setTimeout> | null = null;

  const schedule = (callback: () => void) => {
    if (timer) {
      clearTimeout(timer);
    }

    timer = setTimeout(() => {
      callback();
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
};

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
