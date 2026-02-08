<template>
  <AdminEntityIndex
    page-class="roles-page"
    max-width-class="max-w-6xl"
    title="Роли"
    subtitle="Поиск, сортировка, limit и серверная пагинация."
    create-to="/admin/roles/new"
    create-label="Новая роль"
    :search-value="listState.searchInput.value"
    search-placeholder="Поиск: code или label"
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
            <th><button type="button" class="sort-btn" @click="onToggleSort('code')">Code {{ listState.sortMark('code') }}</button></th>
            <th><button type="button" class="sort-btn" @click="onToggleSort('label')">Label {{ listState.sortMark('label') }}</button></th>
            <th><button type="button" class="sort-btn" @click="onToggleSort('is_system')">Тип {{ listState.sortMark('is_system') }}</button></th>
            <th class="text-right">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4" class="admin-muted py-5 text-center text-sm">Загрузка...</td>
          </tr>
          <tr v-else-if="!roles.length">
            <td colspan="4" class="admin-muted py-5 text-center text-sm">Роли не найдены.</td>
          </tr>
          <tr v-for="role in roles" :key="role.id">
            <td class="font-mono text-xs">{{ role.code }}</td>
            <td>{{ role.label || '—' }}</td>
            <td>
              <span :class="['role-chip', role.is_system ? 'is-system' : 'is-custom']">
                {{ role.is_system ? 'Системная' : 'Пользовательская' }}
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
          <p class="admin-muted mt-1 text-xs">{{ role.label || 'Без label' }}</p>
          <div class="mt-2">
            <span :class="['role-chip', role.is_system ? 'is-system' : 'is-custom']">
              {{ role.is_system ? 'Системная' : 'Пользовательская' }}
            </span>
          </div>
          <div class="mt-3">
            <AdminCrudActions :show-to="`/admin/roles/${role.id}`" :edit-to="`/admin/roles/${role.id}/edit`" :can-delete="false" />
          </div>
        </article>
      </div>
    </template>
  </AdminEntityIndex>
</template>

<script setup lang="ts">
import AdminCrudActions from '~/components/admin/Listing/AdminCrudActions.vue'
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex.vue'
import type { AdminRole } from '~/composables/useAdminRoles'

definePageMeta({
  layout: 'admin'
})

const rolesApi = useAdminRoles()
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
  removeItem
} = useAdminCrudIndex<AdminRole>({
  settingsKey: 'roles',
  defaultSortBy: 'code',
  defaultPerPage: 10,
  listErrorMessage: 'Не удалось загрузить роли.',
  deleteErrorMessage: 'Не удалось удалить роль.',
  list: rolesApi.list,
  remove: rolesApi.remove,
  getItemId: (role) => role.id
})

const cardSortFields = [
  { value: 'code', label: 'Code' },
  { value: 'label', label: 'Label' },
  { value: 'is_system', label: 'Тип' }
]

const onModeChange = (mode: 'table' | 'table-cards' | 'cards') => {
  contentMode.value = mode
}

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value
}

const removeRole = async (role: AdminRole) => {
  await removeItem(role, {
    canDelete: !role.is_system,
    confirmMessage: `Удалить роль ${role.code}?`
  })
}
</script>

<style lang="scss" scoped src="./index.scss"></style>
