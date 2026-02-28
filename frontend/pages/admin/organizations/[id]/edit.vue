<template>
  <section class="mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.organizations.edit.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.organizations.edit.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <form v-else class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.name"
          :label="t('admin.organizations.fields.name')"
          required
          :disabled="saving"
          :error="fieldErrors.name"
        />
        <UiInput
          v-model="form.email"
          :label="t('admin.organizations.fields.email')"
          :disabled="saving"
          :error="fieldErrors.email"
        />
        <UiInput
          v-model="form.phone"
          :label="t('admin.organizations.fields.phone')"
          :disabled="saving"
          :error="fieldErrors.phone"
        />
        <UiInput
          v-model="form.address"
          :label="t('admin.organizations.fields.address')"
          :disabled="saving"
          :error="fieldErrors.address"
        />
        <UiSelect
          v-model="form.owner_user_id"
          :label="t('admin.organizations.fields.ownerUserId')"
          :options="userOptions"
          :placeholder="t('admin.organizations.ownerPlaceholder')"
          searchable
          :disabled="saving"
          :error="fieldErrors.owner_user_id"
          @search="onUserSearch"
        />

        <div class="grid gap-3 sm:grid-cols-2">
          <UiSelect
            v-model="form.status"
            :label="t('admin.organizations.fields.status')"
            :options="statusOptions"
            :placeholder="t('common.dash')"
            :searchable="false"
            :disabled="saving"
            :error="fieldErrors.status"
          />
          <UiSelect
            v-model="form.ownership_status"
            :label="t('admin.organizations.fields.ownershipStatus')"
            :options="ownershipOptions"
            :placeholder="t('common.dash')"
            :searchable="false"
            :disabled="saving"
            :error="fieldErrors.ownership_status"
          />
        </div>

        <UiSelect
          v-model="form.source_type"
          :label="t('admin.organizations.fields.sourceType')"
          :options="sourceTypeOptions"
          :placeholder="t('common.dash')"
          :searchable="false"
          :disabled="saving"
          :error="fieldErrors.source_type"
        />

        <UiTextarea
          v-model="form.description"
          :label="t('admin.organizations.fields.description')"
          :rows="4"
          :disabled="saving"
          :error="fieldErrors.description"
        />

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving"
          >
            {{ saving ? t('admin.organizations.edit.saving') : t('common.save') }}
          </button>
          <NuxtLink
            :to="`/admin/organizations/${route.params.id}`"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.cancel') }}
          </NuxtLink>
        </div>
      </form>
    </article>

    <AdminChangeLogPanel
      v-if="canReadChangeLog"
      model="organization"
      :entity-id="String(route.params.id || '')"
      @rolled-back="onRolledBack"
    />

    <AdminActionLogPanel model="organization" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import UiTextarea from '~/components/ui/FormControls/UiTextarea/UiTextarea.vue';
import { useAdminUserSelectOptions } from '~/composables/useAdminUserSelectOptions';
import type {
  OrganizationOwnershipStatus,
  OrganizationSourceType,
  OrganizationStatus,
  UpdateOrganizationPayload,
} from '~/composables/useAdminOrganizations';
import {
  getApiErrorMessage,
  getApiErrorPayload,
  getFieldError,
} from '~/composables/useAdminCrudCommon';

const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: ['org.company.profile.read', 'org.company.profile.update'],
  permissionMode: 'all',
});

const route = useRoute();
const organizationsApi = useAdminOrganizations();
const { userOptions, loadUserOptions, onUserSearch, ensureSelectedUserOption } =
  useAdminUserSelectOptions();
const { hasPermission } = usePermissions();
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));

const loading = ref(false);
const loadError = ref('');
const saving = ref(false);
const formError = ref('');

const form = reactive({
  name: '',
  description: '',
  address: '',
  phone: '',
  email: '',
  status: '' as OrganizationStatus | '',
  source_type: '' as OrganizationSourceType | '',
  ownership_status: '' as OrganizationOwnershipStatus | '',
  owner_user_id: '',
});

const fieldErrors = reactive<Record<string, string>>({
  name: '',
  description: '',
  address: '',
  phone: '',
  email: '',
  status: '',
  source_type: '',
  ownership_status: '',
  owner_user_id: '',
});

const statusOptions = computed(() => [
  { value: 'draft', label: t('admin.organizations.status.draft') },
  { value: 'active', label: t('admin.organizations.status.active') },
  { value: 'suspended', label: t('admin.organizations.status.suspended') },
  { value: 'archived', label: t('admin.organizations.status.archived') },
]);

const sourceTypeOptions = computed(() => [
  { value: 'manual', label: t('admin.organizations.source.manual') },
  { value: 'import', label: t('admin.organizations.source.import') },
  { value: 'parsed', label: t('admin.organizations.source.parsed') },
  { value: 'self_registered', label: t('admin.organizations.source.selfRegistered') },
]);

const ownershipOptions = computed(() => [
  { value: 'unclaimed', label: t('admin.organizations.ownership.unclaimed') },
  { value: 'pending_claim', label: t('admin.organizations.ownership.pendingClaim') },
  { value: 'claimed', label: t('admin.organizations.ownership.claimed') },
]);

const resetErrors = () => {
  formError.value = '';
  Object.keys(fieldErrors).forEach((key) => {
    fieldErrors[key] = '';
  });
};

const loadOrganization = async () => {
  const id = String(route.params.id || '');

  if (!id) {
    loadError.value = t('admin.organizations.edit.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    const organization = await organizationsApi.show(id);

    form.name = organization.name;
    form.description = organization.description || '';
    form.address = organization.address || '';
    form.phone = organization.phone || '';
    form.email = organization.email || '';
    form.status = (organization.status as OrganizationStatus | null) || '';
    form.source_type = (organization.source_type as OrganizationSourceType | null) || '';
    form.ownership_status =
      (organization.ownership_status as OrganizationOwnershipStatus | null) || '';
    form.owner_user_id = organization.owner_user_id || '';
    await ensureSelectedUserOption(form.owner_user_id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.organizations.edit.errors.load'));
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  const id = String(route.params.id || '');
  if (!id) {
    formError.value = t('admin.organizations.edit.errors.invalidId');
    return;
  }

  saving.value = true;
  resetErrors();

  try {
    const payload: UpdateOrganizationPayload = {
      name: form.name.trim(),
      description: form.description.trim() || null,
      address: form.address.trim() || null,
      phone: form.phone.trim() || null,
      email: form.email.trim() || null,
      status: form.status || null,
      source_type: form.source_type || null,
      ownership_status: form.ownership_status || null,
      owner_user_id: form.owner_user_id.trim() || null,
    };

    await organizationsApi.update(id, payload);
    await navigateTo(`/admin/organizations/${id}`);
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.organizations.edit.errors.update'));
    fieldErrors.name = getFieldError(payload.errors, 'name');
    fieldErrors.description = getFieldError(payload.errors, 'description');
    fieldErrors.address = getFieldError(payload.errors, 'address');
    fieldErrors.phone = getFieldError(payload.errors, 'phone');
    fieldErrors.email = getFieldError(payload.errors, 'email');
    fieldErrors.status = getFieldError(payload.errors, 'status');
    fieldErrors.source_type = getFieldError(payload.errors, 'source_type');
    fieldErrors.ownership_status = getFieldError(payload.errors, 'ownership_status');
    fieldErrors.owner_user_id = getFieldError(payload.errors, 'owner_user_id');
  } finally {
    saving.value = false;
  }
};

const onRolledBack = async () => {
  await loadOrganization();
};

onMounted(async () => {
  await loadUserOptions('');
  await loadOrganization();
});
</script>
