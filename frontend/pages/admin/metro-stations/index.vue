<template>
  <AdminEntityIndex
    page-class="metro-stations-page"
    max-width-class="max-w-7xl"
    :title="t('admin.metro.stations.index.title')"
    :subtitle="t('admin.metro.stations.index.subtitle')"
    create-to="/admin/metro-stations/new"
    :show-create="true"
    :create-label="t('admin.metro.stations.index.createLabel')"
    :search-value="listState.searchInput.value"
    :search-placeholder="t('admin.metro.stations.index.searchPlaceholder')"
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
    :table-skeleton-columns="5"
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
        <table class="admin-table min-w-[980px]">
          <thead>
            <tr>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('name')">
                  {{ t('admin.metro.stations.index.headers.name') }}
                  {{ listState.sortMark('name') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('line_id')">
                  {{ t('admin.metro.stations.index.headers.lineId') }}
                  {{ listState.sortMark('line_id') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('metro_line_id')">
                  {{ t('admin.metro.stations.index.headers.metroLine') }}
                  {{ listState.sortMark('metro_line_id') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('city_id')">
                  {{ t('admin.metro.stations.index.headers.city') }}
                  {{ listState.sortMark('city_id') }}
                </button>
              </th>
              <th class="text-right">{{ t('admin.metro.stations.index.headers.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="admin-muted py-5 text-center text-sm">
                {{ t('common.loading') }}
              </td>
            </tr>
            <tr v-else-if="!items.length">
              <td colspan="5" class="admin-muted py-5 text-center text-sm">
                {{ t('admin.metro.stations.index.empty') }}
              </td>
            </tr>
            <tr v-for="item in items" :key="item.id">
              <td>{{ item.name }}</td>
              <td>{{ item.line_id || t('common.dash') }}</td>
              <td class="font-mono text-xs">
                <AdminMetroLineBadge
                  :to="resolveMetroLineLink(item)"
                  :name="resolveMetroLineName(item)"
                  :color="resolveMetroLineColor(item)"
                  label-class="font-sans text-sm"
                />
              </td>
              <td class="text-xs">
                <AdminLink :to="resolveCityLink(item)">
                  {{ resolveCityName(item) }}
                </AdminLink>
              </td>
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

    <template #cards>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article v-for="item in items" :key="item.id" class="admin-entity-card rounded-xl p-4">
          <h4 class="text-sm font-semibold">{{ item.name }}</h4>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.metro.stations.index.card.lineId', {
                value: item.line_id || t('common.dash'),
              })
            }}
          </p>
          <p class="admin-muted text-xs">
            {{
              t('admin.metro.stations.index.card.metroLine', { value: resolveMetroLineName(item) })
            }}
          </p>
          <p class="admin-muted text-xs">
            {{ t('admin.metro.stations.index.card.city', { value: resolveCityName(item) }) }}
          </p>

          <div class="mt-3">
            <AdminCrudActions
              :show-to="`/admin/metro-stations/${item.id}`"
              :edit-to="`/admin/metro-stations/${item.id}/edit`"
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
import AdminMetroLineBadge from '~/components/admin/Metro/AdminMetroLineBadge.vue';
import AdminCrudActions from '~/components/admin/Listing/AdminCrudActions/AdminCrudActions.vue';
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex/AdminEntityIndex.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import { useDebouncedSearch } from '~/composables/useAsyncSelectOptions';
import type { AdminMetroStation } from '~/composables/useAdminMetroStations';

const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.metro.read',
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
  listErrorMessage: t('admin.metro.stations.errors.loadList'),
  deleteErrorMessage: t('admin.metro.stations.errors.delete'),
  list: api.list,
  remove: api.remove,
  getItemId: (item) => item.id,
});

const cardSortFields = computed(() => [
  { value: 'name', label: t('admin.metro.stations.index.sort.name') },
  { value: 'line_id', label: t('admin.metro.stations.index.sort.lineId') },
  { value: 'metro_line_id', label: t('admin.metro.stations.index.sort.metroLine') },
  { value: 'city_id', label: t('admin.metro.stations.index.sort.city') },
]);

const onModeChange = (mode: 'table' | 'table-cards' | 'cards') => {
  contentMode.value = mode;
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
};

const getEmbeddedMetroLine = (
  item: AdminMetroStation
): { name?: string | null; color?: string | null } | null => {
  return item.metro_line ?? null;
};

const resolveMetroLineName = (item: AdminMetroStation): string => {
  const embedded = getEmbeddedMetroLine(item);
  if (embedded?.name) {
    return embedded.name;
  }

  return item.metro_line_id || t('common.dash');
};

const resolveMetroLineColor = (item: AdminMetroStation): string | null => {
  const embedded = getEmbeddedMetroLine(item);
  if (embedded?.color) {
    return embedded.color;
  }

  return null;
};

const getEmbeddedCity = (item: AdminMetroStation): { name?: string | null } | null => {
  return item.city ?? null;
};

const resolveCityName = (item: AdminMetroStation): string => {
  const embedded = getEmbeddedCity(item);
  if (embedded?.name) {
    return embedded.name;
  }

  return item.city_id || t('common.dash');
};

const resolveMetroLineLink = (item: AdminMetroStation): string => {
  return `/admin/metro-lines/${item.metro_line_id}`;
};

const resolveCityLink = (item: AdminMetroStation): string => {
  return `/admin/geo/cities/${item.city_id}`;
};

const onRemove = (item: AdminMetroStation) => {
  removeItem(item, {
    confirmTitle: t('admin.actions.delete'),
    confirmMessage: t('admin.metro.stations.confirmDelete', { name: item.name }),
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

<style lang="scss" scoped src="../_shared/admin-index-page.scss"></style>
