<template>
  <section class="admin-form-page mx-auto w-full max-w-4xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.activities.edit.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.activities.edit.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <div v-if="loading" class="admin-page-skeleton space-y-3" aria-hidden="true">
        <div class="skeleton-line is-title" />
        <div v-for="index in 8" :key="index" class="space-y-2">
          <div class="skeleton-line is-label" />
          <div class="skeleton-line is-input" />
        </div>
      </div>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <form v-else class="space-y-3" @submit.prevent="submitForm">
        <div class="grid gap-3 sm:grid-cols-2">
          <UiSelect
            v-model="form.organization_id"
            :label="t('admin.activities.fields.organization')"
            :options="organizationOptions"
            :disabled="saving || organizationsLoading"
            :error="fieldErrors.organization_id"
            :placeholder="t('admin.activities.new.organizationPlaceholder')"
          />
          <UiSelect
            v-model="form.location_id"
            :label="t('admin.activities.fields.location')"
            :options="locationOptions"
            :disabled="saving || locationsDisabled"
            :error="fieldErrors.location_id"
            :placeholder="t('admin.activities.new.locationPlaceholder')"
          />
        </div>

        <UiSelect
          v-model="form.root_category_id"
          :label="t('admin.activities.fields.rootCategory')"
          :options="rootCategoryOptions"
          :searchable="false"
          :disabled="saving || categoriesLoading"
          :error="fieldErrors.root_category_id"
          :placeholder="t('admin.activities.new.rootCategoryPlaceholder')"
        />

        <UiSelect
          v-model="form.category_id"
          :label="t('admin.activities.fields.subcategory')"
          :options="leafCategoryOptions"
          :searchable="false"
          :disabled="saving || categoriesLoading || !form.root_category_id"
          :error="fieldErrors.category_id"
          :placeholder="t('admin.activities.new.categoryPlaceholder')"
        />

        <UiInput
          v-model="form.name"
          :label="t('admin.activities.fields.name')"
          required
          :disabled="saving"
          :error="fieldErrors.name"
        />

        <UiInput
          :model-value="form.slug"
          :label="t('admin.activities.fields.slug')"
          required
          :disabled="saving"
          :error="fieldErrors.slug"
          :hint="slugHint"
          @update:model-value="onSlugInput"
        >
          <template #append>
            <button
              type="button"
              class="admin-button-secondary rounded-md px-2 py-1 text-xs"
              :disabled="saving || slugPreviewLoading || !form.name.trim()"
              @click="regenerateSlug"
            >
              {{ t('admin.activities.edit.slugAutofill') }}
            </button>
          </template>
        </UiInput>

        <UiTextarea
          v-model="form.short_description"
          :label="t('admin.activities.fields.shortDescription')"
          :rows="3"
          required
          :disabled="saving"
          :error="fieldErrors.short_description"
        />

        <UiTextarea
          v-model="form.description"
          :label="t('admin.activities.fields.description')"
          :rows="5"
          :disabled="saving"
          :error="fieldErrors.description"
        />

        <div class="space-y-3 rounded-xl border border-[var(--admin-border)] p-4">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold">{{ t('admin.activities.schedule.title') }}</h3>
              <p class="admin-muted mt-1 text-xs">{{ t('admin.activities.schedule.subtitle') }}</p>
            </div>
            <button
              type="button"
              class="admin-button-secondary rounded-lg px-3 py-2 text-sm"
              :disabled="saving"
              @click="addScheduleRow"
            >
              {{ t('admin.activities.schedule.add') }}
            </button>
          </div>

          <p v-if="fieldErrors.schedules" class="admin-error text-sm">
            {{ fieldErrors.schedules }}
          </p>

          <p v-if="!form.schedules.length" class="admin-muted text-sm">
            {{ t('admin.activities.schedule.empty') }}
          </p>

          <div v-else class="space-y-3">
            <div
              v-for="(schedule, index) in form.schedules"
              :key="`edit-schedule-${index}`"
              class="grid gap-3 rounded-xl border border-[var(--admin-border)] p-3 sm:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)_auto]"
            >
              <UiSelect
                v-model="schedule.day_of_week"
                :label="t('admin.activities.schedule.weekday')"
                :options="weekdayOptions"
                :searchable="false"
                :disabled="saving"
                :placeholder="t('admin.activities.schedule.weekdayPlaceholder')"
              />
              <UiInput
                v-model="schedule.start_time"
                :label="t('admin.activities.schedule.startTime')"
                type="time"
                :disabled="saving"
              />
              <UiInput
                v-model="schedule.end_time"
                :label="t('admin.activities.schedule.endTime')"
                type="time"
                :disabled="saving"
              />
              <div class="flex items-end">
                <button
                  type="button"
                  class="admin-button-secondary rounded-lg px-3 py-2 text-sm"
                  :disabled="saving"
                  @click="removeScheduleRow(index)"
                >
                  {{ t('admin.actions.delete') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-4 rounded-xl border border-[var(--admin-border)] p-4">
          <div>
            <h3 class="text-sm font-semibold">{{ t('admin.activities.media.title') }}</h3>
            <p class="admin-muted mt-1 text-xs">{{ t('admin.activities.media.subtitle') }}</p>
          </div>

          <div class="space-y-3">
            <UiImageBlock
              :title="t('admin.activities.media.coverTitle')"
              :description="t('admin.activities.media.coverHint')"
              :images="coverImages"
              :show-add-button="false"
              :removable="!saving"
              :remove-button-text="t('admin.actions.delete')"
              :empty-text="t('admin.activities.media.coverEmpty')"
              :caption-prefix="t('admin.activities.media.coverExistingCaption')"
              @remove="clearCover"
            />
            <UiImageDropzone
              v-model="coverDraftFiles"
              :title="t('admin.activities.media.coverUploadTitle')"
              :description="t('admin.activities.media.imageHint')"
              :browse-button-text="t('admin.activities.media.coverUploadButton')"
              accept="image/png,image/jpeg,image/webp"
              :multiple="false"
              :disabled="saving"
            />
          </div>

          <div class="space-y-3">
            <UiImageBlock
              :title="t('admin.activities.media.galleryTitle')"
              :description="t('admin.activities.media.galleryHint')"
              :images="galleryImages"
              :show-add-button="false"
              :removable="!saving"
              :remove-button-text="t('admin.actions.delete')"
              :empty-text="t('admin.activities.media.galleryEmpty')"
              :caption-prefix="t('admin.activities.media.galleryExistingCaption')"
              @remove="removeGalleryImage"
            />
            <UiImageDropzone
              v-model="galleryDraftFiles"
              :title="t('admin.activities.media.galleryUploadTitle')"
              :description="t('admin.activities.media.imageHint')"
              :browse-button-text="t('admin.activities.media.galleryUploadButton')"
              accept="image/png,image/jpeg,image/webp"
              :multiple="true"
              :disabled="saving"
            />
          </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <UiInput
            v-model="form.min_age"
            :label="t('admin.activities.fields.minAge')"
            type="number"
            min="0"
            :disabled="saving"
            :error="fieldErrors.min_age"
          />
          <UiInput
            v-model="form.max_age"
            :label="t('admin.activities.fields.maxAge')"
            type="number"
            min="0"
            :disabled="saving"
            :error="fieldErrors.max_age"
          />
          <UiInput
            v-model="form.capacity"
            :label="t('admin.activities.fields.capacity')"
            type="number"
            min="0"
            :disabled="saving"
            :error="fieldErrors.capacity"
          />
          <UiSelect
            v-model="form.status"
            :label="t('admin.activities.fields.status')"
            :options="statusOptions"
            :placeholder="t('admin.activities.new.statusPlaceholder')"
            :searchable="false"
            :disabled="saving"
            :error="fieldErrors.status"
          />
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
          <UiInput
            v-model="form.price_from"
            :label="t('admin.activities.fields.priceFrom')"
            type="number"
            min="0"
            step="0.01"
            :disabled="saving"
            :error="fieldErrors.price_from"
          />
          <UiInput
            v-model="form.price_to"
            :label="t('admin.activities.fields.priceTo')"
            type="number"
            min="0"
            step="0.01"
            :disabled="saving"
            :error="fieldErrors.price_to"
          />
          <UiInput
            v-model="form.currency"
            :label="t('admin.activities.fields.currency')"
            :disabled="saving"
            :error="fieldErrors.currency"
          />
        </div>

        <UiInput
          v-model="form.published_at"
          :label="t('admin.activities.fields.publishedAt')"
          type="datetime-local"
          :disabled="saving"
          :error="fieldErrors.published_at"
        />

        <UiSwitch
          v-model="form.is_featured"
          :label="t('admin.activities.fields.isFeatured')"
          :description="t('admin.activities.new.isFeaturedHint')"
          :disabled="saving"
        />

        <p v-if="organizationsLoadError" class="admin-error text-sm">
          {{ organizationsLoadError }}
        </p>
        <p v-if="categoriesLoadError" class="admin-error text-sm">{{ categoriesLoadError }}</p>
        <p v-if="slugPreviewError" class="admin-error text-sm">{{ slugPreviewError }}</p>
        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving"
          >
            {{ saving ? t('admin.activities.edit.saving') : t('common.save') }}
          </button>
          <NuxtLink
            :to="`/admin/activities/${route.params.id}`"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.cancel') }}
          </NuxtLink>
        </div>
      </form>
    </article>

    <AdminChangeLogPanel
      v-if="canReadChangeLog"
      model="activity"
      :entity-id="String(route.params.id || '')"
      @rolled-back="fetchActivity"
    />

    <AdminActionLogPanel model="activity" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import AdminActionLogPanel from '~/components/admin/ActionLog/AdminActionLogPanel.vue';
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import UiSwitch from '~/components/ui/FormControls/UiSwitch/UiSwitch.vue';
import UiTextarea from '~/components/ui/FormControls/UiTextarea/UiTextarea.vue';
import UiImageBlock from '~/components/ui/ImageBlock/UiImageBlock/UiImageBlock.vue';
import UiImageDropzone from '~/components/ui/ImageBlock/UiImageDropzone/UiImageDropzone.vue';
import type {
  AdminActivity,
  AdminActivityFile,
  AdminActivityScheduleInput,
  UpdateActivityPayload,
} from '~/composables/useAdminActivities';
import type { AdminCategory } from '~/composables/useAdminCategories';
import type { AdminOrganization } from '~/composables/useAdminOrganizations';
import {
  applyFieldErrors,
  clearFieldErrors,
  getApiErrorMessage,
  getApiErrorPayload,
} from '~/composables/useAdminCrudCommon';

const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: ['admin.activities.read', 'admin.activities.update'],
  permissionMode: 'all',
});

