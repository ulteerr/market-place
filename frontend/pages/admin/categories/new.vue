<template>
  <section class="admin-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.categories.new.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.categories.new.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <form class="space-y-3" @submit.prevent="submitForm">
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
              {{ t('admin.categories.new.slugAutofill') }}
            </button>
          </template>
        </UiInput>

        <UiSelect
          v-model="form.parent_id"
          :label="t('admin.categories.fields.parent')"
          :options="parentOptions"
          :placeholder="t('admin.categories.new.parentPlaceholder')"
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
          :description="t('admin.categories.new.isActiveHint')"
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
            {{ saving ? t('admin.categories.new.saving') : t('common.create') }}
          </button>
          <NuxtLink
            to="/admin/categories"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.cancel') }}
          </NuxtLink>
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import UiSwitch from '~/components/ui/FormControls/UiSwitch/UiSwitch.vue';
import { useDebouncedSearch } from '~/composables/useAsyncSelectOptions';
import type { AdminCategory, CreateCategoryPayload } from '~/composables/useAdminCategories';
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
  permission: 'admin.categories.create',
});

const categoriesApi = useAdminCategories();

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

const parentOptions = computed(() => {
  return parentCategories.value.map((category) => ({
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
    return t('admin.categories.new.slugLoading');
  }

  return t('admin.categories.new.slugHint');
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
    parentLoadError.value = getApiErrorMessage(error, t('admin.categories.new.errors.loadParents'));
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
    });

    applyGeneratedSlug(slug);
  } catch (error) {
    slugPreviewError.value = getApiErrorMessage(
      error,
      t('admin.categories.new.errors.previewSlug')
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

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    const sortOrder = Number.parseInt(form.sort_order, 10);
    const payload: CreateCategoryPayload = {
      name: form.name.trim(),
      slug: form.slug.trim(),
      parent_id: form.parent_id || null,
      sort_order: Number.isFinite(sortOrder) && sortOrder >= 0 ? sortOrder : 0,
      is_active: form.is_active,
    };

    await categoriesApi.create(payload);
    await navigateTo('/admin/categories');
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.categories.new.errors.create'));
    applyFieldErrors(fieldErrors, payload.errors);
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await loadParentCategories();
  await fetchSlugPreview();
});

useDebouncedSearch(
  () => [form.name, form.parent_id] as const,
  () => {
    void fetchSlugPreview();
  },
  { delay: 250, skipInitial: true }
);
</script>

<style lang="scss" scoped src="../_shared/admin-form-page.scss"></style>
