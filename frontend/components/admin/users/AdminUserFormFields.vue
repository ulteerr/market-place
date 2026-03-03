<template>
  <UiInput
    v-model="form.first_name"
    :label="t(`admin.users.${modeI18nKey}.fields.firstName`)"
    required
    :disabled="saving"
    :error="fieldErrors.first_name"
  />
  <UiInput
    v-model="form.last_name"
    :label="t(`admin.users.${modeI18nKey}.fields.lastName`)"
    required
    :disabled="saving"
    :error="fieldErrors.last_name"
  />
  <UiInput
    v-model="form.middle_name"
    :label="t(`admin.users.${modeI18nKey}.fields.middleName`)"
    :disabled="saving"
    :error="fieldErrors.middle_name"
  />
  <UiSelect
    v-model="form.gender"
    :label="t(`admin.users.${modeI18nKey}.fields.gender`)"
    :options="genderOptions"
    :placeholder="t(`admin.users.${modeI18nKey}.genderPlaceholder`)"
    clearable
    :disabled="saving"
    :error="fieldErrors.gender"
  />
  <UiInput
    v-model="form.email"
    preset="email"
    :label="t(`admin.users.${modeI18nKey}.fields.email`)"
    required
    :disabled="saving"
    :error="fieldErrors.email"
  />
  <UiInput
    v-model="form.phone"
    preset="phone"
    :label="t(`admin.users.${modeI18nKey}.fields.phone`)"
    :disabled="saving"
    :error="fieldErrors.phone"
  />
  <UiInput
    v-model="form.password"
    preset="password"
    password-toggle
    :label="
      mode === 'create'
        ? t('admin.users.new.fields.password')
        : t('admin.users.edit.fields.newPassword')
    "
    :required="mode === 'create'"
    :disabled="saving"
    :error="fieldErrors.password"
  />
  <UiInput
    v-model="form.password_confirmation"
    preset="password"
    password-toggle
    :label="
      mode === 'create'
        ? t('admin.users.new.fields.passwordConfirmation')
        : t('admin.users.edit.fields.newPasswordConfirmation')
    "
    :required="mode === 'create'"
    :disabled="saving"
  />

  <UiSelect
    v-model="form.roles"
    :label="t(`admin.users.${modeI18nKey}.fields.roles`)"
    :options="roleOptions"
    :locked-values="[participantRoleCode]"
    :placeholder="t(`admin.users.${modeI18nKey}.rolesPlaceholder`)"
    multiple
    searchable
    :disabled="saving || loadingRoles"
    :error="fieldErrors.roles"
  />

  <div v-if="showPermissionOverrides" class="space-y-3">
    <p class="text-sm font-medium">{{ t('admin.permissions.userTitle') }}</p>
    <p class="admin-muted text-xs">{{ t('admin.permissions.userHint') }}</p>
    <p v-if="loadingPermissions" class="admin-muted text-sm">{{ t('common.loading') }}</p>

    <div v-for="group in permissionsByScope" v-else :key="group.scope" class="space-y-2">
      <p class="text-xs uppercase tracking-wide text-white/60">
        {{ resolvePermissionScopeLabel(group.scope) }}
      </p>
      <div class="rounded-lg border border-white/10 p-3">
        <div
          v-for="permission in group.items"
          :key="permission.code"
          class="mb-2 grid grid-cols-[1fr_auto_auto] items-center gap-3 text-sm last:mb-0"
        >
          <span>{{ resolvePermissionLabel(permission) }}</span>
          <UiCheckbox
            class="justify-self-end"
            :label="t('admin.permissions.allow')"
            :model-value="form.permission_overrides_allow.includes(permission.code)"
            :disabled="saving"
            @update:model-value="(checked) => $emit('override-allow', permission.code, checked)"
          />
          <UiCheckbox
            class="justify-self-end"
            :label="t('admin.permissions.deny')"
            :model-value="form.permission_overrides_deny.includes(permission.code)"
            :disabled="saving"
            @update:model-value="(checked) => $emit('override-deny', permission.code, checked)"
          />
        </div>
      </div>
    </div>
  </div>

  <UiImageBlock
    v-if="mode === 'edit' && avatarImages.length"
    title=""
    :images="avatarImages"
    :show-add-button="false"
    :removable="!saving"
    :remove-button-text="t('admin.actions.delete')"
    :empty-text="t('common.dash')"
    :caption-prefix="t('admin.profile.avatar.previewAlt')"
    @remove="$emit('clear-avatar')"
  />

  <UiImageDropzone
    v-model="avatarDraftFilesModel"
    :title="t(`admin.users.${modeI18nKey}.fields.avatar`)"
    :description="t('admin.files.avatarHint')"
    :browse-button-text="t(`admin.users.${modeI18nKey}.fields.avatar`)"
    accept="image/png,image/jpeg,image/webp"
    :multiple="false"
    :disabled="saving"
    @files-added="(files) => $emit('avatar-files-added', files)"
  />

  <UiImageBlock
    v-if="mode === 'create' && avatarImages.length"
    title=""
    :images="avatarImages"
    :show-add-button="false"
    :removable="!saving"
    :remove-button-text="t('admin.actions.delete')"
    :empty-text="t('common.dash')"
    :caption-prefix="t('admin.profile.avatar.previewAlt')"
    @remove="$emit('clear-avatar')"
  />

  <p v-if="avatarError" class="admin-error text-sm">{{ avatarError }}</p>

  <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>
</template>

<script setup lang="ts">
import UiCheckbox from '~/components/ui/FormControls/UiCheckbox/UiCheckbox.vue';
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import UiImageBlock from '~/components/ui/ImageBlock/UiImageBlock/UiImageBlock.vue';
import UiImageDropzone from '~/components/ui/ImageBlock/UiImageDropzone/UiImageDropzone.vue';
import type { AdminAccessPermission } from '~/composables/useAdminPermissions';
import type { AdminUserFormModel } from '~/composables/useAdminUserForm';

interface OptionItem {
  value: string;
  label: string;
  disabled?: boolean;
}

interface PermissionGroup {
  scope: string;
  items: AdminAccessPermission[];
}

interface AvatarImageItem {
  id: string;
  src: string;
  alt: string;
  caption?: string;
}

const props = defineProps<{
  mode: 'create' | 'edit';
  form: AdminUserFormModel;
  fieldErrors: Record<string, string>;
  saving: boolean;
  loadingRoles: boolean;
  loadingPermissions: boolean;
  genderOptions: OptionItem[];
  roleOptions: OptionItem[];
  participantRoleCode: string;
  showPermissionOverrides: boolean;
  permissionsByScope: PermissionGroup[];
  avatarDraftFiles: File[];
  avatarImages: AvatarImageItem[];
  avatarError: string;
  formError: string;
  resolvePermissionScopeLabel: (scope: string) => string;
  resolvePermissionLabel: (permission: AdminAccessPermission) => string;
}>();

const emit = defineEmits<{
  'update:avatarDraftFiles': [files: File[]];
  'avatar-files-added': [files: File[]];
  'clear-avatar': [];
  'override-allow': [code: string, checked: boolean];
  'override-deny': [code: string, checked: boolean];
}>();

const { t } = useI18n();
const modeI18nKey = computed(() => (props.mode === 'create' ? 'new' : 'edit'));

const avatarDraftFilesModel = computed({
  get: () => props.avatarDraftFiles,
  set: (files: File[]) => emit('update:avatarDraftFiles', files),
});
</script>
