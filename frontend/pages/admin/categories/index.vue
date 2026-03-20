<template>
  <AdminEntityIndex
    page-class="categories-page"
    max-width-class="max-w-7xl"
    :title="t('admin.categories.index.title')"
    :subtitle="t('admin.categories.index.subtitle')"
    create-to="/admin/categories/new"
    :show-create="canCreateCategories"
    :create-label="t('admin.categories.index.createLabel')"
    :search-value="listState.searchInput.value"
    :search-placeholder="t('admin.categories.index.searchPlaceholder')"
    :show-apply="false"
    :per-page="listState.perPage.value"
    :per-page-options="listState.perPageOptions"
    :loading="loading"
    :shown-count="categories.length"
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
    @reset="onResetFilters"
    @sort="onToggleSort"
    @page="fetchCategories"
  >
    <template #filters>
      <div
        v-if="activeParentFilterName"
        class="flex flex-wrap items-center gap-2 rounded-xl border border-[var(--border)] px-3 py-2"
      >
        <p class="admin-muted text-sm">
          {{ t('admin.categories.index.filteredByParent', { value: activeParentFilterName }) }}
        </p>
        <button
          type="button"
          class="admin-button-secondary rounded-md px-2 py-1 text-xs"
          @click="clearParentFilter"
        >
          {{ t('admin.categories.index.clearParentFilter') }}
        </button>
      </div>
    </template>

    <template #table>
      <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
        <table class="admin-table min-w-[920px]">
          <thead>
            <tr>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('name')">
                  {{ t('admin.categories.index.headers.name') }}
                  {{ listState.sortMark('name') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('slug')">
                  {{ t('admin.categories.index.headers.slug') }}
                  {{ listState.sortMark('slug') }}
                </button>
              </th>
              <th>{{ t('admin.categories.index.headers.parent') }}</th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('sort_order')">
                  {{ t('admin.categories.index.headers.sortOrder') }}
                  {{ listState.sortMark('sort_order') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('is_active')">
                  {{ t('admin.categories.index.headers.status') }}
                  {{ listState.sortMark('is_active') }}
                </button>
              </th>
              <th class="text-center">{{ t('admin.categories.index.headers.children') }}</th>
              <th class="text-center">{{ t('admin.categories.index.headers.activities') }}</th>
              <th class="text-right">{{ t('admin.categories.index.headers.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="admin-muted py-5 text-center text-sm">
                {{ t('common.loading') }}
              </td>
            </tr>
            <tr v-else-if="!categories.length">
              <td colspan="8" class="admin-muted py-5 text-center text-sm">
                {{ t('admin.categories.index.empty') }}
              </td>
            </tr>
            <tr v-for="category in categories" :key="category.id">
              <td class="font-medium">{{ category.name }}</td>
              <td class="font-mono text-xs">{{ category.slug }}</td>
              <td>{{ resolveParentLabel(category) }}</td>
              <td>{{ category.sort_order }}</td>
              <td>{{ resolveStatusLabel(category.is_active) }}</td>
              <td class="text-center">
                <button
                  v-if="(category.children_count ?? 0) > 0"
                  type="button"
                  class="admin-button-secondary rounded-md px-2 py-1 text-xs"
                  @click="openChildren(category)"
                >
                  {{ category.children_count }}
                </button>
                <span v-else class="admin-muted text-xs">0</span>
              </td>
              <td class="text-center">{{ category.activities_count ?? 0 }}</td>
              <td>
                <AdminCrudActions
                  :show-to="`/admin/categories/${category.id}`"
                  :edit-to="`/admin/categories/${category.id}/edit`"
                  :can-show="true"
                  :can-edit="canEditCategories"
                  :can-delete="canDeleteCategories"
                  :deleting="deletingId === category.id"
                  align="end"
                  @delete="removeCategory(category)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <template #cards>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="category in categories"
          :key="category.id"
          class="admin-card rounded-xl p-4"
        >
          <h4 class="text-base font-medium">{{ category.name }}</h4>
          <p class="admin-muted mt-1 text-xs">
            {{ t('admin.categories.index.card.slug', { value: category.slug }) }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{ t('admin.categories.index.card.parent', { value: resolveParentLabel(category) }) }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.categories.index.card.sortOrder', {
                value: String(category.sort_order),
              })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.categories.index.card.status', {
                value: resolveStatusLabel(category.is_active),
              })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.categories.index.card.children', {
                value: String(category.children_count ?? 0),
              })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.categories.index.card.activities', {
                value: String(category.activities_count ?? 0),
              })
            }}
          </p>
          <div class="mt-3">
            <AdminCrudActions
              :show-to="`/admin/categories/${category.id}`"
              :edit-to="`/admin/categories/${category.id}/edit`"
              :can-show="true"
              :can-edit="canEditCategories"
              :can-delete="canDeleteCategories"
              :deleting="deletingId === category.id"
              @delete="removeCategory(category)"
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
import UiModal from '~/components/ui/Modal/UiModal.vue';
import { useDebouncedSearch } from '~/composables/useAsyncSelectOptions';
import type { AdminCategory } from '~/composables/useAdminCategories';

