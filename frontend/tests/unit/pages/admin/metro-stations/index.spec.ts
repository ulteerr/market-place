// @vitest-environment jsdom
import { mount } from '@vue/test-utils';
import {
  computed,
  defineComponent,
  nextTick,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
  watch,
} from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import MetroStationsIndexPage from '~/pages/admin/metro-stations/index.vue';

type MetroStationItem = {
  id: string;
  name: string;
  line_id: string | null;
  metro_line_id: string;
  city_id: string;
  geo_lat: number | null;
  geo_lon: number | null;
  source: string;
};

const makeCrudState = (items: MetroStationItem[]) => {
  const listState = {
    searchInput: ref(''),
    search: ref(''),
    perPage: ref(20),
    perPageOptions: [10, 20, 50],
    sortBy: ref('name'),
    sortMark: vi.fn(() => ''),
    applySearch: vi.fn(() => 4),
  };

  return {
    listState,
    items: ref(items),
    loading: ref(false),
    loadError: ref(''),
    deletingId: ref<string | null>(null),
    removeConfirmOpen: ref(false),
    removeConfirmMessage: ref(''),
    contentMode: ref<'table' | 'table-cards' | 'cards'>('table'),
    tableOnDesktop: ref(true),
    pagination: reactive({
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: items.length,
    }),
    showPagination: computed(() => false),
    paginationItems: computed(() => []),
    fetchItems: vi.fn(),
    onToggleSort: vi.fn(),
    onResetFilters: vi.fn(),
    onUpdatePerPage: vi.fn(),
    removeItem: vi.fn(),
    confirmRemoveItem: vi.fn(),
    cancelRemoveItem: vi.fn(),
  };
};

describe('admin metro stations index page', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.stubGlobal('ref', ref);
    vi.stubGlobal('reactive', reactive);
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('watch', watch);
    vi.stubGlobal('onMounted', onMounted);
    vi.stubGlobal('onBeforeUnmount', onBeforeUnmount);
  });

  it('passes translated section labels to entity index component', () => {
    const crudState = makeCrudState([]);

    vi.stubGlobal('definePageMeta', vi.fn());
    vi.stubGlobal('useI18n', () => ({
      t: (key: string) => key,
    }));
    vi.stubGlobal('useAdminMetroStations', () => ({
      list: vi.fn(),
      remove: vi.fn(),
    }));
    vi.stubGlobal('useAdminMetroLines', () => ({
      show: vi.fn(async () => ({ name: 'Line', color: '#000000' })),
    }));
    vi.stubGlobal('useAdminCrudIndex', () => crudState);

    const wrapper = mount(MetroStationsIndexPage, {
      global: {
        stubs: {
          NuxtLink: defineComponent({
            template: '<a><slot /></a>',
          }),
          AdminEntityIndex: defineComponent({
            props: ['title', 'subtitle', 'createLabel', 'searchPlaceholder'],
            template:
              '<section><p data-test="title">{{ title }}</p><p data-test="subtitle">{{ subtitle }}</p><p data-test="create">{{ createLabel }}</p><p data-test="search">{{ searchPlaceholder }}</p><slot name="table" /><slot name="cards" /></section>',
          }),
          AdminCrudActions: defineComponent({
            emits: ['delete'],
            template: '<button data-test="delete" @click="$emit(\'delete\')">delete</button>',
          }),
          UiModal: defineComponent({
            template: '<div />',
          }),
        },
      },
    });

    expect(wrapper.get('[data-test="title"]').text()).toBe('admin.metro.stations.index.title');
    expect(wrapper.get('[data-test="subtitle"]').text()).toBe(
      'admin.metro.stations.index.subtitle'
    );
    expect(wrapper.get('[data-test="create"]').text()).toBe(
      'admin.metro.stations.index.createLabel'
    );
    expect(wrapper.get('[data-test="search"]').text()).toBe(
      'admin.metro.stations.index.searchPlaceholder'
    );
  });

  it('builds localized delete confirmation for selected metro station', async () => {
    const item: MetroStationItem = {
      id: 'st-1',
      name: 'Sokol',
      line_id: 'L-2',
      metro_line_id: 'ml-2',
      city_id: 'msk',
      geo_lat: 55.8,
      geo_lon: 37.5,
      source: 'manual',
    };
    const crudState = makeCrudState([item]);

    vi.stubGlobal('definePageMeta', vi.fn());
    vi.stubGlobal('useI18n', () => ({
      t: (key: string, params?: Record<string, unknown>) => {
        if (key === 'admin.metro.stations.confirmDelete') {
          return `${key}:${String(params?.name ?? '')}`;
        }
        return key;
      },
    }));
    vi.stubGlobal('useAdminMetroStations', () => ({
      list: vi.fn(),
      remove: vi.fn(),
    }));
    vi.stubGlobal('useAdminMetroLines', () => ({
      show: vi.fn(async () => ({ name: 'Line', color: '#000000' })),
    }));
    vi.stubGlobal('useAdminCrudIndex', () => crudState);

    const wrapper = mount(MetroStationsIndexPage, {
      global: {
        stubs: {
          NuxtLink: defineComponent({
            template: '<a><slot /></a>',
          }),
          AdminEntityIndex: defineComponent({
            template: '<section><slot name="table" /><slot name="cards" /></section>',
          }),
          AdminCrudActions: defineComponent({
            emits: ['delete'],
            template: '<button data-test="delete" @click="$emit(\'delete\')">delete</button>',
          }),
          UiModal: defineComponent({
            template: '<div />',
          }),
        },
      },
    });

    await wrapper.get('[data-test="delete"]').trigger('click');

    expect(crudState.removeItem).toHaveBeenCalledTimes(1);
    expect(crudState.removeItem).toHaveBeenCalledWith(
      item,
      expect.objectContaining({
        confirmMessage: 'admin.metro.stations.confirmDelete:Sokol',
      })
    );
  });

  it('triggers debounced search fetch after query input change', async () => {
    vi.useFakeTimers();
    const crudState = makeCrudState([]);

    vi.stubGlobal('definePageMeta', vi.fn());
    vi.stubGlobal('useI18n', () => ({
      t: (key: string) => key,
    }));
    vi.stubGlobal('useAdminMetroStations', () => ({
      list: vi.fn(),
      remove: vi.fn(),
    }));
    vi.stubGlobal('useAdminMetroLines', () => ({
      show: vi.fn(async () => ({ name: 'Line', color: '#000000' })),
    }));
    vi.stubGlobal('useAdminCrudIndex', () => crudState);

    mount(MetroStationsIndexPage, {
      global: {
        stubs: {
          NuxtLink: true,
          AdminEntityIndex: defineComponent({
            template: '<section><slot name="table" /><slot name="cards" /></section>',
          }),
          AdminCrudActions: true,
          UiModal: true,
        },
      },
    });

    crudState.listState.searchInput.value = 'station query';
    await nextTick();

    vi.advanceTimersByTime(310);

    expect(crudState.listState.applySearch).toHaveBeenCalledTimes(1);
    expect(crudState.fetchItems).toHaveBeenCalledWith(4);
    vi.useRealTimers();
  });
});
