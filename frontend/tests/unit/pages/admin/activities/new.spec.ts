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
import ActivitiesNewPage from '~/pages/admin/activities/new.vue';

const activitiesApi = {
  previewSlug: vi.fn(),
  create: vi.fn(),
};

const organizationsApi = {
  list: vi.fn(),
};

const categoriesApi = {
  tree: vi.fn(),
};

const navigateToMock = vi.fn();

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
      <small>{{ hint }}</small>
      <slot name="append" />
    </label>
  `,
});

const UiTextareaStub = defineComponent({
  props: {
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
  },
  emits: ['update:modelValue'],
  template: `
    <label>
      <span>{{ label }}</span>
      <textarea :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" />
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
      <select :value="modelValue" @change="$emit('update:modelValue', $event.target.value)">
        <option value="">none</option>
        <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
      </select>
      <output>{{ options.map((option) => option.label).join('|') }}</output>
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
      <input type="checkbox" :checked="modelValue" @change="$emit('update:modelValue', $event.target.checked)" />
    </label>
  `,
});

const UiImageBlockStub = defineComponent({
  props: {
    images: { type: Array, default: () => [] },
  },
  emits: ['remove'],
  template: `
    <div>
      <span data-test="image-count">{{ images.length }}</span>
    </div>
  `,
});

const UiImageDropzoneStub = defineComponent({
  props: {
    modelValue: { type: Array, default: () => [] },
  },
  emits: ['update:modelValue'],
  template: '<div data-test="dropzone" />',
});

describe('admin activities new page', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.stubGlobal('definePageMeta', vi.fn());
    vi.stubGlobal('useI18n', () => ({ t: (key: string) => key }));
    vi.stubGlobal('useAdminActivities', () => activitiesApi);
    vi.stubGlobal('useAdminOrganizations', () => organizationsApi);
    vi.stubGlobal('useAdminCategories', () => categoriesApi);
    vi.stubGlobal('navigateTo', navigateToMock);
    vi.stubGlobal('ref', ref);
    vi.stubGlobal('reactive', reactive);
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('watch', watch);
    vi.stubGlobal('onMounted', onMounted);
    vi.stubGlobal('onBeforeUnmount', onBeforeUnmount);
    vi.stubGlobal('File', File);
    vi.stubGlobal('URL', {
      createObjectURL: vi.fn(() => 'blob:preview'),
      revokeObjectURL: vi.fn(),
    });

    activitiesApi.previewSlug.mockResolvedValue('futbolnaya-sektsiya');
    activitiesApi.create.mockResolvedValue(undefined);
    organizationsApi.list.mockResolvedValue({
      data: [
        {
          id: 'org-1',
          name: 'Чемпионы',
          locations: [{ id: 'loc-1', city_id: 'city-1', address: 'ул. Ленина, 1' }],
        },
      ],
    });
    categoriesApi.tree.mockResolvedValue([
      {
        id: 'root-1',
        name: 'Спорт',
        slug: 'sport',
        parent_id: null,
        children: [{ id: 'leaf-1', name: 'Футбол', slug: 'futbol', parent_id: 'root-1' }],
      },
      {
        id: 'root-2',
        name: 'Музыка',
        slug: 'muzyka',
        parent_id: null,
        children: [{ id: 'leaf-2', name: 'Вокал', slug: 'vokal', parent_id: 'root-2' }],
      },
    ]);
  });

  it('maps root and leaf categories into separate selectors', async () => {
    const wrapper = mount(ActivitiesNewPage, {
      global: {
        stubs: {
          NuxtLink: true,
          UiInput: UiInputStub,
          UiTextarea: UiTextareaStub,
          UiSelect: UiSelectStub,
          UiSwitch: UiSwitchStub,
          UiImageBlock: UiImageBlockStub,
          UiImageDropzone: UiImageDropzoneStub,
        },
      },
    });

    await nextTick();
    await nextTick();

    const selects = wrapper.findAllComponents(UiSelectStub);

    expect(selects[2]?.find('output').text()).toBe('Спорт|Музыка');
    await selects[2]!.vm.$emit('update:modelValue', 'root-1');
    await nextTick();
    expect(selects[3]?.find('output').text()).toBe('Футбол');
  });

  it('submits normalized create payload with category pair and schedules', async () => {
    const wrapper = mount(ActivitiesNewPage, {
      global: {
        stubs: {
          NuxtLink: true,
          UiInput: UiInputStub,
          UiTextarea: UiTextareaStub,
          UiSelect: UiSelectStub,
          UiSwitch: UiSwitchStub,
          UiImageBlock: UiImageBlockStub,
          UiImageDropzone: UiImageDropzoneStub,
        },
      },
    });

    await nextTick();
    await nextTick();

    const inputs = wrapper.findAllComponents(UiInputStub);
    const selects = wrapper.findAllComponents(UiSelectStub);
    const textareas = wrapper.findAllComponents(UiTextareaStub);

    await selects[0]!.vm.$emit('update:modelValue', 'org-1');
    await nextTick();
    await selects[1]!.vm.$emit('update:modelValue', 'loc-1');
    await selects[2]!.vm.$emit('update:modelValue', 'root-1');
    await nextTick();
    await selects[3]!.vm.$emit('update:modelValue', 'leaf-1');
    await inputs[0]!.vm.$emit('update:modelValue', 'Футбольная секция');
    await nextTick();
    await inputs[1]!.vm.$emit('update:modelValue', 'custom-slug');
    await textareas[0]!.vm.$emit('update:modelValue', 'Тренировки для детей');
    await textareas[1]!.vm.$emit('update:modelValue', 'Подробное описание активности');

    const addScheduleButton = wrapper
      .findAll('button[type="button"]')
      .find((button) => button.text() === 'admin.activities.schedule.add');
    expect(addScheduleButton).toBeTruthy();
    await addScheduleButton!.trigger('click');
    await nextTick();

    const refreshedSelects = wrapper.findAllComponents(UiSelectStub);
    const refreshedInputs = wrapper.findAllComponents(UiInputStub);

    await refreshedSelects[4]!.vm.$emit('update:modelValue', '1');
    await refreshedInputs[2]!.vm.$emit('update:modelValue', '17:00');
    await refreshedInputs[3]!.vm.$emit('update:modelValue', '18:30');
    await refreshedInputs[4]!.vm.$emit('update:modelValue', '7');
    await refreshedInputs[5]!.vm.$emit('update:modelValue', '12');
    await refreshedInputs[6]!.vm.$emit('update:modelValue', '20');
    await refreshedSelects[5]!.vm.$emit('update:modelValue', 'published');
    await refreshedInputs[7]!.vm.$emit('update:modelValue', '1500');
    await refreshedInputs[8]!.vm.$emit('update:modelValue', '2500');
    await refreshedInputs[9]!.vm.$emit('update:modelValue', 'RUB');

    await wrapper.get('form').trigger('submit.prevent');

    expect(activitiesApi.create).toHaveBeenCalledWith({
      organization_id: 'org-1',
      location_id: 'loc-1',
      category_id: 'leaf-1',
      name: 'Футбольная секция',
      slug: 'custom-slug',
      short_description: 'Тренировки для детей',
      description: 'Подробное описание активности',
      min_age: 7,
      max_age: 12,
      capacity: 20,
      price_from: 1500,
      price_to: 2500,
      currency: 'RUB',
      status: 'published',
      is_featured: false,
      published_at: null,
      schedules: [
        {
          day_of_week: 1,
          start_time: '17:00:00',
          end_time: '18:30:00',
        },
      ],
      cover: null,
      gallery: [],
    });
    expect(navigateToMock).toHaveBeenCalledWith('/admin/activities');
  });
});
