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
import ActivitiesEditPage from '~/pages/admin/activities/[id]/edit.vue';

const activitiesApi = {
  previewSlug: vi.fn(),
  show: vi.fn(),
  update: vi.fn(),
};

const organizationsApi = {
  list: vi.fn(),
};

const categoriesApi = {
  tree: vi.fn(),
};

const routerPushMock = vi.fn();

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
      <span data-test="image-captions">{{ images.map((image) => image.caption).join('|') }}</span>
      <button v-for="(image, index) in images" :key="image.id" type="button" @click="$emit('remove', index)">
        remove {{ index }}
      </button>
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

describe('admin activities edit page', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.stubGlobal('definePageMeta', vi.fn());
    vi.stubGlobal('useI18n', () => ({ t: (key: string) => key }));
    vi.stubGlobal('useAdminActivities', () => activitiesApi);
    vi.stubGlobal('useAdminOrganizations', () => organizationsApi);
    vi.stubGlobal('useAdminCategories', () => categoriesApi);
    vi.stubGlobal('useRoute', () => ({ params: { id: 'act-1' } }));
    vi.stubGlobal('useRouter', () => ({ push: routerPushMock }));
    vi.stubGlobal('usePermissions', () => ({ hasPermission: () => true }));
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

    activitiesApi.previewSlug.mockResolvedValue('futbol-2');
    activitiesApi.update.mockResolvedValue(undefined);
    activitiesApi.show.mockResolvedValue({
      id: 'act-1',
      organization_id: 'org-1',
      location_id: 'loc-1',
      name: 'Футбольная секция',
      slug: 'futbol',
      short_description: 'Короткое описание',
      description: 'Подробное описание',
      min_age: 7,
      max_age: 12,
      capacity: 20,
      price_from: 1500,
      price_to: 2500,
      currency: 'RUB',
      status: 'draft',
      is_featured: false,
      published_at: '2026-03-18T10:30:00Z',
      organization: { id: 'org-1', name: 'Чемпионы' },
      location: {
        id: 'loc-1',
        organization_id: 'org-1',
        city_id: 'city-1',
        address: 'ул. Ленина, 1',
      },
      primary_category: {
        id: 'leaf-1',
        name: 'Футбол',
        slug: 'futbol',
        parent_id: 'root-1',
        parent: { id: 'root-1', name: 'Спорт', slug: 'sport' },
      },
      schedules: [{ id: 'sch-1', day_of_week: 1, start_time: '17:00:00', end_time: '18:30:00' }],
      cover: {
        id: 'cover-1',
        url: 'https://example.com/cover.jpg',
        original_name: 'cover.jpg',
        mime_type: 'image/jpeg',
        size: 123,
        collection: 'cover',
      },
      gallery: [
        {
          id: 'gallery-1',
          url: 'https://example.com/gallery-1.jpg',
          original_name: 'gallery-1.jpg',
          mime_type: 'image/jpeg',
          size: 456,
          collection: 'gallery',
        },
      ],
    });
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

  it('preloads existing root/category pair and existing media blocks', async () => {
    const wrapper = mount(ActivitiesEditPage, {
      global: {
        stubs: {
          NuxtLink: true,
          UiInput: UiInputStub,
          UiTextarea: UiTextareaStub,
          UiSelect: UiSelectStub,
          UiSwitch: UiSwitchStub,
          UiImageBlock: UiImageBlockStub,
          UiImageDropzone: UiImageDropzoneStub,
          AdminChangeLogPanel: true,
          AdminActionLogPanel: true,
        },
      },
    });

    await flushPromises();
    await nextTick();

    const selects = wrapper.findAllComponents(UiSelectStub);
    const imageCaptions = wrapper.findAll('[data-test="image-captions"]');

    expect((selects[2]?.find('select').element as HTMLSelectElement).value).toBe('root-1');
    expect((selects[3]?.find('select').element as HTMLSelectElement).value).toBe('leaf-1');
    expect(imageCaptions[0]?.text()).toContain('admin.activities.media.coverExistingCaption');
    expect(imageCaptions[1]?.text()).toContain('admin.activities.media.galleryExistingCaption');
  });

  it('requests slug preview with ignore_id and submits media-aware update payload', async () => {
    const wrapper = mount(ActivitiesEditPage, {
      global: {
        stubs: {
          NuxtLink: true,
          UiInput: UiInputStub,
          UiTextarea: UiTextareaStub,
          UiSelect: UiSelectStub,
          UiSwitch: UiSwitchStub,
          UiImageBlock: UiImageBlockStub,
          UiImageDropzone: UiImageDropzoneStub,
          AdminChangeLogPanel: true,
          AdminActionLogPanel: true,
        },
      },
    });

    await flushPromises();
    await nextTick();

    const slugButton = wrapper
      .findAll('button[type="button"]')
      .find((button) => button.text() === 'admin.activities.edit.slugAutofill');
    expect(slugButton).toBeTruthy();
    await slugButton!.trigger('click');

    expect(activitiesApi.previewSlug).toHaveBeenLastCalledWith({
      name: 'Футбольная секция',
      ignore_id: 'act-1',
    });

    const imageButtons = wrapper.findAll('[data-test="image-captions"] + button');
    await imageButtons[0]!.trigger('click');
    await imageButtons[1]!.trigger('click');

    await wrapper.get('form').trigger('submit.prevent');

    expect(activitiesApi.update).toHaveBeenCalledWith(
      'act-1',
      expect.objectContaining({
        organization_id: 'org-1',
        location_id: 'loc-1',
        category_id: 'leaf-1',
        name: 'Футбольная секция',
        slug: 'futbol-2',
        short_description: 'Короткое описание',
        description: 'Подробное описание',
        min_age: 7,
        max_age: 12,
        capacity: 20,
        price_from: 1500,
        price_to: 2500,
        currency: 'RUB',
        status: 'draft',
        is_featured: false,
        published_at: '2026-03-18T10:30',
        cover_delete: true,
        gallery_delete_ids: ['gallery-1'],
        gallery_order_ids: [],
        schedules: [
          {
            day_of_week: 1,
            start_time: '17:00:00',
            end_time: '18:30:00',
          },
        ],
      })
    );
    expect(routerPushMock).toHaveBeenCalledWith('/admin/activities/act-1');
  });
});
