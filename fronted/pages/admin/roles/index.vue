<template>
  <AdminEntityIndex
    page-class="roles-page"
    max-width-class="max-w-6xl"
    :title="t('admin.roles.index.title')"
    :subtitle="t('admin.roles.index.subtitle')"
    create-to="/admin/roles/new"
    :create-label="t('admin.roles.index.createLabel')"
    :search-value="listState.searchInput.value"
    :search-placeholder="t('admin.roles.index.searchPlaceholder')"
    :per-page="listState.perPage.value"
    :per-page-options="listState.perPageOptions"
    :loading="loading"
    :shown-count="roles.length"
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
    :table-skeleton-columns="4"
    @update:search-value="(value) => (listState.searchInput.value = value)"
    @update:per-page="onUpdatePerPage"
    @update:mode="onModeChange"
    @toggle-desktop="onToggleDesktopMode"
    @apply="onApplySearch"
    @reset="onResetFilters"
    @sort="onToggleSort"
    @page="fetchRoles"
  >
    <template #table>
      <table class="admin-table">
        <thead>
          <tr>
            <th>
              <button type="button" class="sort-btn" @click="onToggleSort('code')">
                {{ t('admin.roles.index.headers.code') }} {{ listState.sortMark('code') }}
              </button>
            </th>
            <th>
              <button type="button" class="sort-btn" @click="onToggleSort('label')">
                {{ t('admin.roles.index.headers.label') }} {{ listState.sortMark('label') }}
              </button>
            </th>
            <th>
              <button type="button" class="sort-btn" @click="onToggleSort('is_system')">
                {{ t('admin.roles.index.headers.type') }} {{ listState.sortMark('is_system') }}
              </button>
            </th>
            <th class="text-right">{{ t('admin.roles.index.headers.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4" class="admin-muted py-5 text-center text-sm">
              {{ t('common.loading') }}
            </td>
          </tr>
          <tr v-else-if="!roles.length">
            <td colspan="4" class="admin-muted py-5 text-center text-sm">
              {{ t('admin.roles.index.empty') }}
            </td>
          </tr>
          <tr v-for="role in roles" :key="role.id">
            <td class="font-mono text-xs">{{ role.code }}</td>
            <td>{{ role.label || '—' }}</td>
            <td>
              <span :class="['role-chip', role.is_system ? 'is-system' : 'is-custom']">
                {{
                  role.is_system
                    ? t('admin.roles.index.type.system')
                    : t('admin.roles.index.type.custom')
                }}
              </span>
            </td>
            <td>
              <AdminCrudActions
                :show-to="`/admin/roles/${role.id}`"
                :edit-to="`/admin/roles/${role.id}/edit`"
                :can-delete="!role.is_system"
                :deleting="deletingId === role.id"
                align="end"
                @delete="removeRole(role)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </template>

    <template #cards>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article v-for="role in roles" :key="role.id" class="role-card rounded-xl p-4">
          <h4 class="font-mono text-xs">{{ role.code }}</h4>
          <p class="admin-muted mt-1 text-xs">
            {{ role.label || t('admin.roles.index.cardLabelFallback') }}
          </p>
          <div class="mt-2">
            <span :class="['role-chip', role.is_system ? 'is-system' : 'is-custom']">
              {{
                role.is_system
                  ? t('admin.roles.index.type.system')
                  : t('admin.roles.index.type.custom')
              }}
            </span>
          </div>
          <div class="mt-3">
            <AdminCrudActions
              :show-to="`/admin/roles/${role.id}`"
              :edit-to="`/admin/roles/${role.id}/edit`"
              :can-delete="!role.is_system"
              :deleting="deletingId === role.id"
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
import type { AdminRole } from '~/composables/useAdminRoles';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
});

const rolesApi = useAdminRoles();
const {
  listState,
  items: roles,
  loading,
  loadError,
  deletingId,
  contentMode,
  tableOnDesktop,
  pagination,
  showPagination,
  paginationItems,
  fetchItems: fetchRoles,
  onToggleSort,
  onApplySearch,
  onResetFilters,
  onUpdatePerPage,
  removeItem,
} = useAdminCrudIndex<AdminRole>({
  settingsKey: 'roles',
  defaultSortBy: 'code',
  defaultPerPage: 10,
  listErrorMessage: t('admin.roles.errors.loadList'),
  deleteErrorMessage: t('admin.roles.errors.delete'),
  list: rolesApi.list,
  remove: rolesApi.remove,
  getItemId: (role) => role.id,
});

const cardSortFields = computed(() => [
  { value: 'code', label: t('admin.roles.index.sort.code') },
  { value: 'label', label: t('admin.roles.index.sort.label') },
  { value: 'is_system', label: t('admin.roles.index.sort.type') },
]);

const onModeChange = (mode: 'table' | 'table-cards' | 'cards') => {
  contentMode.value = mode;
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
};

const removeRole = async (role: AdminRole) => {
  await removeItem(role, {
    canDelete: !role.is_system,
    confirmMessage: t('admin.roles.confirmDelete', { code: role.code }),
  });
};
</script>

<style lang="scss" scoped src="./index.scss"></style>
