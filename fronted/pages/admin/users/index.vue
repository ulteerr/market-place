<template>
  <AdminEntityIndex
    page-class="users-page"
    max-width-class="max-w-7xl"
    title="Пользователи"
    subtitle="Поиск, сортировка, limit и серверная пагинация."
    create-to="/admin/users/new"
    create-label="Новый пользователь"
    :search-value="listState.searchInput.value"
    search-placeholder="Поиск: фамилия, имя, отчество, email, телефон, роль"
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
    :table-skeleton-columns="5"
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
            <th>
              <button type="button" class="sort-btn" @click="onToggleSort('first_name')">
                Фамилия {{ listState.sortMark('last_name') }}
              </button>
            </th>
            <th>
              <button type="button" class="sort-btn" @click="onToggleSort('last_name')">
                Имя {{ listState.sortMark('first_name') }}
              </button>
            </th>
            <th>
              <button type="button" class="sort-btn" @click="onToggleSort('middle_name')">
                Отчество {{ listState.sortMark('middle_name') }}
              </button>
            </th>
            <th>
              <button type="button" class="sort-btn" @click="onToggleSort('access')">
                Доступ {{ listState.sortMark('access') }}
              </button>
            </th>
            <th class="text-right">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="admin-muted py-5 text-center text-sm">Загрузка...</td>
          </tr>
          <tr v-else-if="!users.length">
            <td colspan="5" class="admin-muted py-5 text-center text-sm">
              Пользователи не найдены.
            </td>
          </tr>
          <tr v-for="item in users" :key="item.id">
            <td>{{ item.last_name || '—' }}</td>
            <td>{{ item.first_name || '—' }}</td>
            <td>{{ item.middle_name || '—' }}</td>
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
          <h4 class="text-sm font-semibold">
            {{ getAdminUserFullName(item) }}
          </h4>
          <p class="admin-muted text-xs">Фамилия: {{ item.last_name || '—' }}</p>
          <p class="admin-muted mt-1 text-xs">Имя: {{ item.first_name || '—' }}</p>
          <p class="admin-muted text-xs">Отчество: {{ item.middle_name || '—' }}</p>
          <div class="mt-2">
            <span :class="['access-chip', accessClass(item)]">{{ accessLabel(item) }}</span>
          </div>
          <div class="mt-3">
            <AdminCrudActions
              :show-to="`/admin/users/${item.id}`"
              :edit-to="`/admin/users/${item.id}/edit`"
              :deleting="deletingId === item.id"
            />
          </div>
        </article>
      </div>
    </template>
  </AdminEntityIndex>
</template>

<script setup lang="ts">
import AdminCrudActions from '~/components/admin/Listing/AdminCrudActions.vue';
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex.vue';
import type { AdminUser } from '~/composables/useAdminUsers';
import { getAdminUserFullName, resolveAdminUserPanelAccess } from '~/composables/useAdminUsers';

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
} = useAdminCrudIndex<AdminUser>({
  settingsKey: 'users',
  useViewPreference: true,
  defaultSortBy: 'last_name',
  defaultPerPage: 10,
  listErrorMessage: 'Не удалось загрузить пользователей.',
  deleteErrorMessage: 'Не удалось удалить пользователя.',
  list: usersApi.list,
  remove: usersApi.remove,
  getItemId: (user) => user.id,
});

const cardSortFields = [
  { value: 'last_name', label: 'Фамилия' },
  { value: 'first_name', label: 'Имя' },
  { value: 'middle_name', label: 'Отчество' },
  { value: 'access', label: 'Доступ' },
];

const onModeChange = (mode: 'table' | 'table-cards' | 'cards') => {
  contentMode.value = mode;
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
};

const accessLabel = (item: AdminUser): string => {
  const access = resolveAdminUserPanelAccess(item);

  if (access === null) {
    return 'Неизвестно';
  }

  return access ? 'Админ-панель' : 'Без админ-доступа';
};

const accessClass = (item: AdminUser): string => {
  const access = resolveAdminUserPanelAccess(item);

  if (access === null) {
    return 'is-unknown';
  }

  return access ? 'is-admin' : 'is-basic';
};

const removeUser = async (user: AdminUser) => {
  await removeItem(user, {
    confirmMessage: `Удалить пользователя ${getAdminUserFullName(user)}?`,
  });
};
</script>

<style lang="scss" scoped src="./index.scss"></style>
