<template>
  <AdminEntityIndex
    page-class="geo-districts-page"
    max-width-class="max-w-7xl"
    :title="t('admin.geo.districts.index.title')"
    :subtitle="t('admin.geo.districts.index.subtitle')"
    create-to="/admin/geo/districts/new"
    :show-create="true"
    :create-label="t('admin.geo.districts.index.createLabel')"
    :search-value="listState.searchInput.value"
    :search-placeholder="t('admin.geo.districts.index.searchPlaceholder')"
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
    :table-skeleton-columns="3"
    @update:search-value="(value) => (listState.searchInput.value = value)"
    @update:per-page="onUpdatePerPage"
    @update:mode="onModeChange"
    @toggle-desktop="onToggleDesktopMode"
    @reset="onResetFilters"
    @sort="onToggleSort"
    @page="fetchItems"
  >
    <template #table>
      <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
        <table class="admin-table min-w-[700px]">
          <thead>
            <tr>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('name')">
                  {{ t('admin.geo.districts.index.headers.name') }}
                  {{ listState.sortMark('name') }}
                </button>
              </th>
              <th>{{ t('admin.geo.districts.index.headers.cityId') }}</th>
              <th class="text-right">{{ t('admin.geo.districts.index.headers.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="3" class="admin-muted py-5 text-center text-sm">
                {{ t('common.loading') }}
              </td>
            </tr>
            <tr v-else-if="!items.length">
              <td colspan="3" class="admin-muted py-5 text-center text-sm">
                {{ t('admin.geo.districts.index.empty') }}
              </td>
            </tr>
            <tr v-for="item in items" :key="item.id">
              <td>{{ item.name }}</td>
              <td class="text-xs">
                <AdminLink :to="resolveCityLink(item)">
                  {{ resolveCityName(item) }}
                </AdminLink>
              </td>
              <td>
                <AdminCrudActions
                  :show-to="`/admin/geo/districts/${item.id}`"
                  :edit-to="`/admin/geo/districts/${item.id}/edit`"
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

    <template #cards>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article v-for="item in items" :key="item.id" class="admin-entity-card rounded-xl p-4">
          <h4 class="text-sm font-semibold">{{ item.name }}</h4>
          <p class="admin-muted mt-1 text-xs">
            {{ t('admin.geo.districts.index.card.city', { value: resolveCityName(item) }) }}
          </p>

          <div class="mt-3">
            <AdminCrudActions
              :show-to="`/admin/geo/districts/${item.id}`"
              :edit-to="`/admin/geo/districts/${item.id}/edit`"
              :can-show="true"
              :can-edit="true"
              :can-delete="true"
              :deleting="deletingId === item.id"
              @delete="onRemove(item)"
            />
          </div>
        </article>
      </div>
    </template>
  </AdminEntityIndex>

  <UiModal
    v-model="removeConfirmOpen"
    mode="confirm"
    :title="t('admin.actions.delete')"
    :message="removeConfirmMessage"
    :confirm-label="t('admin.actions.delete')"
    :cancel-label="t('common.cancel')"
    :loading-label="t('common.loading')"
    :confirm-loading="Boolean(deletingId)"
    destructive
    @confirm="confirmRemoveItem"
    @cancel="cancelRemoveItem"
  />
</template>

<script setup lang="ts">
import AdminLink from '~/components/admin/AdminLink/AdminLink.vue';
import AdminCrudActions from '~/components/admin/Listing/AdminCrudActions/AdminCrudActions.vue';
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex/AdminEntityIndex.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import { useDebouncedSearch } from '~/composables/useAsyncSelectOptions';
import type { AdminGeoDistrict } from '~/composables/useAdminGeoDistricts';

const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.panel.access',
});

const api = useAdminGeoDistricts();
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
} = useAdminCrudIndex<AdminGeoDistrict>({
  settingsKey: 'geo-districts',
  defaultSortBy: 'name',
  defaultPerPage: 20,
  listErrorMessage: t('admin.geo.districts.errors.loadList'),
  deleteErrorMessage: t('admin.geo.districts.errors.delete'),
  list: api.list,
  remove: api.remove,
  getItemId: (item) => item.id,
});

const cardSortFields = computed(() => [
  { value: 'name', label: t('admin.geo.districts.index.sort.name') },
  { value: 'city_id', label: t('admin.geo.districts.index.sort.cityId') },
]);

const onModeChange = (mode: 'table' | 'table-cards' | 'cards') => {
  contentMode.value = mode;
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
};

const getEmbeddedCity = (item: AdminGeoDistrict): { name?: string | null } | null => {
  return item.city ?? null;
};

const resolveCityName = (item: AdminGeoDistrict): string => {
  const embedded = getEmbeddedCity(item);
  if (embedded?.name) {
    return embedded.name;
  }

  return item.city_id || t('common.dash');
};

const resolveCityLink = (item: AdminGeoDistrict): string => {
  return `/admin/geo/cities/${item.city_id}`;
};

const onRemove = (item: AdminGeoDistrict) => {
  removeItem(item, {
    confirmTitle: t('admin.actions.delete'),
    confirmMessage: t('admin.geo.districts.confirmDelete', { name: item.name }),
    confirmLabel: t('admin.actions.delete'),
    cancelLabel: t('common.cancel'),
  });
};

useDebouncedSearch(
  () => listState.searchInput.value,
  (nextValue) => {
    if (nextValue.trim() === listState.search.value) return;
    fetchItems(listState.applySearch());
  },
  { delay: 300, skipInitial: true }
);
</script>

<style lang="scss" scoped src="../../_shared/admin-index-page.scss"></style>
