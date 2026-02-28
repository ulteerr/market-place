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
        <article v-for="item in items" :key="item.id" class="role-card rounded-xl p-4">
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
import AdminLink from '~/components/admin/AdminLink.vue';
import AdminMetroLineBadge from '~/components/admin/Metro/AdminMetroLineBadge.vue';
import AdminCrudActions from '~/components/admin/Listing/AdminCrudActions.vue';
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import type { AdminGeoCity } from '~/composables/useAdminGeoCities';
import type { AdminMetroLine } from '~/composables/useAdminMetroLines';
import type { AdminMetroStation } from '~/composables/useAdminMetroStations';

const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.panel.access',
});

const api = useAdminMetroStations();
const metroLinesApi = useAdminMetroLines();
const geoCitiesApi = useAdminGeoCities();
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

const metroLineLookup = ref<Record<string, Pick<AdminMetroLine, 'name' | 'color'>>>({});
const cityLookup = ref<Record<string, Pick<AdminGeoCity, 'name'>>>({});

const getEmbeddedMetroLine = (
  item: AdminMetroStation
): { name?: string | null; color?: string | null } | null => {
  const payload = item as unknown as {
    metro_line?: { name?: string | null; color?: string | null };
  };
  return payload.metro_line ?? null;
};

const resolveMetroLineName = (item: AdminMetroStation): string => {
  const embedded = getEmbeddedMetroLine(item);
  if (embedded?.name) {
    return embedded.name;
  }

  return metroLineLookup.value[item.metro_line_id]?.name || item.metro_line_id || t('common.dash');
};

const resolveMetroLineColor = (item: AdminMetroStation): string | null => {
  const embedded = getEmbeddedMetroLine(item);
  if (embedded?.color) {
    return embedded.color;
  }

  return metroLineLookup.value[item.metro_line_id]?.color || null;
};

const getEmbeddedCity = (item: AdminMetroStation): { name?: string | null } | null => {
  const payload = item as unknown as {
    city?: { name?: string | null };
  };
  return payload.city ?? null;
};

const resolveCityName = (item: AdminMetroStation): string => {
  const embedded = getEmbeddedCity(item);
  if (embedded?.name) {
    return embedded.name;
  }

  return cityLookup.value[item.city_id]?.name || item.city_id || t('common.dash');
};

const hydrateMetroLineLookup = async () => {
  const hasItems = items.value.length > 0;
  if (!hasItems) {
    return;
  }

  const unresolvedLineIds = [...new Set(items.value.map((item) => item.metro_line_id))]
    .filter(Boolean)
    .filter((id) => !metroLineLookup.value[id]);

  if (!unresolvedLineIds.length) {
    return;
  }

  const resolvedEntries = await Promise.allSettled(
    unresolvedLineIds.map(async (id) => {
      const line = await metroLinesApi.show(id);
      return [id, { name: line.name, color: line.color ?? null }] as const;
    })
  );

  const nextLookup = { ...metroLineLookup.value };
  for (const entry of resolvedEntries) {
    if (entry.status !== 'fulfilled') {
      continue;
    }
    const [id, value] = entry.value;
    nextLookup[id] = value;
  }

  metroLineLookup.value = nextLookup;
};

const resolveMetroLineLink = (item: AdminMetroStation): string => {
  return `/admin/metro-lines/${item.metro_line_id}`;
};

const resolveCityLink = (item: AdminMetroStation): string => {
  return `/admin/geo/cities/${item.city_id}`;
};

const hydrateCityLookup = async () => {
  const hasItems = items.value.length > 0;
  if (!hasItems) {
    return;
  }

  const unresolvedCityIds = [...new Set(items.value.map((item) => item.city_id))]
    .filter(Boolean)
    .filter((id) => !cityLookup.value[id]);

  if (!unresolvedCityIds.length) {
    return;
  }

  const resolvedEntries = await Promise.allSettled(
    unresolvedCityIds.map(async (id) => {
      const city = await geoCitiesApi.show(id);
      return [id, { name: city.name }] as const;
    })
  );

  const nextLookup = { ...cityLookup.value };
  for (const entry of resolvedEntries) {
    if (entry.status !== 'fulfilled') {
      continue;
    }
    const [id, value] = entry.value;
    nextLookup[id] = value;
  }

  cityLookup.value = nextLookup;
};

const onRemove = (item: AdminMetroStation) => {
  removeItem(item, {
    confirmTitle: t('admin.actions.delete'),
    confirmMessage: t('admin.metro.stations.confirmDelete', { name: item.name }),
    confirmLabel: t('admin.actions.delete'),
    cancelLabel: t('common.cancel'),
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

watch(
  () => items.value.map((item) => item.metro_line_id).join(','),
  () => {
    void hydrateMetroLineLookup();
  },
  { immediate: true }
);

watch(
  () => items.value.map((item) => item.city_id).join(','),
  () => {
    void hydrateCityLookup();
  },
  { immediate: true }
);

onMounted(() => {
  searchAutoReady.value = true;
});

onBeforeUnmount(() => {
  if (searchAutoTimer) clearTimeout(searchAutoTimer);
});
</script>

<style lang="scss" scoped src="../roles/index.scss"></style>
