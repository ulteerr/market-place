<template>
  <section class="mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.children.new.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.children.new.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <form class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.last_name"
          :label="t('admin.children.new.fields.lastName')"
          required
          :disabled="saving"
          :error="fieldErrors.last_name"
        />
        <UiInput
          v-model="form.first_name"
          :label="t('admin.children.new.fields.firstName')"
          required
          :disabled="saving"
          :error="fieldErrors.first_name"
        />
        <UiInput
          v-model="form.middle_name"
          :label="t('admin.children.new.fields.middleName')"
          :disabled="saving"
          :error="fieldErrors.middle_name"
        />
        <UiSelect
          v-model="form.gender"
          :label="t('admin.children.new.fields.gender')"
          :options="genderOptions"
          :placeholder="t('admin.children.new.genderPlaceholder')"
          :searchable="false"
          :disabled="saving"
          :error="fieldErrors.gender"
        />
        <UiDatePicker
          v-model="form.birth_date"
          mode="single"
          :label="t('admin.children.new.fields.birthDate')"
          :disabled="saving"
          :error="fieldErrors.birth_date"
        />
        <UiSelect
          v-model="form.user_id"
          :label="t('admin.children.new.fields.userId')"
          :options="userOptions"
          :placeholder="t('admin.children.new.userPlaceholder')"
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
            {{ saving ? t('admin.children.new.saving') : t('common.create') }}
          </button>
          <NuxtLink
            to="/admin/children"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
            >{{ t('common.cancel') }}</NuxtLink
          >
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiDatePicker from '~/components/ui/FormControls/UiDatePicker.vue';
import UiInput from '~/components/ui/FormControls/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect.vue';
import type { CreateChildPayload } from '~/composables/useAdminChildren';
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
  permission: 'org.children.write',
});

const childrenApi = useAdminChildren();
const usersApi = useAdminUsers();

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
  gender: null as 'male' | 'female' | null,
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

const resetErrors = () => {
  formError.value = '';
  fieldErrors.user_id = '';
  fieldErrors.first_name = '';
  fieldErrors.last_name = '';
  fieldErrors.middle_name = '';
  fieldErrors.gender = '';
  fieldErrors.birth_date = '';
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

const onUserSearch = (query: string) => {
  if (userSearchTimer) {
    clearTimeout(userSearchTimer);
  }

  userSearchTimer = setTimeout(() => {
    loadUserOptions(query);
  }, 250);
};

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    const payload: CreateChildPayload = {
      user_id: String(form.user_id || '').trim(),
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      middle_name: form.middle_name.trim() || null,
      gender: form.gender || null,
      birth_date: form.birth_date || null,
    };

    await childrenApi.create(payload);
    await navigateTo('/admin/children');
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.children.new.errors.create'));
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
});

onBeforeUnmount(() => {
  if (userSearchTimer) {
    clearTimeout(userSearchTimer);
    userSearchTimer = null;
  }
});
</script>