const route = useRoute();
const router = useRouter();
const activitiesApi = useAdminActivities();
const organizationsApi = useAdminOrganizations();
const categoriesApi = useAdminCategories();
const { hasPermission } = usePermissions();
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));

const loading = ref(false);
const loadError = ref('');
const saving = ref(false);
const formError = ref('');
const organizationsLoading = ref(false);
const organizationsLoadError = ref('');
const categoriesLoading = ref(false);
const categoriesLoadError = ref('');
const slugPreviewLoading = ref(false);
const slugPreviewError = ref('');
const slugTouched = ref(false);
const lastGeneratedSlug = ref('');
const activity = ref<AdminActivity | null>(null);
const coverDraftFiles = ref<File[]>([]);
const galleryDraftFiles = ref<File[]>([]);
const coverDraftUrl = ref<string | null>(null);
const galleryDraftUrls = ref<string[]>([]);
const existingCoverRemoved = ref(false);
const existingGalleryDeletedIds = ref<string[]>([]);
const organizations = ref<AdminOrganization[]>([]);
const categories = ref<AdminCategory[]>([]);

const form = reactive({
  organization_id: '',
  location_id: '',
  root_category_id: '',
  category_id: '',
  name: '',
  slug: '',
  short_description: '',
  description: '',
  min_age: '',
  max_age: '',
  capacity: '',
  price_from: '',
  price_to: '',
  currency: 'RUB',
  status: 'draft',
  is_featured: false,
  published_at: '',
  schedules: [] as Array<{ day_of_week: string; start_time: string; end_time: string }>,
});

