<template>
  <AdminEntityIndex
    page-class="users-page"
    max-width-class="max-w-7xl"
    :title="t('admin.users.index.title')"
    :subtitle="t('admin.users.index.subtitle')"
    create-to="/admin/users/new"
    :show-create="canCreateUsers"
    :create-label="t('admin.users.index.createLabel')"
    :search-value="listState.searchInput.value"
    :search-placeholder="t('admin.users.index.searchPlaceholder')"
    :show-apply="false"
    :per-page="listState.perPage.value"
    :per-page-options="listState.perPageOptions"
    :loading="loading"
    :shown-count="users.length"
    :total-count="pagination.total"
    :load-error="loadError"
    :mode="contentMode"
    :table-on-desktop="tableOnDesktop"
    :card-sort-fields="cardSortFields"
    :active-sort-by="listState.sortBy.value"
    :sort-mark="listState.sortMark"
    :show-pagination="showPagination"
    :current-page="pagination.current_page"
    :last-page="pagination.last_page"
    :pagination-per-page="pagination.per_page"
    :pagination-items="paginationItems"
    :table-skeleton-columns="8"
    @update:search-value="(value) => (listState.searchInput.value = value)"
    @update:per-page="onUpdatePerPage"
    @update:mode="onModeChange"
    @toggle-desktop="onToggleDesktopMode"
    @reset="onResetAllFilters"
    @sort="onToggleSort"
    @page="fetchUsers"
  >
    <template #filters>
      <AdminTagFilter
        v-model="selectedAccessGroups"
        :options="accessTagOptions"
        mode="single"
        @update:model-value="onAccessFilterChange"
      />
    </template>

    <template #table>
      <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
        <table class="admin-table min-w-[980px]">
          <thead>
            <tr>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('id')">
                  {{ t('admin.users.index.headers.id') }}
                  {{ listState.sortMark('id') }}
                </button>
              </th>
              <th>{{ t('admin.users.index.headers.thumbnail') }}</th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('last_name')">
                  {{ t('admin.users.index.headers.lastName') }}
                  {{ listState.sortMark('last_name') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('first_name')">
                  {{ t('admin.users.index.headers.firstName') }}
                  {{ listState.sortMark('first_name') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('middle_name')">
                  {{ t('admin.users.index.headers.middleName') }}
                  {{ listState.sortMark('middle_name') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('gender')">
                  {{ t('admin.users.index.headers.gender') }}
                  {{ listState.sortMark('gender') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('access')">
                  {{ t('admin.users.index.headers.access') }} {{ listState.sortMark('access') }}
                </button>
              </th>
              <th>{{ t('admin.users.index.headers.status') }}</th>
              <th class="text-right">{{ t('admin.users.index.headers.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="9" class="admin-muted py-5 text-center text-sm">
                {{ t('common.loading') }}
              </td>
            </tr>
            <tr v-else-if="!users.length">
              <td colspan="9" class="admin-muted py-5 text-center text-sm">
                {{ t('admin.users.index.empty') }}
              </td>
            </tr>
            <tr v-for="item in users" :key="item.id">
              <td class="font-mono text-xs">{{ item.id }}</td>
              <td>
                <UiImagePreview
                  :src="item.avatar?.url ?? null"
                  :alt="getAdminUserFullName(item)"
                  :preview-alt="getAdminUserFullName(item)"
                  variant="table"
                  :fallback-text="t('common.dash')"
                  :preview-title="t('admin.users.index.preview.title')"
                  :open-aria-label="t('admin.users.index.preview.open')"
                />
              </td>
              <td>
                <span>{{ item.last_name || t('common.dash') }}</span>
              </td>
              <td>{{ item.first_name || t('common.dash') }}</td>
              <td>{{ item.middle_name || t('common.dash') }}</td>
              <td>{{ resolveGenderLabel(item.gender) }}</td>
              <td>
                <span :class="['access-chip', accessClass(item)]">{{ accessLabel(item) }}</span>
              </td>
              <td>{{ formatPresenceStatus(item.is_online, item.last_seen_at) }}</td>
              <td>
                <AdminCrudActions
                  :show-to="`/admin/users/${item.id}`"
                  :edit-to="`/admin/users/${item.id}/edit`"
                  :can-show="canReadUsers"
                  :can-edit="canEditUser(item)"
                  :can-delete="canDeleteUsers"
                  :deleting="deletingId === item.id"
                  align="end"
                  @delete="removeUser(item)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <template #cards>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article v-for="item in users" :key="item.id" class="user-card rounded-xl p-4">
          <UiImagePreview
            :src="item.avatar?.url ?? null"
            :alt="getAdminUserFullName(item)"
            :preview-alt="getAdminUserFullName(item)"
            variant="card"
            :fallback-text="t('common.dash')"
            :preview-title="t('admin.users.index.preview.title')"
            :open-aria-label="t('admin.users.index.preview.open')"
          />
          <h4 class="mt-2 text-sm font-semibold">
            {{ getAdminUserFullName(item) }}
          </h4>
          <p class="admin-muted text-xs">
            {{ t('admin.users.index.card.id', { value: item.id }) }}
          </p>
          <p class="admin-muted text-xs">
            {{
              t('admin.users.index.card.lastName', { value: item.last_name || t('common.dash') })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.users.index.card.firstName', { value: item.first_name || t('common.dash') })
            }}
          </p>
          <p class="admin-muted text-xs">
            {{
              t('admin.users.index.card.middleName', {
                value: item.middle_name || t('common.dash'),
              })
            }}
          </p>
          <p class="admin-muted text-xs">
            {{ t('admin.users.index.card.gender', { value: resolveGenderLabel(item.gender) }) }}
          </p>
          <p class="admin-muted text-xs">
            {{
              t('admin.users.index.card.status', {
                value: formatPresenceStatus(item.is_online, item.last_seen_at),
              })
            }}
          </p>
          <div class="mt-2">
            <span :class="['access-chip', accessClass(item)]">{{ accessLabel(item) }}</span>
          </div>
          <div class="mt-3">
            <AdminCrudActions
              :show-to="`/admin/users/${item.id}`"
              :edit-to="`/admin/users/${item.id}/edit`"
              :can-show="canReadUsers"
              :can-edit="canEditUser(item)"
              :can-delete="canDeleteUsers"
              :deleting="deletingId === item.id"
              @delete="removeUser(item)"
            />
          </div>
        </article>
      </div>
    </template>
  </AdminEntityIndex>

  <UiModal
    v-model="removeConfirmOpen"
    mode="confirm"
    :title="removeConfirmTitle"
    :message="removeConfirmMessage"
    :confirm-label="removeConfirmLabel"
    :cancel-label="removeCancelLabel"
    :loading-label="t('common.loading')"
    :confirm-loading="Boolean(deletingId)"
    destructive
    @confirm="confirmRemoveItem"
    @cancel="cancelRemoveItem"
  />
</template>

<script setup lang="ts">
import AdminCrudActions from '~/components/admin/Listing/AdminCrudActions/AdminCrudActions.vue';
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex/AdminEntityIndex.vue';
import AdminTagFilter from '~/components/admin/Listing/AdminTagFilter/AdminTagFilter.vue';
import UiImagePreview from '~/components/ui/ImagePreview/UiImagePreview.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import { useDebouncedSearch } from '~/composables/useAsyncSelectOptions';
import type { AdminUser } from '~/composables/useAdminUsers';
import {
  getHighestRoleLevelForUser,
  getHighestRoleLevelFromCodes,
  getAdminUserFullName,
  resolveAdminUserPanelAccess,
} from '~/composables/useAdminUsers';
import { useRealtimeEcho } from '~/composables/realtime-echo/useRealtimeEcho';
import {
  applyPresencePatchToUsers,
  subscribeToUsersPresence,
} from '~/composables/realtime-presence/runtime';
import { useRealtimeEchoState } from '~/composables/realtime-echo/useRealtimeEchoState';
import { useRealtimeObservability } from '~/composables/useRealtimeObservability';
import { useUserPresenceStatus } from '~/composables/useUserPresenceStatus';
import { createPresenceStatusRefreshController } from '~/composables/presence-status-refresh/runtime';
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

const usersApi = useAdminUsers();
const route = useRoute();
const router = useRouter();
const { user: authUser, isAuthenticated, token } = useAuth();
const { hasPermission } = usePermissions();
const canReadUsers = computed(() => hasPermission('admin.users.read'));
const canCreateUsers = computed(() => hasPermission('admin.users.create'));
const canUpdateUsers = computed(() => hasPermission('admin.users.update'));
const canDeleteUsers = computed(() => hasPermission('admin.users.delete'));
const actorMaxRoleLevel = computed(() =>
  getHighestRoleLevelFromCodes(Array.isArray(authUser.value?.roles) ? authUser.value.roles : [])
);

const readAccessGroupFromQuery = (): 'admin' | 'basic' | null => {
  const raw = route.query.access_group;
  const value = Array.isArray(raw) ? raw[0] : raw;

  return value === 'admin' || value === 'basic' ? value : null;
};

const initialAccessGroup = readAccessGroupFromQuery();
const selectedAccessGroups = ref<Array<'admin' | 'basic'>>(
  initialAccessGroup ? [initialAccessGroup] : []
);
const accessTagOptions = computed(() => [
  { value: 'admin', label: t('admin.users.index.tags.admin') },
  { value: 'basic', label: t('admin.users.index.tags.basic') },
]);
const {
  listState,
  items: users,
  loading,
  loadError,
  deletingId,
  removeConfirmOpen,
  removeConfirmTitle,
  removeConfirmMessage,
  removeConfirmLabel,
  removeCancelLabel,
  contentMode,
  tableOnDesktop,
  pagination,
  showPagination,
  paginationItems,
  fetchItems: fetchUsers,
  onToggleSort,
  onResetFilters,
  onUpdatePerPage,
  removeItem,
  confirmRemoveItem,
  cancelRemoveItem,
} = useAdminCrudIndex<AdminUser>({
  settingsKey: 'users',
  useViewPreference: true,
  defaultSortBy: 'last_name',
  defaultPerPage: 10,
  listErrorMessage: t('admin.errors.users.loadList'),
  deleteErrorMessage: t('admin.errors.users.delete'),
  list: (params) =>
    usersApi.list({
      ...params,
      access_group: selectedAccessGroups.value[0] ?? undefined,
    }),
  remove: usersApi.remove,
  getItemId: (user) => user.id,
});

const cardSortFields = computed(() => [
  { value: 'id', label: t('admin.users.index.sort.id') },
  { value: 'last_name', label: t('admin.users.index.sort.lastName') },
  { value: 'first_name', label: t('admin.users.index.sort.firstName') },
  { value: 'middle_name', label: t('admin.users.index.sort.middleName') },
  { value: 'gender', label: t('admin.users.index.sort.gender') },
  { value: 'access', label: t('admin.users.index.sort.access') },
]);

const onModeChange = (mode: 'table' | 'table-cards' | 'cards') => {
  contentMode.value = mode;
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
};

const syncAccessGroupQuery = async () => {
  await router.replace({
    query: {
      ...route.query,
      access_group: selectedAccessGroups.value[0] ?? undefined,
      page: undefined,
    },
  });
};

const onAccessFilterChange = async () => {
  await syncAccessGroupQuery();
  await fetchUsers(1);
};

const onResetAllFilters = async () => {
  selectedAccessGroups.value = [];
  await syncAccessGroupQuery();
  onResetFilters();
};

const accessLabel = (item: AdminUser): string => {
  const access = resolveAdminUserPanelAccess(item);

  if (access === null) {
    return t('admin.users.index.access.unknown');
  }

  return access ? t('admin.users.index.access.admin') : t('admin.users.index.access.basic');
};

const accessClass = (item: AdminUser): string => {
  const access = resolveAdminUserPanelAccess(item);

  if (access === null) {
    return 'is-unknown';
  }

  return access ? 'is-admin' : 'is-basic';
};

const resolveGenderLabel = (gender: string | null | undefined): string => {
  if (gender === 'male') {
    return t('admin.genders.male');
  }

  if (gender === 'female') {
    return t('admin.genders.female');
  }

  return t('common.dash');
};

const canEditUser = (item: AdminUser): boolean => {
  if (!canUpdateUsers.value) {
    return false;
  }

  return getHighestRoleLevelForUser(item) <= actorMaxRoleLevel.value;
};

const removeUser = async (user: AdminUser) => {
  if (!canDeleteUsers.value) {
    return;
  }

  removeItem(user, {
    canDelete: canDeleteUsers.value,
    confirmTitle: t('admin.actions.delete'),
    confirmMessage: t('admin.users.confirmDelete', { name: getAdminUserFullName(user) }),
    confirmLabel: t('admin.actions.delete'),
    cancelLabel: t('common.cancel'),
  });
};

useDebouncedSearch(
  () => listState.searchInput.value,
  (nextValue) => {
    if (nextValue.trim() === listState.search.value) {
      return;
    }

    fetchUsers(listState.applySearch());
  },
  { delay: 300, skipInitial: true }
);

const presenceStatusRefresh = createPresenceStatusRefreshController({
  intervalMs: STATUS_REFRESH_INTERVAL_MS,
  refresh: async () => {
    await fetchUsers(pagination.current_page || 1);
  },
  isPageVisible: () => document.visibilityState === 'visible',
});
let stopRealtimePresenceSubscription: (() => void) | null = null;
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
      users.value = applyPresencePatchToUsers(users.value, payload);
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

onMounted(() => {
  document.addEventListener('visibilitychange', handlePresenceVisibility);
  window.addEventListener('focus', handlePresenceWindowFocus);
  setPollingEnabled(true);
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
</script>

<style lang="scss" scoped src="./index.scss"></style>
