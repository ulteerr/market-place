<template>
  <AdminEntityIndex
    page-class="metro-lines-page"
    max-width-class="max-w-7xl"
    :title="t('admin.metro.lines.index.title')"
    :subtitle="t('admin.metro.lines.index.subtitle')"
    create-to="/admin/metro-lines/new"
    :show-create="true"
    :create-label="t('admin.metro.lines.index.createLabel')"
    :search-value="listState.searchInput.value"
    :search-placeholder="t('admin.metro.lines.index.searchPlaceholder')"
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
        <table class="admin-table min-w-[860px]">
          <thead>
            <tr>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('name')">
                  {{ t('admin.metro.lines.index.headers.name') }}
                  {{ listState.sortMark('name') }}
                </button>
              </th>
              <th>{{ t('admin.metro.lines.index.headers.lineId') }}</th>
              <th>{{ t('admin.metro.lines.index.headers.color') }}</th>
              <th>{{ t('admin.metro.lines.index.headers.cityId') }}</th>
              <th class="text-right">{{ t('admin.metro.lines.index.headers.actions') }}</th>
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
                {{ t('admin.metro.lines.index.empty') }}
              </td>
            </tr>
            <tr v-for="item in items" :key="item.id">
              <td>{{ item.name }}</td>
              <td>{{ item.line_id || t('common.dash') }}</td>
              <td>
                <span v-if="item.color" class="inline-flex items-center gap-2">
                  <AdminColorDot :color="item.color" />
                </span>
                <span v-else class="font-mono text-xs">{{ t('common.dash') }}</span>
              </td>
              <td class="font-mono text-xs">{{ item.city_id }}</td>
              <td>
                <AdminCrudActions
                  :show-to="`/admin/metro-lines/${item.id}`"
                  :edit-to="`/admin/metro-lines/${item.id}/edit`"
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
              t('admin.metro.lines.index.card.lineId', { value: item.line_id || t('common.dash') })
            }}
          </p>
          <p class="admin-muted text-xs">
            {{ t('admin.metro.lines.index.card.color') }}
            <span v-if="item.color" class="ml-1 inline-flex align-middle">
              <AdminColorDot :color="item.color" />
            </span>
            <span v-else class="ml-1">{{ t('common.dash') }}</span>
          </p>
          <p class="admin-muted text-xs">
            {{ t('admin.metro.lines.index.card.cityId', { value: item.city_id }) }}
          </p>

          <div class="mt-3">
            <AdminCrudActions
              :show-to="`/admin/metro-lines/${item.id}`"
              :edit-to="`/admin/metro-lines/${item.id}/edit`"
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
import AdminColorDot from '~/components/admin/Metro/AdminColorDot.vue';
import AdminCrudActions from '~/components/admin/Listing/AdminCrudActions.vue';
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import type { AdminMetroLine } from '~/composables/useAdminMetroLines';

const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.panel.access',
});

const api = useAdminMetroLines();
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
} = useAdminCrudIndex<AdminMetroLine>({
  settingsKey: 'metro-lines',
  defaultSortBy: 'name',
  defaultPerPage: 20,
  listErrorMessage: t('admin.metro.lines.errors.loadList'),
  deleteErrorMessage: t('admin.metro.lines.errors.delete'),
  list: api.list,
  remove: api.remove,
  getItemId: (item) => item.id,
});

const cardSortFields = computed(() => [
  { value: 'name', label: t('admin.metro.lines.index.sort.name') },
  { value: 'line_id', label: t('admin.metro.lines.index.sort.lineId') },
]);

const onModeChange = (mode: 'table' | 'table-cards' | 'cards') => {
  contentMode.value = mode;
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
};

const onRemove = (item: AdminMetroLine) => {
  removeItem(item, {
    confirmTitle: t('admin.actions.delete'),
    confirmMessage: t('admin.metro.lines.confirmDelete', { name: item.name }),
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

onMounted(() => {
  searchAutoReady.value = true;
});

onBeforeUnmount(() => {
  if (searchAutoTimer) clearTimeout(searchAutoTimer);
});
</script>

<style lang="scss" scoped src="../roles/index.scss"></style>
