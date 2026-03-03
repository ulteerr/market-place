import type { AdminAccessPermission } from '~/composables/useAdminPermissions';
import type { AdminRole } from '~/composables/useAdminRoles';
import { getHighestRoleLevelFromCodes, getRoleLevel } from '~/composables/useAdminUsers';
import {
  applyFieldErrors,
  clearFieldErrors,
  getFieldError,
  type ApiErrorPayload,
} from '~/composables/useAdminCrudCommon';

export type AdminUserFormMode = 'create' | 'edit';

export interface AdminUserFormModel {
  first_name: string;
  last_name: string;
  middle_name: string;
  gender: 'male' | 'female' | '';
  email: string;
  phone: string;
  password: string;
  password_confirmation: string;
  roles: string[];
  permission_overrides_allow: string[];
  permission_overrides_deny: string[];
}

export interface AdminUserFormAvatar {
  id: string;
  url: string;
}

interface UseAdminUserFormOptions {
  mode: AdminUserFormMode;
  initialRoles?: string[];
}

export const useAdminUserForm = (options: UseAdminUserFormOptions) => {
  const { t } = useI18n();
  const { user: authUser } = useAuth();
  const rolesApi = useAdminRoles();
  const permissionsApi = useAdminPermissions();

  const saving = ref(false);
  const loadingRoles = ref(false);
  const loadingPermissions = ref(false);
  const formError = ref('');
  const avatarError = ref('');
  const roles = ref<AdminRole[]>([]);
  const permissions = ref<AdminAccessPermission[]>([]);
  const avatarDraftFiles = ref<File[]>([]);
  const avatarFile = ref<File | null>(null);
  const avatarPreviewUrl = ref<string | null>(null);
  const avatarDeleted = ref(false);
  const existingAvatar = ref<AdminUserFormAvatar | null>(null);

  const form = reactive<AdminUserFormModel>({
    first_name: '',
    last_name: '',
    middle_name: '',
    gender: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    roles: [...(options.initialRoles ?? ['participant'])],
    permission_overrides_allow: [],
    permission_overrides_deny: [],
  });

  const PARTICIPANT_ROLE_CODE = 'participant';
  const actorMaxRoleLevel = computed(() =>
    getHighestRoleLevelFromCodes(Array.isArray(authUser.value?.roles) ? authUser.value.roles : [])
  );

  const normalizeUserRoleCodes = (
    rawRoles: Array<string | { code?: string | null }> = []
  ): string[] => {
    return rawRoles
      .map((role) => (typeof role === 'string' ? role : (role?.code ?? '')))
      .filter((role): role is string => typeof role === 'string' && role.length > 0);
  };

  const normalizeAssignableRoles = (roleCodes: string[]): string[] => {
    const unique = [
      ...new Set(roleCodes.filter((role) => typeof role === 'string' && role.length > 0)),
    ];

    if (!unique.includes(PARTICIPANT_ROLE_CODE)) {
      unique.push(PARTICIPANT_ROLE_CODE);
    }

    return unique.filter((role) => getRoleLevel(role) <= actorMaxRoleLevel.value);
  };

  const fieldErrors = reactive<Record<string, string>>({
    first_name: '',
    last_name: '',
    middle_name: '',
    gender: '',
    email: '',
    phone: '',
    password: '',
    roles: '',
  });

  const genderOptions = computed(() => [
    { value: 'male', label: t('admin.genders.male') },
    { value: 'female', label: t('admin.genders.female') },
  ]);

  const roleOptions = computed(() => {
    return roles.value.map((role) => ({
      label: role.label ? `${role.code} (${role.label})` : role.code,
      value: role.code,
      disabled:
        role.code === PARTICIPANT_ROLE_CODE || getRoleLevel(role.code) > actorMaxRoleLevel.value,
    }));
  });

  const showPermissionOverrides = computed(() =>
    form.roles.some((roleCode) => roleCode !== PARTICIPANT_ROLE_CODE)
  );

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

  const avatarImages = computed(() =>
    avatarPreviewUrl.value
      ? [
          {
            id: options.mode === 'create' ? 'new-avatar' : 'draft-avatar',
            src: avatarPreviewUrl.value,
            alt: t('admin.profile.avatar.previewAlt'),
            caption: t('admin.profile.avatar.previewAlt'),
          },
        ]
      : existingAvatar.value
        ? [
            {
              id: existingAvatar.value.id,
              src: existingAvatar.value.url,
              alt: t('admin.profile.avatar.previewAlt'),
              caption: t('admin.profile.avatar.previewAlt'),
            },
          ]
        : []
  );

  const setAvatarDraft = (file: File | null) => {
    if (avatarPreviewUrl.value) {
      URL.revokeObjectURL(avatarPreviewUrl.value);
      avatarPreviewUrl.value = null;
    }

    avatarFile.value = file;
    avatarDraftFiles.value = file ? [file] : [];

    if (file) {
      avatarPreviewUrl.value = URL.createObjectURL(file);
    }
  };

  const clearErrors = () => {
    formError.value = '';
    avatarError.value = '';
    clearFieldErrors(fieldErrors);
  };

  const applyApiErrors = (payload: ApiErrorPayload) => {
    applyFieldErrors(fieldErrors, payload.errors, {
      roles: ['roles', 'roles.0'],
    });
    avatarError.value = getFieldError(payload.errors, 'avatar');
  };

  const onAvatarFilesAdded = (files: File[]) => {
    avatarError.value = '';
    avatarDeleted.value = false;
    setAvatarDraft(files[0] ?? null);
  };

  const clearAvatar = () => {
    if (avatarFile.value) {
      setAvatarDraft(null);
      return;
    }

    if (existingAvatar.value) {
      existingAvatar.value = null;
      avatarDeleted.value = true;
    }
  };

  const fetchRoles = async () => {
    loadingRoles.value = true;

    try {
      const page = await rolesApi.list({
        per_page: 100,
        sort_by: 'code',
        sort_dir: 'asc',
      });
      roles.value = page.data;
    } finally {
      loadingRoles.value = false;
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

  const fetchFormOptions = async () => {
    await Promise.all([fetchRoles(), fetchPermissions()]);
  };

  const onOverrideAllowToggle = (code: string, checked: boolean) => {
    if (checked) {
      if (!form.permission_overrides_allow.includes(code)) {
        form.permission_overrides_allow = [...form.permission_overrides_allow, code];
      }
      form.permission_overrides_deny = form.permission_overrides_deny.filter(
        (item) => item !== code
      );
      return;
    }

    form.permission_overrides_allow = form.permission_overrides_allow.filter(
      (item) => item !== code
    );
  };

  const onOverrideDenyToggle = (code: string, checked: boolean) => {
    if (checked) {
      if (!form.permission_overrides_deny.includes(code)) {
        form.permission_overrides_deny = [...form.permission_overrides_deny, code];
      }
      form.permission_overrides_allow = form.permission_overrides_allow.filter(
        (item) => item !== code
      );
      return;
    }

    form.permission_overrides_deny = form.permission_overrides_deny.filter((item) => item !== code);
  };

  const setExistingAvatar = (avatar: AdminUserFormAvatar | null) => {
    existingAvatar.value = avatar;
    avatarDeleted.value = false;
  };

  watch(avatarDraftFiles, (nextFiles) => {
    const nextFile = nextFiles[0] ?? null;
    if (nextFile !== avatarFile.value) {
      onAvatarFilesAdded(nextFile ? [nextFile] : []);
    }
  });

  watch(
    () => form.roles,
    (nextRoles) => {
      const normalized = normalizeAssignableRoles(nextRoles);

      if (normalized.join('|') !== nextRoles.join('|')) {
        form.roles = normalized;
      }
    },
    { deep: true, immediate: true }
  );

  onBeforeUnmount(() => {
    if (avatarPreviewUrl.value) {
      URL.revokeObjectURL(avatarPreviewUrl.value);
    }
  });

  return {
    permissionsApi,
    PARTICIPANT_ROLE_CODE,
    form,
    fieldErrors,
    formError,
    avatarError,
    saving,
    loadingRoles,
    loadingPermissions,
    roles,
    permissions,
    genderOptions,
    roleOptions,
    showPermissionOverrides,
    permissionsByScope,
    avatarDraftFiles,
    avatarFile,
    avatarDeleted,
    avatarImages,
    existingAvatar,
    normalizeUserRoleCodes,
    normalizeAssignableRoles,
    actorMaxRoleLevel,
    clearErrors,
    applyApiErrors,
    onAvatarFilesAdded,
    clearAvatar,
    onOverrideAllowToggle,
    onOverrideDenyToggle,
    fetchRoles,
    fetchPermissions,
    fetchFormOptions,
    setExistingAvatar,
  };
};