const fieldErrors = reactive<Record<string, string>>({
  organization_id: '',
  location_id: '',
  root_category_id: '',
  category_id: '',
  name: '',
  slug: '',
  short_description: '',
  description: '',
  min_age: '',
  max_age: '',
  capacity: '',
  price_from: '',
  price_to: '',
  currency: '',
  status: '',
  published_at: '',
  schedules: '',
});

const currentActivityId = computed(() => String(route.params.id || ''));

const organizationOptions = computed(() =>
  organizations.value.map((organization) => ({
    value: organization.id,
    label: organization.name,
  }))
);

const selectedOrganization = computed(
  () => organizations.value.find((organization) => organization.id === form.organization_id) ?? null
);

const locationOptions = computed(() =>
  (selectedOrganization.value?.locations ?? []).map((location, index) => ({
    value: location.id,
    label: location.address || `${t('admin.activities.new.locationLabel')} ${index + 1}`,
  }))
);

const rootCategoryOptions = computed(() =>
  categories.value
    .filter((category) => !category.parent_id)
    .map((category) => ({
      value: category.id,
      label: category.name,
    }))
);

const leafCategoryOptions = computed(() =>
  categories.value.flatMap((category) => {
    if (category.id !== form.root_category_id) {
      return [];
    }

    const children = Array.isArray(category.children) ? category.children : [];
    return children.map((child) => ({
      value: child.id,
      label: child.name,
    }));
  })
);
const locationsDisabled = computed(
  () => !form.organization_id || saving.value || organizationsLoading.value
);