const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.categories.read',
});

const categoriesApi = useAdminCategories();
const route = useRoute();
const router = useRouter();
const { hasPermission } = usePermissions();
const canCreateCategories = computed(() => hasPermission('admin.categories.create'));
const canEditCategories = computed(() => hasPermission('admin.categories.update'));
const canDeleteCategories = computed(() => hasPermission('admin.categories.delete'));
const parentFilterId = ref('');

const {
  listState,
  items: categories,
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
  fetchItems: fetchCategories,
  onToggleSort,
  onResetFilters,
  onUpdatePerPage,
  removeItem,
  confirmRemoveItem,
  cancelRemoveItem,
} = useAdminCrudIndex<AdminCategory>({
  settingsKey: 'categories',
  defaultSortBy: 'sort_order',
  defaultPerPage: 20,
  allowedSortBy: ['name', 'slug', 'sort_order', 'is_active', 'created_at', 'updated_at'],
  listErrorMessage: t('admin.categories.errors.loadList'),
  deleteErrorMessage: t('admin.categories.errors.delete'),
  list: categoriesApi.list,
  remove: categoriesApi.remove,
  getItemId: (category) => category.id,
  readCustomStateFromQuery: () => {
    const parentId = route.query.parent_id;
    parentFilterId.value = typeof parentId === 'string' && parentId.trim() !== '' ? parentId : '';
  },
  buildCustomListQuery: () => {
    return {
      parent_id:
        typeof parentFilterId.value === 'string' && parentFilterId.value.trim() !== ''
          ? parentFilterId.value
          : undefined,
    };
  },
  buildCustomQuery: () => {
    return {
      parent_id:
        typeof parentFilterId.value === 'string' && parentFilterId.value.trim() !== ''
          ? parentFilterId.value
          : undefined,
    };
  },
  resetCustomFilters: () => {
    parentFilterId.value = '';
  },
});

const cardSortFields = computed(() => [
  { value: 'sort_order', label: t('admin.categories.index.sort.sortOrder') },
  { value: 'name', label: t('admin.categories.index.sort.name') },
  { value: 'slug', label: t('admin.categories.index.sort.slug') },
  { value: 'is_active', label: t('admin.categories.index.sort.status') },
]);

const activeParentFilterName = computed(() => {
  if (!parentFilterId.value) {
    return '';
  }

  const match = categories.value.find((category) => category.parent_id === parentFilterId.value);
  if (match?.parent?.name) {
    return match.parent.name;
  }

  return parentFilterId.value;
});

const resolveParentLabel = (category: AdminCategory): string => {
  return category.parent?.name || t('admin.categories.index.root');
};

const resolveStatusLabel = (isActive: boolean): string => {
  return isActive ? t('admin.categories.status.active') : t('admin.categories.status.inactive');
};

const openChildren = async (category: AdminCategory) => {
  await router.push({
    path: '/admin/categories',
    query: {
      parent_id: category.id,
    },
  });

  parentFilterId.value = category.id;
  await fetchCategories(1);
};

const clearParentFilter = async () => {
  await router.push({
    path: '/admin/categories',
    query: {
      ...route.query,
      parent_id: undefined,
    },
  });

  parentFilterId.value = '';
  await fetchCategories(1);
};

const onModeChange = (mode: 'table' | 'table-cards' | 'cards') => {
  contentMode.value = mode;
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
};

const removeCategory = (category: AdminCategory) => {
  removeItem(category, {
    canDelete: canDeleteCategories.value,
    confirmTitle: t('admin.actions.delete'),
    confirmMessage: t('admin.categories.confirmDelete', { name: category.name }),
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

    fetchCategories(listState.applySearch());
  },
  { delay: 300, skipInitial: true }
);
</script>

<style lang="scss" scoped src="../_shared/admin-index-page.scss"></style>
