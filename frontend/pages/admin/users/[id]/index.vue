<template>
  <section class="users-show-page admin-show-page mx-auto w-full max-w-3xl space-y-6">
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
            <dt class="admin-muted text-xs">{{ t('admin.users.show.labels.birthDate') }}</dt>
            <dd>{{ user.birth_date || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.users.show.labels.roles') }}</dt>
            <dd>
              <template v-if="resolvedRoles.length">
                <template
                  v-for="(role, index) in resolvedRoles"
                  :key="`${role.code}-${role.id || index}`"
                >
                  <AdminLink v-if="role.id" :to="`/admin/roles/${role.id}`">{{
                    role.label
                  }}</AdminLink>
                  <span v-else>{{ role.label }}</span>
                  <span v-if="index < resolvedRoles.length - 1">, </span>
                </template>
              </template>
              <template v-else>{{ t('common.dash') }}</template>
            </dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.users.show.labels.status') }}</dt>
            <dd>{{ formatPresenceStatus(user.is_online, user.last_seen_at) }}</dd>
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

    <AdminActionLogPanel model="user" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import AdminLink from '~/components/admin/AdminLink/AdminLink.vue';
import type { AdminRole } from '~/composables/useAdminRoles';
import type { AdminUser } from '~/composables/useAdminUsers';
import {
  getAdminUserFullName,
  getHighestRoleLevelForUser,
  getHighestRoleLevelFromCodes,
} from '~/composables/useAdminUsers';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';
import { useRealtimeEcho } from '~/composables/realtime-echo/useRealtimeEcho';
import {
  applyPresencePatchToUser,
  subscribeToUsersPresence,
} from '~/composables/realtime-presence/runtime';
import { useRealtimeEchoState } from '~/composables/realtime-echo/useRealtimeEchoState';
import { createPresenceStatusRefreshController } from '~/composables/presence-status-refresh/runtime';
import { useRealtimeObservability } from '~/composables/useRealtimeObservability';
import { useUserPresenceStatus } from '~/composables/useUserPresenceStatus';
const { t } = useI18n();
const { formatPresenceStatus } = useUserPresenceStatus();
const { reportRealtimeEvent } = useRealtimeObservability();
const realtimeEchoState = useRealtimeEchoState();
const STATUS_REFRESH_INTERVAL_MS = 30_000;

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.users.read',
});

const route = useRoute();
const usersApi = useAdminUsers();
const rolesApi = useAdminRoles();
const { user: authUser, isAuthenticated, token } = useAuth();
const { hasPermission } = usePermissions();
const canUpdateUsers = computed(() => hasPermission('admin.users.update'));
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));
const actorMaxRoleLevel = computed(() =>
  getHighestRoleLevelFromCodes(Array.isArray(authUser.value?.roles) ? authUser.value.roles : [])
);

const user = ref<AdminUser | null>(null);
const rolesByCode = ref<Record<string, AdminRole>>({});
const loading = ref(false);
const loadError = ref('');
let stopRealtimePresenceSubscription: (() => void) | null = null;

const normalizeRoleView = (
  role: string | { id?: string | null; code?: string | null; label?: string | null }
): { id: string | null; code: string; label: string } | null => {
  if (typeof role === 'string') {
    const roleMeta = rolesByCode.value[role];

    return {
      id: roleMeta?.id ?? null,
      code: role,
      label: roleMeta?.label || role,
    };
  }

  const code = role?.code?.trim() ?? '';
  if (!code) {
    return null;
  }

  const roleMeta = rolesByCode.value[code];

  return {
    id: role?.id ?? roleMeta?.id ?? null,
    code,
    label: role?.label?.trim() || roleMeta?.label || code,
  };
};

const resolvedRoles = computed<Array<{ id: string | null; code: string; label: string }>>(() => {
  if (!user.value?.roles?.length) {
    return [];
  }

  return user.value.roles
    .map((role) => normalizeRoleView(role))
    .filter((role): role is { id: string | null; code: string; label: string } => role !== null);
});

const fetchRolesMeta = async () => {
  try {
    const response = await rolesApi.list({
      page: 1,
      per_page: 200,
      sort_by: 'code',
      sort_dir: 'asc',
    });

    rolesByCode.value = response.data.reduce<Record<string, AdminRole>>((accumulator, role) => {
      if (typeof role.code === 'string' && role.code.length > 0) {
        accumulator[role.code] = role;
      }

      return accumulator;
    }, {});
  } catch {
    rolesByCode.value = {};
  }
};