const statusOptions = computed(() => [
  { value: 'draft', label: t('admin.activities.status.draft') },
  { value: 'pending_review', label: t('admin.activities.status.pendingReview') },
  { value: 'published', label: t('admin.activities.status.published') },
  { value: 'archived', label: t('admin.activities.status.archived') },
]);

const weekdayOptions = computed(() => [
  { value: '1', label: t('admin.activities.schedule.weekdays.monday') },
  { value: '2', label: t('admin.activities.schedule.weekdays.tuesday') },
  { value: '3', label: t('admin.activities.schedule.weekdays.wednesday') },
  { value: '4', label: t('admin.activities.schedule.weekdays.thursday') },
  { value: '5', label: t('admin.activities.schedule.weekdays.friday') },
  { value: '6', label: t('admin.activities.schedule.weekdays.saturday') },
  { value: '7', label: t('admin.activities.schedule.weekdays.sunday') },
]);

const coverImages = computed(() => {
  if (coverDraftUrl.value) {
    return [
      {
        id: 'draft-cover',
        src: coverDraftUrl.value,
        alt: t('admin.activities.media.coverTitle'),
        caption: t('admin.activities.media.coverDraftCaption'),
      },
    ];
  }

  const existingCover = activity.value?.cover;
  if (!existingCover || existingCoverRemoved.value) {
    return [];
  }

  return [
    {
      id: existingCover.id,
      src: existingCover.url,
      alt: existingCover.original_name || activity.value?.name || 'cover',
      caption: t('admin.activities.media.coverExistingCaption'),
    },
  ];
});

const existingGalleryFiles = computed(() =>
  (activity.value?.gallery ?? []).filter(
    (file) => !existingGalleryDeletedIds.value.includes(file.id)
  )
);

const galleryImages = computed(() => [
  ...existingGalleryFiles.value.map((file) => ({
    id: file.id,
    src: file.url,
    alt: file.original_name || activity.value?.name || 'gallery',
    caption: t('admin.activities.media.galleryExistingCaption'),
  })),
  ...galleryDraftUrls.value.map((url, index) => ({
    id: `draft-gallery-${index}`,
    src: url,
    alt: `${t('admin.activities.media.galleryTitle')} ${index + 1}`,
    caption: `${t('admin.activities.media.galleryDraftCaption')} ${index + 1}`,
  })),
]);

const slugHint = computed(() => {
  if (fieldErrors.slug) {
    return '';
  }

  if (slugPreviewLoading.value) {
    return t('admin.activities.edit.slugLoading');
  }

  if (slugPreviewError.value) {
    return slugPreviewError.value;
  }

  return t('admin.activities.edit.slugHint');
});

const resetErrors = () => {
  formError.value = '';
  clearFieldErrors(fieldErrors);
};

const createEmptyScheduleRow = () => ({
  day_of_week: '',
  start_time: '',
  end_time: '',
});

const revokeCoverDraftUrl = () => {
  if (coverDraftUrl.value) {
    URL.revokeObjectURL(coverDraftUrl.value);
    coverDraftUrl.value = null;
  }
};

const revokeGalleryDraftUrls = () => {
  galleryDraftUrls.value.forEach((url) => URL.revokeObjectURL(url));
  galleryDraftUrls.value = [];
};

const addScheduleRow = () => {
  form.schedules.push(createEmptyScheduleRow());
};

const removeScheduleRow = (index: number) => {
  form.schedules.splice(index, 1);
};

const normalizeScheduleTime = (value: string): string => {
  const trimmed = value.trim();

  if (!trimmed) {
    return '';
  }

  return trimmed.length === 5 ? `${trimmed}:00` : trimmed;
};

const toTimeInputValue = (value?: string | null): string => {
  if (!value) {
    return '';
  }

  return value.slice(0, 5);
};

const clearCover = () => {
  coverDraftFiles.value = [];
  if (activity.value?.cover) {
    existingCoverRemoved.value = true;
  }
};

