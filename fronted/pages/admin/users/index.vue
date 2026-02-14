<template>
  <AdminEntityIndex
    page-class="users-page"
    max-width-class="max-w-7xl"
    :title="t('admin.users.index.title')"
    :subtitle="t('admin.users.index.subtitle')"
    create-to="/admin/users/new"
    :create-label="t('admin.users.index.createLabel')"
    :search-value="listState.searchInput.value"
    :search-placeholder="t('admin.users.index.searchPlaceholder')"
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
    :table-skeleton-columns="6"
    @update:search-value="(value) => (listState.searchInput.value = value)"
    @update:per-page="onUpdatePerPage"
    @update:mode="onModeChange"
    @toggle-desktop="onToggleDesktopMode"
    @apply="onApplySearch"
    @reset="onResetFilters"
    @sort="onToggleSort"
    @page="fetchUsers"
  >
    <template #table>
      <table class="admin-table">
        <thead>
          <tr>
            <th>{{ t('admin.users.index.headers.thumbnail') }}</th>
            <th>
              <button type="button" class="sort-btn" @click="onToggleSort('last_name')">
                {{ t('admin.users.index.headers.lastName') }} {{ listState.sortMark('last_name') }}
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
              <button type="button" class="sort-btn" @click="onToggleSort('access')">
                {{ t('admin.users.index.headers.access') }} {{ listState.sortMark('access') }}
              </button>
            </th>
            <th class="text-right">{{ t('admin.users.index.headers.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="admin-muted py-5 text-center text-sm">
              {{ t('common.loading') }}
            </td>
          </tr>
          <tr v-else-if="!users.length">
            <td colspan="6" class="admin-muted py-5 text-center text-sm">
              {{ t('admin.users.index.empty') }}
            </td>
          </tr>
          <tr v-for="item in users" :key="item.id">
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
            <td>
              <span :class="['access-chip', accessClass(item)]">{{ accessLabel(item) }}</span>
            </td>
            <td>
              <AdminCrudActions
                :show-to="`/admin/users/${item.id}`"
                :edit-to="`/admin/users/${item.id}/edit`"
                :deleting="deletingId === item.id"
                align="end"
                @delete="removeUser(item)"
              />
            </td>
          </tr>
        </tbody>
      </table>
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
          <div class="mt-2">
            <span :class="['access-chip', accessClass(item)]">{{ accessLabel(item) }}</span>
          </div>
          <div class="mt-3">
            <AdminCrudActions
              :show-to="`/admin/users/${item.id}`"
              :edit-to="`/admin/users/${item.id}/edit`"
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
import AdminCrudActions from '~/components/admin/Listing/AdminCrudActions.vue';
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex.vue';
import UiImagePreview from '~/components/ui/ImagePreview/UiImagePreview.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import type { AdminUser } from '~/composables/useAdminUsers';
import { getAdminUserFullName, resolveAdminUserPanelAccess } from '~/composables/useAdminUsers';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
});

const usersApi = useAdminUsers();
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
  onApplySearch,
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
  list: usersApi.list,
  remove: usersApi.remove,
  getItemId: (user) => user.id,
});

const cardSortFields = computed(() => [
  { value: 'last_name', label: t('admin.users.index.sort.lastName') },
  { value: 'first_name', label: t('admin.users.index.sort.firstName') },
  { value: 'middle_name', label: t('admin.users.index.sort.middleName') },
  { value: 'access', label: t('admin.users.index.sort.access') },
]);

const onModeChange = (mode: 'table' | 'table-cards' | 'cards') => {
  contentMode.value = mode;
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
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

const removeUser = async (user: AdminUser) => {
  removeItem(user, {
    confirmTitle: t('admin.actions.delete'),
    confirmMessage: t('admin.users.confirmDelete', { name: getAdminUserFullName(user) }),
    confirmLabel: t('admin.actions.delete'),
    cancelLabel: t('common.cancel'),
  });
};
</script>

<style lang="scss" scoped src="./index.scss"></style>
