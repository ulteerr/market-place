<template>
  <section class="users-show-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.users.show.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.users.show.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="user">
        <div class="mb-4 flex items-center gap-3">
          <div class="user-show-avatar">
            <img
              v-if="user.avatar?.url"
              :src="user.avatar.url"
              :alt="getAdminUserFullName(user)"
              class="user-show-avatar-image"
            />
            <span v-else class="user-show-avatar-fallback">{{ userInitials }}</span>
          </div>
          <p class="text-sm font-medium">{{ getAdminUserFullName(user) }}</p>
        </div>

        <dl class="grid gap-3 sm:grid-cols-2">
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.users.show.labels.firstName') }}</dt>
            <dd>{{ user.first_name }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.users.show.labels.lastName') }}</dt>
            <dd>{{ user.last_name }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.users.show.labels.middleName') }}</dt>
            <dd>{{ user.middle_name || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.users.show.labels.gender') }}</dt>
            <dd>{{ resolveGenderLabel(user.gender) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.users.show.labels.email') }}</dt>
            <dd>{{ user.email }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.users.show.labels.phone') }}</dt>
            <dd>{{ user.phone || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.users.show.labels.roles') }}</dt>
            <dd>{{ (user.roles || []).join(', ') || t('common.dash') }}</dd>
          </div>
        </dl>

        <div class="mt-5 flex gap-2">
          <NuxtLink
            v-if="canEditViewedUser"
            :to="`/admin/users/${user.id}/edit`"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            >{{ t('common.edit') }}</NuxtLink
          >
          <NuxtLink to="/admin/users" class="admin-button-secondary rounded-lg px-4 py-2 text-sm">{{
            t('common.backToList')
          }}</NuxtLink>
        </div>
      </template>
    </article>

    <AdminChangeLogPanel
      v-if="canReadChangeLog"
      model="user"
      :entity-id="user?.id || String(route.params.id || '')"
      @rolled-back="onUserRolledBack"
    />
  </section>
</template>

<script setup lang="ts">
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import type { AdminUser } from '~/composables/useAdminUsers';
import {
  getAdminUserFullName,
  getHighestRoleLevelForUser,
  getHighestRoleLevelFromCodes,
} from '~/composables/useAdminUsers';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.users.read',
});

const route = useRoute();
const usersApi = useAdminUsers();
const { user: authUser } = useAuth();
const { hasPermission } = usePermissions();
const canUpdateUsers = computed(() => hasPermission('admin.users.update'));
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));
const actorMaxRoleLevel = computed(() =>
  getHighestRoleLevelFromCodes(Array.isArray(authUser.value?.roles) ? authUser.value.roles : [])
);

const user = ref<AdminUser | null>(null);
const loading = ref(false);
const loadError = ref('');

const fetchUser = async () => {
  const id = String(route.params.id || '');

  if (!id) {
    loadError.value = t('admin.users.show.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    user.value = await usersApi.show(id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.users.show.errors.load'));
  } finally {
    loading.value = false;
  }
};

const userInitials = computed(() => {
  if (!user.value) {
    return 'US';
  }

  const first = user.value.first_name?.trim()?.[0] ?? '';
  const last = user.value.last_name?.trim()?.[0] ?? '';
  const initials = `${first}${last}`.toUpperCase();

  return initials || user.value.email?.[0]?.toUpperCase() || 'US';
});

const resolveGenderLabel = (gender: string | null | undefined): string => {
  if (gender === 'male') {
    return t('admin.genders.male');
  }

  if (gender === 'female') {
    return t('admin.genders.female');
  }

  return t('common.dash');
};

const canEditViewedUser = computed(() => {
  if (!user.value || !canUpdateUsers.value) {
    return false;
  }

  return getHighestRoleLevelForUser(user.value) <= actorMaxRoleLevel.value;
});

onMounted(fetchUser);

const onUserRolledBack = async () => {
  await fetchUser();
};
</script>

<style lang="scss" scoped src="./index.scss"></style>