const fetchUser = async (options?: { silent?: boolean }) => {
  const id = String(route.params.id || '');

  if (!id) {
    loadError.value = t('admin.users.show.errors.invalidId');
    return;
  }

  const shouldShowLoading = options?.silent !== true;
  if (shouldShowLoading) {
    loading.value = true;
  }
  loadError.value = '';

  try {
    user.value = await usersApi.show(id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.users.show.errors.load'));
  } finally {
    if (shouldShowLoading) {
      loading.value = false;
    }
  }
};

const presenceStatusRefresh = createPresenceStatusRefreshController({
  intervalMs: STATUS_REFRESH_INTERVAL_MS,
  refresh: () => fetchUser({ silent: true }),
  isPageVisible: () => document.visibilityState === 'visible',
});
let realtimePresenceRetryTimer: ReturnType<typeof setTimeout> | null = null;
const pollingEnabled = ref(false);

const setPollingEnabled = (enabled: boolean) => {
  if (pollingEnabled.value === enabled) {
    return;
  }

  pollingEnabled.value = enabled;
  if (enabled) {
    presenceStatusRefresh.start();
    return;
  }

  presenceStatusRefresh.stop();
};

const clearRealtimeRetryTimer = () => {
  if (realtimePresenceRetryTimer === null) {
    return;
  }

  clearTimeout(realtimePresenceRetryTimer);
  realtimePresenceRetryTimer = null;
};

const scheduleRealtimeRetry = () => {
  if (realtimePresenceRetryTimer !== null || stopRealtimePresenceSubscription) {
    return;
  }

  realtimePresenceRetryTimer = setTimeout(() => {
    realtimePresenceRetryTimer = null;
    void connectRealtimePresence();
  }, 2_000);
};

const connectRealtimePresence = async () => {
  if (!isAuthenticated.value || !token.value || stopRealtimePresenceSubscription) {
    return;
  }

  const realtimeEcho = useRealtimeEcho();
  if (!realtimeEcho) {
    return;
  }

  const echo = await realtimeEcho.connect();
  if (!echo) {
    setPollingEnabled(true);
    void reportRealtimeEvent('websocket_subscribe_error', 'error', 'warning', {
      channel: 'users.presence',
    });
    scheduleRealtimeRetry();
    return;
  }

  clearRealtimeRetryTimer();
  stopRealtimePresenceSubscription = subscribeToUsersPresence(
    echo,
    (payload) => {
      user.value = applyPresencePatchToUser(user.value, payload);
    },
    {
      onSubscribed: () => {
        setPollingEnabled(false);
        void reportRealtimeEvent('websocket_subscribe_ok', 'ok', 'info', {
          channel: 'users.presence',
        });
      },
      onError: () => {
        stopRealtimePresenceSubscription?.();
        stopRealtimePresenceSubscription = null;
        setPollingEnabled(true);
        void reportRealtimeEvent('websocket_subscribe_error', 'error', 'warning', {
          channel: 'users.presence',
        });
        scheduleRealtimeRetry();
      },
    }
  );
};

const handlePresenceVisibility = () => {
  void presenceStatusRefresh.handleVisibilityChange();
};

const handlePresenceWindowFocus = () => {
  void presenceStatusRefresh.handleWindowFocus();
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

onMounted(() => {
  void fetchRolesMeta();
  void fetchUser();
});
onMounted(() => {
  document.addEventListener('visibilitychange', handlePresenceVisibility);
  window.addEventListener('focus', handlePresenceWindowFocus);
  setPollingEnabled(false);
  void connectRealtimePresence();
});

watch([isAuthenticated, token], ([authenticated, tokenValue]) => {
  if (!authenticated || !tokenValue) {
    setPollingEnabled(false);
    clearRealtimeRetryTimer();
    stopRealtimePresenceSubscription?.();
    stopRealtimePresenceSubscription = null;
    return;
  }

  if (!stopRealtimePresenceSubscription) {
    setPollingEnabled(true);
    void connectRealtimePresence();
  }
});

if (realtimeEchoState) {
  watch(
    realtimeEchoState,
    (state) => {
      if (state === 'connected') {
        if (!stopRealtimePresenceSubscription) {
          void connectRealtimePresence();
        }
        return;
      }

      if (state === 'connecting') {
        return;
      }

      stopRealtimePresenceSubscription?.();
      stopRealtimePresenceSubscription = null;

      if (isAuthenticated.value && token.value) {
        setPollingEnabled(true);
        scheduleRealtimeRetry();
      }
    },
    { immediate: true }
  );
}

onBeforeUnmount(() => {
  document.removeEventListener('visibilitychange', handlePresenceVisibility);
  window.removeEventListener('focus', handlePresenceWindowFocus);
  setPollingEnabled(false);
  clearRealtimeRetryTimer();
  stopRealtimePresenceSubscription?.();
  stopRealtimePresenceSubscription = null;
});

const onUserRolledBack = async () => {
  await fetchUser();
};
</script>

<style lang="scss" scoped src="./index.scss"></style>
