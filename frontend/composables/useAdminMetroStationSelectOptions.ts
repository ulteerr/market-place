import type { AdminMetroLine } from '~/composables/useAdminMetroLines';

type SelectOption = {
  value: string;
  label: string;
  color?: string | null;
};

const buildMetroLineLabel = (line: Pick<AdminMetroLine, 'name' | 'id' | 'line_id'>): string => {
  const lineIdPart = line.line_id ? ` (${line.line_id})` : '';
  return `${line.name}${lineIdPart} - ${line.id}`;
};

const cityLabel = (cityId: string): string => cityId;

export const useAdminMetroStationSelectOptions = () => {
  const metroLinesApi = useAdminMetroLines();

  const metroLineOptions = ref<SelectOption[]>([]);
  const cityOptions = ref<SelectOption[]>([]);

  const upsertOption = (target: SelectOption[], option: SelectOption): SelectOption[] => {
    const next = [...target];
    const index = next.findIndex((item) => item.value === option.value);

    if (index >= 0) {
      next[index] = option;
      return next;
    }

    next.push(option);
    return next;
  };

  const hydrateFromLines = (lines: AdminMetroLine[]) => {
    let nextMetroLineOptions = [...metroLineOptions.value];
    let nextCityOptions = [...cityOptions.value];

    for (const line of lines) {
      nextMetroLineOptions = upsertOption(nextMetroLineOptions, {
        value: line.id,
        label: buildMetroLineLabel(line),
        color: line.color ?? null,
      });

      nextCityOptions = upsertOption(nextCityOptions, {
        value: line.city_id,
        label: cityLabel(line.city_id),
      });
    }

    metroLineOptions.value = nextMetroLineOptions.sort((left, right) =>
      left.label.localeCompare(right.label, 'ru')
    );
    cityOptions.value = nextCityOptions.sort((left, right) =>
      left.label.localeCompare(right.label, 'ru')
    );
  };

  const loadOptions = async (search: string) => {
    const response = await metroLinesApi.list({
      per_page: 50,
      search: search.trim() || undefined,
      sort_by: 'name',
      sort_dir: 'asc',
    });

    hydrateFromLines(response.data);
  };

  const ensureSelectedMetroLineOption = async (metroLineId: string | null | undefined) => {
    const value = String(metroLineId || '').trim();
    if (!value) {
      return;
    }

    if (metroLineOptions.value.some((option) => option.value === value)) {
      return;
    }

    try {
      const line = await metroLinesApi.show(value);
      hydrateFromLines([line]);
    } catch {
      metroLineOptions.value = upsertOption(metroLineOptions.value, {
        value,
        label: value,
      });
    }
  };

  const ensureSelectedCityOption = async (cityId: string | null | undefined) => {
    const value = String(cityId || '').trim();
    if (!value) {
      return;
    }

    if (cityOptions.value.some((option) => option.value === value)) {
      return;
    }

    cityOptions.value = upsertOption(cityOptions.value, {
      value,
      label: cityLabel(value),
    }).sort((left, right) => left.label.localeCompare(right.label, 'ru'));
  };

  const onMetroLineSearch = async (query: string) => {
    await loadOptions(query);
  };

  const onCitySearch = async (query: string) => {
    await loadOptions(query);
  };

  return {
    metroLineOptions,
    cityOptions,
    loadOptions,
    onMetroLineSearch,
    onCitySearch,
    ensureSelectedMetroLineOption,
    ensureSelectedCityOption,
  };
};
