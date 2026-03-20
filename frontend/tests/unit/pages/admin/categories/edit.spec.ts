// @vitest-environment jsdom
import { flushPromises, mount } from '@vue/test-utils';
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
import CategoriesEditPage from '~/pages/admin/categories/[id]/edit.vue';

const categoriesApi = {
  tree: vi.fn(),
  previewSlug: vi.fn(),
  show: vi.fn(),
  update: vi.fn(),
};

const navigateToMock = vi.fn();

vi.mock('~/composables/useAsyncSelectOptions', () => ({
  useDebouncedSearch: vi.fn(),
}));

const UiInputStub = defineComponent({
  props: {
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
  },
  emits: ['update:modelValue'],
  template: `
    <label>
      <span>{{ label }}</span>
      <input :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" />
      <small data-test="hint">{{ hint }}</small>
      <slot name="append" />
    </label>
  `,
});

const UiSelectStub = defineComponent({
  props: {
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    options: { type: Array, default: () => [] },
  },
  emits: ['update:modelValue'],
  template: `
    <label>
      <span>{{ label }}</span>
      <select
        data-test="parent-select"
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
      >
        <option value="">none</option>
        <option v-for="option in options" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
      <output data-test="parent-options">{{ options.map((option) => option.label).join('|') }}</output>
    </label>
  `,
});

const UiSwitchStub = defineComponent({
  props: {
    modelValue: { type: Boolean, default: false },
    label: { type: String, default: '' },
  },
  emits: ['update:modelValue'],
  template: `
    <label>
      <span>{{ label }}</span>
      <input
        data-test="active-switch"
        type="checkbox"
        :checked="modelValue"
        @change="$emit('update:modelValue', $event.target.checked)"
      />
    </label>
  `,
});

describe('admin categories edit page', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.stubGlobal('definePageMeta', vi.fn());
    vi.stubGlobal('useI18n', () => ({
      t: (key: string) => key,
    }));
    vi.stubGlobal('useAdminCategories', () => categoriesApi);
    vi.stubGlobal('navigateTo', navigateToMock);
    vi.stubGlobal('useRoute', () => ({
      params: { id: 'leaf-1' },
    }));
    vi.stubGlobal('usePermissions', () => ({
      hasPermission: () => true,
    }));
    vi.stubGlobal('ref', ref);
    vi.stubGlobal('reactive', reactive);
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('watch', watch);
    vi.stubGlobal('onMounted', onMounted);
    vi.stubGlobal('onBeforeUnmount', onBeforeUnmount);

    categoriesApi.tree.mockResolvedValue([
      { id: 'root-1', name: 'Спорт', slug: 'sport', parent_id: null },
      { id: 'leaf-1', name: 'Футбол', slug: 'futbol', parent_id: 'root-1' },
      { id: 'root-2', name: 'Музыка', slug: 'muzyka', parent_id: null },
    ]);
    categoriesApi.show.mockResolvedValue({
      id: 'leaf-1',
      name: 'Футбол',
      slug: 'futbol',
      parent_id: 'root-1',
      sort_order: 10,
      is_active: true,
    });
    categoriesApi.previewSlug.mockResolvedValue('futbol-2');
    categoriesApi.update.mockResolvedValue(undefined);
  });

  it('excludes current category from parent selector options', async () => {
    const wrapper = mount(CategoriesEditPage, {
      global: {
        stubs: {
          NuxtLink: true,
          UiInput: UiInputStub,
          UiSelect: UiSelectStub,
          UiSwitch: UiSwitchStub,
          AdminChangeLogPanel: true,
          AdminActionLogPanel: true,
        },
      },
    });

    await flushPromises();
    await nextTick();

    expect(wrapper.get('[data-test="parent-options"]').text()).toBe('Спорт|Музыка');
  });

  it('requests slug preview with ignore_id when user regenerates slug', async () => {
    const wrapper = mount(CategoriesEditPage, {
      global: {
        stubs: {
          NuxtLink: true,
          UiInput: UiInputStub,
          UiSelect: UiSelectStub,
          UiSwitch: UiSwitchStub,
          AdminChangeLogPanel: true,
          AdminActionLogPanel: true,
        },
      },
    });

    await flushPromises();
    await nextTick();

    const button = wrapper.find('button[type="button"]');
    await button.trigger('click');

    expect(categoriesApi.previewSlug).toHaveBeenLastCalledWith({
      name: 'Футбол',
      parent_id: 'root-1',
      ignore_id: 'leaf-1',
    });
  });
});
