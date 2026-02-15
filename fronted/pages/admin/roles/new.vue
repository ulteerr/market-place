<template>
  <section class="roles-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.roles.new.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.roles.new.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <form class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.code"
          :label="t('admin.roles.new.fields.code')"
          required
          :disabled="saving"
          :error="fieldErrors.code"
        />
        <UiInput
          v-model="form.label"
          :label="t('admin.roles.new.fields.label')"
          :disabled="saving"
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
              :disabled="saving"
              @update:model-value="(checked) => onPermissionToggle(permission.code, checked)"
            />
          </div>
        </div>

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving"
          >
            {{ saving ? t('admin.roles.new.saving') : t('common.create') }}
          </button>
          <NuxtLink to="/admin/roles" class="admin-button-secondary rounded-lg px-4 py-2 text-sm">{{
            t('common.cancel')
          }}</NuxtLink>
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiCheckbox from '~/components/ui/FormControls/UiCheckbox.vue';
import UiInput from '~/components/ui/FormControls/UiInput.vue';
import type { AdminAccessPermission } from '~/composables/useAdminPermissions';
import type { CreateRolePayload } from '~/composables/useAdminRoles';
import {
  getApiErrorPayload,
  getApiErrorMessage,
  getFieldError,
} from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.roles.create',
});

const rolesApi = useAdminRoles();
const permissionsApi = useAdminPermissions();

const saving = ref(false);
const loadingPermissions = ref(false);
const formError = ref('');
const permissions = ref<AdminAccessPermission[]>([]);

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
  if (checked) {
    if (!form.permissions.includes(code)) {
      form.permissions = [...form.permissions, code];
    }
    return;
  }

  form.permissions = form.permissions.filter((item) => item !== code);
};

const fetchPermissions = async () => {
  loadingPermissions.value = true;
  try {
    permissions.value = await permissionsApi.list();
  } finally {
    loadingPermissions.value = false;
  }
};

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    const payload: CreateRolePayload = {
      code: form.code.trim(),
      label: form.label.trim() || null,
    };

    if (form.permissions.length > 0) {
      payload.permissions = [...form.permissions];
    }

    await rolesApi.create(payload);
    await navigateTo('/admin/roles');
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.roles.new.errors.create'));
    fieldErrors.code = getFieldError(payload.errors, 'code');
    fieldErrors.label = getFieldError(payload.errors, 'label');
  } finally {
    saving.value = false;
  }
};

onMounted(fetchPermissions);
</script>

<style lang="scss" scoped src="./new.scss"></style>
