<template>
  <AdminEntityIndex
    page-class="metro-stations-page"
    max-width-class="max-w-7xl"
    title="Станции метро"
    subtitle="CRUD для станций метро"
    create-to="/admin/metro-stations/new"
    :show-create="true"
    create-label="Новая станция"
    :search-value="listState.searchInput.value"
    search-placeholder="Поиск по названию"
    :show-apply="false"
    :per-page="listState.perPage.value"
    :per-page-options="listState.perPageOptions"
    :loading="loading"
    :shown-count="items.length"
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
    :table-skeleton-columns="7"
    @update:search-value="(value) => (listState.searchInput.value = value)"
    @update:per-page="onUpdatePerPage"
    @update:mode="(mode) => (contentMode = mode)"
    @toggle-desktop="tableOnDesktop = !tableOnDesktop"
    @reset="onResetFilters"
    @sort="onToggleSort"
    @page="fetchItems"
  >
    <template #table>
      <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
        <table class="admin-table min-w-[980px]">
          <thead>
            <tr>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('name')">
                  Название {{ listState.sortMark('name') }}
                </button>
              </th>
              <th>Line ID</th>
              <th>Metro Line ID</th>
              <th>City ID</th>
              <th>Geo</th>
              <th>Source</th>
              <th class="text-right">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="admin-muted py-5 text-center text-sm">Загрузка...</td>
            </tr>
            <tr v-else-if="!items.length">
              <td colspan="7" class="admin-muted py-5 text-center text-sm">Нет данных</td>
            </tr>
            <tr v-for="item in items" :key="item.id">
              <td>{{ item.name }}</td>
              <td>{{ item.line_id || '—' }}</td>
              <td class="font-mono text-xs">{{ item.metro_line_id }}</td>
              <td class="font-mono text-xs">{{ item.city_id }}</td>
              <td class="text-xs">{{ item.geo_lat ?? '—' }}, {{ item.geo_lon ?? '—' }}</td>
              <td>{{ item.source }}</td>
              <td>
                <AdminCrudActions
                  :show-to="`/admin/metro-stations/${item.id}`"
                  :edit-to="`/admin/metro-stations/${item.id}/edit`"
                  :can-show="true"
                  :can-edit="true"
                  :can-delete="true"
                  :deleting="deletingId === item.id"
                  align="end"
                  @delete="onRemove(item)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </AdminEntityIndex>

  <UiModal
    v-model="removeConfirmOpen"
    mode="confirm"
    title="Удаление"
    :message="removeConfirmMessage"
    confirm-label="Удалить"
    cancel-label="Отмена"
    loading-label="Загрузка"
    :confirm-loading="Boolean(deletingId)"
    destructive
    @confirm="confirmRemoveItem"
    @cancel="cancelRemoveItem"
  />
</template>

<script setup lang="ts">
import AdminCrudActions from '~/components/admin/Listing/AdminCrudActions.vue';
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import type { AdminMetroStation } from '~/composables/useAdminMetroStations';

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.panel.access',
});

const api = useAdminMetroStations();
const {
  listState,
  items,
  loading,
  loadError,
  deletingId,
  removeConfirmOpen,
  removeConfirmMessage,
  contentMode,
  tableOnDesktop,
  pagination,
  showPagination,
  paginationItems,
  fetchItems,
  onToggleSort,
  onResetFilters,
  onUpdatePerPage,
  removeItem,
  confirmRemoveItem,
  cancelRemoveItem,
} = useAdminCrudIndex<AdminMetroStation>({
  settingsKey: 'metro-stations',
  defaultSortBy: 'name',
  defaultPerPage: 20,
  listErrorMessage: 'Не удалось загрузить станции метро',
  deleteErrorMessage: 'Не удалось удалить станцию метро',
  list: api.list,
  remove: api.remove,
  getItemId: (item) => item.id,
});

const cardSortFields = computed(() => [
  { value: 'name', label: 'Название' },
  { value: 'line_id', label: 'Line ID' },
]);

const onRemove = (item: AdminMetroStation) => {
  removeItem(item, {
    confirmTitle: 'Удаление',
    confirmMessage: `Удалить станцию «${item.name}»?`,
    confirmLabel: 'Удалить',
    cancelLabel: 'Отмена',
  });
};

const searchAutoReady = ref(false);
let searchAutoTimer: ReturnType<typeof setTimeout> | null = null;

watch(
  () => listState.searchInput.value,
  (nextValue) => {
    if (!searchAutoReady.value) return;
    if (nextValue.trim() === listState.search.value) return;
    if (searchAutoTimer) clearTimeout(searchAutoTimer);
    searchAutoTimer = setTimeout(() => {
      fetchItems(listState.applySearch());
    }, 300);
  }
);

onMounted(() => {
  searchAutoReady.value = true;
});

onBeforeUnmount(() => {
  if (searchAutoTimer) clearTimeout(searchAutoTimer);
});
</script>
