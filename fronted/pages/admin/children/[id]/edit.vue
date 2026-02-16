<template>
  <section class="mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.children.edit.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.children.edit.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <form v-else class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.last_name"
          :label="t('admin.children.edit.fields.lastName')"
          required
          :disabled="saving"
          :error="fieldErrors.last_name"
        />
        <UiInput
          v-model="form.first_name"
          :label="t('admin.children.edit.fields.firstName')"
          required
          :disabled="saving"
          :error="fieldErrors.first_name"
        />
        <UiInput
          v-model="form.middle_name"
          :label="t('admin.children.edit.fields.middleName')"
          :disabled="saving"
          :error="fieldErrors.middle_name"
        />
        <UiSelect
          v-model="form.gender"
          :label="t('admin.children.edit.fields.gender')"
          :options="genderOptions"
          :placeholder="t('admin.children.edit.genderPlaceholder')"
          :searchable="false"
          :disabled="saving"
          :error="fieldErrors.gender"
        />
        <UiDatePicker
          v-model="form.birth_date"
          mode="single"
          :label="t('admin.children.edit.fields.birthDate')"
          :disabled="saving"
          :error="fieldErrors.birth_date"
        />
        <UiSelect
          v-model="form.user_id"
          :label="t('admin.children.edit.fields.userId')"
          :options="userOptions"
          :placeholder="t('admin.children.edit.userPlaceholder')"
          searchable
          required
          :disabled="saving"
          :error="fieldErrors.user_id"
          @search="onUserSearch"
        />

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving"
          >
            {{ saving ? t('admin.children.edit.saving') : t('common.save') }}
          </button>
          <NuxtLink
            :to="`/admin/children/${route.params.id}`"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
            >{{ t('common.cancel') }}</NuxtLink
          >
        </div>
      </form>
    </article>

    <AdminChangeLogPanel
      v-if="canReadChangeLog"
      model="child"
      :entity-id="String(route.params.id || '')"
      @rolled-back="onChildRolledBack"
    />
  </section>
</template>

<script setup lang="ts">
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import UiDatePicker from '~/components/ui/FormControls/UiDatePicker.vue';
import UiInput from '~/components/ui/FormControls/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect.vue';
import type { UpdateChildPayload } from '~/composables/useAdminChildren';
import type { AdminUser } from '~/composables/useAdminUsers';
import {
  getApiErrorPayload,
  getApiErrorMessage,
  getFieldError,
} from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: ['org.children.read', 'org.children.write'],
  permissionMode: 'all',
});

const route = useRoute();
const childrenApi = useAdminChildren();
const usersApi = useAdminUsers();
const { hasPermission } = usePermissions();
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));

const loading = ref(false);
const loadError = ref('');
const saving = ref(false);
const formError = ref('');
const loadingUsers = ref(false);
const userOptions = ref<Array<{ label: string; value: string }>>([]);
let userSearchTimer: ReturnType<typeof setTimeout> | null = null;

const form = reactive({
  user_id: '' as string | null,
  first_name: '',
  last_name: '',
  middle_name: '',
  gender: '' as string | null,
  birth_date: '' as string | null,
});

const fieldErrors = reactive<Record<string, string>>({
  user_id: '',
  first_name: '',
  last_name: '',
  middle_name: '',
  gender: '',
  birth_date: '',
});

const genderOptions = computed(() => [
  { value: 'male', label: t('admin.genders.male') },
  { value: 'female', label: t('admin.genders.female') },
]);

const normalizeIsoDate = (value: string | null | undefined): string => {
  if (!value || typeof value !== 'string') {
    return '';
  }

  const match = value.match(/^(\d{4}-\d{2}-\d{2})/);
  if (match) {
    return match[1];
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return '';
  }

  return parsed.toISOString().slice(0, 10);
};

const resolveUserLabel = (user: AdminUser): string => {
  const fullName = [user.first_name, user.last_name, user.middle_name]
    .filter((part): part is string => typeof part === 'string' && part.trim().length > 0)
    .join(' ');

  const title = fullName || user.email || user.id;
  return `${title} - ${user.id}`;
};

const loadUserOptions = async (search = '') => {
  loadingUsers.value = true;

  try {
    const payload = await usersApi.list({
      per_page: 20,
      search: search.trim() || undefined,
      sort_by: 'last_name',
      sort_dir: 'asc',
    });

    userOptions.value = payload.data.map((user) => ({
      value: user.id,
      label: resolveUserLabel(user),
    }));
  } finally {
    loadingUsers.value = false;
  }
};

const ensureSelectedUserOption = async (userId: string) => {
  if (!userId || userOptions.value.some((option) => option.value === userId)) {
    return;
  }

  await loadUserOptions(userId);
};

const onUserSearch = (query: string) => {
  if (userSearchTimer) {
    clearTimeout(userSearchTimer);
  }

  userSearchTimer = setTimeout(() => {
    loadUserOptions(query);
  }, 250);
};

const resetErrors = () => {
  formError.value = '';
  fieldErrors.user_id = '';
  fieldErrors.first_name = '';
  fieldErrors.last_name = '';
  fieldErrors.middle_name = '';
  fieldErrors.gender = '';
  fieldErrors.birth_date = '';
};

const fetchChild = async () => {
  const id = String(route.params.id || '');

  if (!id) {
    loadError.value = t('admin.children.edit.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    const child = await childrenApi.show(id);
    form.user_id = child.user_id;
    form.first_name = child.first_name;
    form.last_name = child.last_name;
    form.middle_name = child.middle_name || '';
    form.gender = child.gender || null;
    form.birth_date = normalizeIsoDate(child.birth_date);
    await ensureSelectedUserOption(child.user_id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.children.edit.errors.load'));
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    const id = String(route.params.id || '');
    const payload: UpdateChildPayload = {
      user_id: String(form.user_id || '').trim(),
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      middle_name: form.middle_name.trim() || null,
      gender: form.gender || null,
      birth_date: form.birth_date || null,
    };

    await childrenApi.update(id, payload);
    await navigateTo(`/admin/children/${id}`);
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.children.edit.errors.update'));
    fieldErrors.user_id = getFieldError(payload.errors, 'user_id');
    fieldErrors.first_name = getFieldError(payload.errors, 'first_name');
    fieldErrors.last_name = getFieldError(payload.errors, 'last_name');
    fieldErrors.middle_name = getFieldError(payload.errors, 'middle_name');
    fieldErrors.gender = getFieldError(payload.errors, 'gender');
    fieldErrors.birth_date = getFieldError(payload.errors, 'birth_date');
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await loadUserOptions('');
  await fetchChild();
});

onBeforeUnmount(() => {
  if (userSearchTimer) {
    clearTimeout(userSearchTimer);
    userSearchTimer = null;
  }
});

const onChildRolledBack = async () => {
  await fetchChild();
};
</script>
