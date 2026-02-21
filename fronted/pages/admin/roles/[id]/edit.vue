<template>
  <section class="roles-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.roles.edit.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.roles.edit.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <form v-else class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.code"
          :label="t('admin.roles.edit.fields.code')"
          required
          :disabled="saving || isSystem"
          :error="fieldErrors.code"
        />
        <UiInput
          v-model="form.label"
          :label="t('admin.roles.edit.fields.label')"
          :disabled="saving || isSystem"
          :error="fieldErrors.label"
        />

        <div class="space-y-2">
          <p class="text-sm font-medium">{{ t('admin.permissions.title') }}</p>
          <p class="admin-muted text-xs">{{ t('admin.permissions.roleHint') }}</p>
          <p v-if="loadingPermissions" class="admin-muted text-sm">{{ t('common.loading') }}</p>

          <div
            v-for="group in permissionsByScope"
            v-else
            :key="group.scope"
            class="rounded-lg border border-white/10 p-3"
          >
            <p class="mb-2 text-xs uppercase tracking-wide text-white/60">
              {{ permissionsApi.resolvePermissionScopeLabel(group.scope) }}
            </p>
            <UiCheckbox
              v-for="permission in group.items"
              :key="permission.code"
              class="mb-2 text-sm last:mb-0"
              :label="permissionsApi.resolvePermissionLabel(permission)"
              :model-value="form.permissions.includes(permission.code)"
              :disabled="saving || isSystem"
              @update:model-value="(checked) => onPermissionToggle(permission.code, checked)"
            />
          </div>
        </div>

        <p v-if="isSystem" class="admin-muted text-sm">{{ t('admin.roles.edit.systemLocked') }}</p>
        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving || isSystem"
          >
            {{ saving ? t('admin.roles.edit.saving') : t('common.save') }}
          </button>
          <NuxtLink
            :to="`/admin/roles/${route.params.id}`"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
            >{{ t('common.cancel') }}</NuxtLink
          >
        </div>
      </form>
    </article>

    <AdminChangeLogPanel
      v-if="canReadChangeLog"
      model="role"
      :entity-id="String(route.params.id || '')"
      @rolled-back="onRoleRolledBack"
    />
  </section>
</template>

<script setup lang="ts">
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import UiCheckbox from '~/components/ui/FormControls/UiCheckbox/UiCheckbox.vue';
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import type { AdminAccessPermission } from '~/composables/useAdminPermissions';
import type { UpdateRolePayload } from '~/composables/useAdminRoles';
import {
  getApiErrorPayload,
  getApiErrorMessage,
  getFieldError,
} from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.roles.update',
});

const route = useRoute();
const rolesApi = useAdminRoles();
const permissionsApi = useAdminPermissions();
const { hasPermission } = usePermissions();
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));

const loading = ref(false);
const loadError = ref('');
const saving = ref(false);
const loadingPermissions = ref(false);
const formError = ref('');
const isSystem = ref(false);
const permissions = ref<AdminAccessPermission[]>([]);
const permissionsTouched = ref(false);

const form = reactive({
  code: '',
  label: '',
  permissions: [] as string[],
});

const fieldErrors = reactive<Record<string, string>>({
  code: '',
  label: '',
});

const resetErrors = () => {
  formError.value = '';
  fieldErrors.code = '';
  fieldErrors.label = '';
};

const permissionsByScope = computed(() => {
  const grouped = new Map<string, AdminAccessPermission[]>();

  permissions.value.forEach((permission) => {
    const scope = permission.scope || 'other';
    const bucket = grouped.get(scope) ?? [];
    bucket.push(permission);
    grouped.set(scope, bucket);
  });

  return [...grouped.entries()].map(([scope, items]) => ({
    scope,
    items,
  }));
});

const onPermissionToggle = (code: string, checked: boolean) => {
  permissionsTouched.value = true;

  if (checked) {
    if (!form.permissions.includes(code)) {
      form.permissions = [...form.permissions, code];
    }
    return;
  }

  if (form.permissions.includes(code)) {
    form.permissions = form.permissions.filter((item) => item !== code);
    return;
  }
};

const fetchPermissions = async () => {
  loadingPermissions.value = true;
  try {
    permissions.value = await permissionsApi.list();
  } finally {
    loadingPermissions.value = false;
  }
};

const fetchRole = async () => {
  const id = String(route.params.id || '');

  if (!id) {
    loadError.value = t('admin.roles.edit.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    const role = await rolesApi.show(id);
    form.code = role.code;
    form.label = role.label || '';
    form.permissions = Array.isArray(role.permissions) ? [...role.permissions] : [];
    permissionsTouched.value = false;
    isSystem.value = role.is_system;
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.roles.edit.errors.load'));
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  if (isSystem.value) {
    return;
  }

  saving.value = true;
  resetErrors();

  try {
    const id = String(route.params.id || '');
    const payload: UpdateRolePayload = {
      code: form.code.trim(),
      label: form.label.trim() || null,
    };

    if (permissionsTouched.value || form.permissions.length > 0) {
      payload.permissions = [...form.permissions];
    }

    await rolesApi.update(id, payload);
    await navigateTo(`/admin/roles/${id}`);
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.roles.edit.errors.update'));
    fieldErrors.code = getFieldError(payload.errors, 'code');
    fieldErrors.label = getFieldError(payload.errors, 'label');
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await Promise.all([fetchRole(), fetchPermissions()]);
});

const onRoleRolledBack = async () => {
  await fetchRole();
};
</script>

<style lang="scss" scoped src="./edit.scss"></style>