const removeGalleryImage = (index: number) => {
  const existingCount = existingGalleryFiles.value.length;

  if (index < existingCount) {
    const existingFile = existingGalleryFiles.value[index];
    if (existingFile) {
      existingGalleryDeletedIds.value = [...existingGalleryDeletedIds.value, existingFile.id];
    }
    return;
  }

  galleryDraftFiles.value.splice(index - existingCount, 1);
};

const galleryOrderIds = computed(() => existingGalleryFiles.value.map((file) => file.id));

const buildSchedulesPayload = (): AdminActivityScheduleInput[] =>
  form.schedules
    .map((schedule) => ({
      day_of_week: Number(schedule.day_of_week),
      start_time: normalizeScheduleTime(schedule.start_time),
      end_time: normalizeScheduleTime(schedule.end_time),
    }))
    .filter((schedule) => schedule.day_of_week && schedule.start_time && schedule.end_time);

const validateCategoryPair = (): boolean => {
  let isValid = true;

  if (!form.root_category_id) {
    fieldErrors.root_category_id = t('admin.activities.new.errors.rootCategoryRequired');
    isValid = false;
  }

  if (!form.category_id) {
    fieldErrors.category_id = t('admin.activities.new.errors.subcategoryRequired');
    isValid = false;
  }

  return isValid;
};

const normalizeNumber = (value: string): number | null => {
  const trimmed = value.trim();

  if (!trimmed) {
    return null;
  }

  const parsed = Number(trimmed);
  return Number.isFinite(parsed) ? parsed : null;
};

