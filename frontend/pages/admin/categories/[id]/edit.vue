<template>
  <section class="admin-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.categories.edit.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.categories.edit.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <div v-if="loading" class="admin-page-skeleton space-y-3" aria-hidden="true">
        <div class="skeleton-line is-title" />
        <div v-for="index in 5" :key="index" class="space-y-2">
          <div class="skeleton-line is-label" />
          <div class="skeleton-line is-input" />
        </div>
      </div>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <form v-else class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.name"
          :label="t('admin.categories.fields.name')"
          required
          :disabled="saving"
          :error="fieldErrors.name"
        />

        <UiInput
          :model-value="form.slug"
          :label="t('admin.categories.fields.slug')"
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
              {{ t('admin.categories.edit.slugAutofill') }}
            </button>
          </template>
        </UiInput>

        <UiSelect
          v-model="form.parent_id"
          :label="t('admin.categories.fields.parent')"
          :options="parentOptions"
          :placeholder="t('admin.categories.edit.parentPlaceholder')"
          :disabled="saving || parentLoading"
          :error="fieldErrors.parent_id"
        />

        <UiInput
          v-model="form.sort_order"
          :label="t('admin.categories.fields.sortOrder')"
          type="number"
          min="0"
          :disabled="saving"
          :error="fieldErrors.sort_order"
        />

        <UiSwitch
          v-model="form.is_active"
          :label="t('admin.categories.fields.isActive')"
          :description="t('admin.categories.edit.isActiveHint')"
          :disabled="saving"
        />

        <p v-if="parentLoadError" class="admin-error text-sm">{{ parentLoadError }}</p>
        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving"
          >
            {{ saving ? t('admin.categories.edit.saving') : t('common.save') }}
          </button>
          <NuxtLink
            :to="`/admin/categories/${route.params.id}`"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.cancel') }}
          </NuxtLink>
        </div>
      </form>
    </article>

    <AdminChangeLogPanel
      v-if="canReadChangeLog"
      model="category"
      :entity-id="String(route.params.id || '')"
      @rolled-back="fetchCategory"
    />

    <AdminActionLogPanel model="category" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import UiSwitch from '~/components/ui/FormControls/UiSwitch/UiSwitch.vue';
import { useDebouncedSearch } from '~/composables/useAsyncSelectOptions';
import type { AdminCategory, UpdateCategoryPayload } from '~/composables/useAdminCategories';
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
  permission: ['admin.categories.read', 'admin.categories.update'],
  permissionMode: 'all',
});

const route = useRoute();
const categoriesApi = useAdminCategories();
const { hasPermission } = usePermissions();
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));

const loading = ref(false);
const loadError = ref('');
const saving = ref(false);
const formError = ref('');
const parentLoading = ref(false);
const parentLoadError = ref('');
const parentCategories = ref<AdminCategory[]>([]);
const slugPreviewLoading = ref(false);
const slugPreviewError = ref('');
const slugTouched = ref(false);
const lastGeneratedSlug = ref('');

const form = reactive({
  name: '',
  slug: '',
  parent_id: '' as string | null,
  sort_order: '0',
  is_active: true,
});

const fieldErrors = reactive<Record<string, string>>({
  name: '',
  slug: '',
  parent_id: '',
  sort_order: '',
});

const currentCategoryId = computed(() => String(route.params.id || ''));

const parentOptions = computed(() => {
  return parentCategories.value
    .filter((category) => category.id !== currentCategoryId.value)
    .map((category) => ({
      value: category.id,
      label: category.name,
    }));
});

const slugHint = computed(() => {
  if (fieldErrors.slug) {
    return '';
  }

  if (slugPreviewError.value) {
    return slugPreviewError.value;
  }

  if (slugPreviewLoading.value) {
    return t('admin.categories.edit.slugLoading');
  }

  return t('admin.categories.edit.slugHint');
});

const resetErrors = () => {
  formError.value = '';
  clearFieldErrors(fieldErrors);
};

const loadParentCategories = async () => {
  parentLoading.value = true;
  parentLoadError.value = '';

  try {
    const tree = await categoriesApi.tree(false);
    parentCategories.value = tree.filter((category) => !category.parent_id);
  } catch (error) {
    parentLoadError.value = getApiErrorMessage(
      error,
      t('admin.categories.edit.errors.loadParents')
    );
  } finally {
    parentLoading.value = false;
  }
};

const applyGeneratedSlug = (slug: string) => {
  form.slug = slug;
  lastGeneratedSlug.value = slug;
  slugTouched.value = false;
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
    const slug = await categoriesApi.previewSlug({
      name,
      parent_id: form.parent_id || null,
      ignore_id: currentCategoryId.value || null,
    });

    applyGeneratedSlug(slug);
  } catch (error) {
    slugPreviewError.value = getApiErrorMessage(
      error,
      t('admin.categories.edit.errors.previewSlug')
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

const fetchCategory = async () => {
  const id = currentCategoryId.value;

  if (!id) {
    loadError.value = t('admin.categories.edit.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    const category = await categoriesApi.show(id);
    form.name = category.name;
    form.slug = category.slug;
    form.parent_id = category.parent_id;
    form.sort_order = String(category.sort_order);
    form.is_active = category.is_active;
    lastGeneratedSlug.value = category.slug;
    slugTouched.value = false;
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.categories.edit.errors.load'));
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    const id = currentCategoryId.value;
    const sortOrder = Number.parseInt(form.sort_order, 10);
    const payload: UpdateCategoryPayload = {
      name: form.name.trim(),
      slug: form.slug.trim(),
      parent_id: form.parent_id || null,
      sort_order: Number.isFinite(sortOrder) && sortOrder >= 0 ? sortOrder : 0,
      is_active: form.is_active,
    };

    await categoriesApi.update(id, payload);
    await navigateTo(`/admin/categories/${id}`);
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.categories.edit.errors.update'));
    applyFieldErrors(fieldErrors, payload.errors);
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await loadParentCategories();
  await fetchCategory();
});

useDebouncedSearch(
  () => [form.name, form.parent_id] as const,
  () => {
    void fetchSlugPreview();
  },
  { delay: 250, skipInitial: true }
);
</script>

<style lang="scss" scoped src="../../_shared/admin-edit-form-page.scss"></style>
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
    width: 42%;
    height: 1.25rem;
  }

  .skeleton-line.is-label {
    width: 28%;
    height: 0.75rem;
  }

  .skeleton-line.is-input {
    width: 100%;
    height: 2.75rem;
    border-radius: 1rem;
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