const toDateTimeLocal = (value?: string | null): string => {
  if (!value) {
    return '';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  const pad = (input: number) => String(input).padStart(2, '0');

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const fillForm = (activity: AdminActivity) => {
  form.organization_id = activity.organization_id;
  form.location_id = activity.location_id;
  form.root_category_id = activity.primary_category?.parent?.id || '';
  form.category_id = activity.primary_category?.id || '';
  form.name = activity.name;
  form.slug = activity.slug;
  form.short_description = activity.short_description;
  form.description = activity.description || '';
  form.min_age = activity.min_age != null ? String(activity.min_age) : '';
  form.max_age = activity.max_age != null ? String(activity.max_age) : '';
  form.capacity = activity.capacity != null ? String(activity.capacity) : '';
  form.price_from = activity.price_from != null ? String(activity.price_from) : '';
  form.price_to = activity.price_to != null ? String(activity.price_to) : '';
  form.currency = activity.currency || 'RUB';
  form.status = activity.status;
  form.is_featured = activity.is_featured;
  form.published_at = toDateTimeLocal(activity.published_at);
  form.schedules = (activity.schedules ?? []).map((schedule) => ({
    day_of_week: String(schedule.day_of_week),
    start_time: toTimeInputValue(schedule.start_time),
    end_time: toTimeInputValue(schedule.end_time),
  }));
  existingCoverRemoved.value = false;
  existingGalleryDeletedIds.value = [];
  lastGeneratedSlug.value = activity.slug;
  slugTouched.value = false;
};

const loadOrganizations = async () => {
  organizationsLoading.value = true;
  organizationsLoadError.value = '';

  try {
    const response = await organizationsApi.list({
      per_page: 100,
      sort_by: 'name',
      sort_dir: 'asc',
    });

    organizations.value = response.data;
  } catch (error) {
    organizationsLoadError.value = getApiErrorMessage(
      error,
      t('admin.activities.new.errors.loadOrganizations')
    );
  } finally {
    organizationsLoading.value = false;
  }
};

const loadCategories = async () => {
  categoriesLoading.value = true;
  categoriesLoadError.value = '';

  try {
    categories.value = await categoriesApi.tree(true);
  } catch (error) {
    categoriesLoadError.value = getApiErrorMessage(
      error,
      t('admin.activities.new.errors.loadCategories')
    );
  } finally {
    categoriesLoading.value = false;
  }
};

const fetchSlugPreview = async (force = false) => {
  const name = form.name.trim();

  if (!name) {
    if (!slugTouched.value || force) {
      form.slug = '';
      lastGeneratedSlug.value = '';
    }
    slugPreviewError.value = '';
    return;
  }

  if (!force && slugTouched.value) {
    return;
  }

  slugPreviewLoading.value = true;
  slugPreviewError.value = '';

  try {
    const slug = await activitiesApi.previewSlug({
      name,
      ignore_id: currentActivityId.value || null,
    });

    form.slug = slug;
    lastGeneratedSlug.value = slug;
    slugTouched.value = false;
  } catch (error) {
    slugPreviewError.value = getApiErrorMessage(
      error,
      t('admin.activities.edit.errors.previewSlug')
    );
  } finally {
    slugPreviewLoading.value = false;
  }
};

const regenerateSlug = async () => {
  await fetchSlugPreview(true);
};

const onSlugInput = (value: string) => {
  form.slug = value;

  if (value.trim() === '' || value === lastGeneratedSlug.value) {
    slugTouched.value = false;
    return;
  }

  slugTouched.value = true;
};

const fetchActivity = async () => {
  const id = currentActivityId.value;

  if (!id) {
    loadError.value = t('admin.activities.edit.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    const loadedActivity = await activitiesApi.show(id);
    fillForm(loadedActivity);
    coverDraftFiles.value = [];
    galleryDraftFiles.value = [];
    revokeCoverDraftUrl();
    revokeGalleryDraftUrls();
    activity.value = loadedActivity;
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.activities.edit.errors.load'));
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  resetErrors();

  if (!validateCategoryPair()) {
    return;
  }

  saving.value = true;

  const payload: UpdateActivityPayload = {
    organization_id: form.organization_id,
    location_id: form.location_id,
    category_id: form.category_id,
    name: form.name.trim(),
    slug: form.slug.trim(),
    short_description: form.short_description.trim(),
    description: form.description.trim() || null,
    min_age: normalizeNumber(form.min_age),
    max_age: normalizeNumber(form.max_age),
    capacity: normalizeNumber(form.capacity),
    price_from: normalizeNumber(form.price_from),
    price_to: normalizeNumber(form.price_to),
    currency: form.currency.trim() || null,
    status: form.status || null,
    is_featured: form.is_featured,
    published_at: form.published_at || null,
    schedules: buildSchedulesPayload(),
    cover: coverDraftFiles.value[0] ?? null,
    cover_delete: existingCoverRemoved.value,
    gallery: [...galleryDraftFiles.value],
    gallery_delete_ids: [...existingGalleryDeletedIds.value],
    gallery_order_ids: galleryOrderIds.value,
  };

  try {
    await activitiesApi.update(currentActivityId.value, payload);
    await router.push(`/admin/activities/${currentActivityId.value}`);
  } catch (error) {
    formError.value = getApiErrorMessage(error, t('admin.activities.edit.errors.update'));
    applyFieldErrors(fieldErrors, getApiErrorPayload<Record<string, string[]>>(error), {
      schedules: ['schedules.0.day_of_week', 'schedules.0.start_time', 'schedules.0.end_time'],
    });
  } finally {
    saving.value = false;
  }
};

watch(
  () => form.name,
  () => {
    void fetchSlugPreview();
  }
);

watch(coverDraftFiles, (files) => {
  revokeCoverDraftUrl();

  const file = files[0];
  if (file instanceof File) {
    coverDraftUrl.value = URL.createObjectURL(file);
    existingCoverRemoved.value = false;
  }
});

watch(galleryDraftFiles, (files) => {
  revokeGalleryDraftUrls();
  galleryDraftUrls.value = files.map((file) => URL.createObjectURL(file));
});

onMounted(async () => {
  await Promise.all([loadOrganizations(), loadCategories(), fetchActivity()]);
});

watch(
  () => form.root_category_id,
  () => {
    const availableLeafIds = new Set(leafCategoryOptions.value.map((option) => option.value));
    if (!availableLeafIds.has(form.category_id)) {
      form.category_id = '';
    }
  }
);

onBeforeUnmount(() => {
  revokeCoverDraftUrl();
  revokeGalleryDraftUrls();
});
</script>

<style lang="scss" scoped>
.admin-page-skeleton {
  .skeleton-line {
    display: block;
    border-radius: 999px;
    background: linear-gradient(
      90deg,
      rgba(148, 163, 184, 0.12) 0%,
      rgba(148, 163, 184, 0.28) 50%,
      rgba(148, 163, 184, 0.12) 100%
    );
    background-size: 200% 100%;
    animation: admin-page-skeleton-shimmer 1.2s ease-in-out infinite;
  }

  .skeleton-line.is-title {
    width: 38%;
    height: 1.25rem;
  }

  .skeleton-line.is-label {
    width: 32%;
    height: 0.75rem;
  }

  .skeleton-line.is-input {
    width: 100%;
    height: 2.75rem;
    border-radius: 0.875rem;
  }
}

@keyframes admin-page-skeleton-shimmer {
  0% {
    background-position: 200% 0;
  }

  100% {
    background-position: -200% 0;
  }
}
</style>
