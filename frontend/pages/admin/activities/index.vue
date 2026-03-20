<template>
  <section v-if="!isHydrated" class="admin-index-page mx-auto w-full max-w-7xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.activities.index.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.activities.index.subtitle') }}</p>
    </div>
  </section>

  <AdminEntityIndex
    v-else
    page-class="activities-page"
    max-width-class="max-w-7xl"
    :title="t('admin.activities.index.title')"
    :subtitle="t('admin.activities.index.subtitle')"
    create-to="/admin/activities/new"
    :show-create="canCreateActivities"
    :create-label="t('admin.activities.index.createLabel')"
    :search-value="listState.searchInput.value"
    :search-placeholder="t('admin.activities.index.searchPlaceholder')"
    :show-apply="false"
    :per-page="listState.perPage.value"
    :per-page-options="listState.perPageOptions"
    :loading="loading"
    :shown-count="activities.length"
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
    @update:mode="onModeChange"
    @toggle-desktop="onToggleDesktopMode"
    @reset="onResetFilters"
    @sort="onToggleSort"
    @page="fetchActivities"
  >
    <template #filters>
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <UiSelect
          v-model="statusFilter"
          :label="t('admin.activities.filters.status')"
          :options="statusOptions"
          :placeholder="t('admin.activities.filters.allStatuses')"
        />
        <UiSelect
          v-model="featuredFilter"
          :label="t('admin.activities.filters.featured')"
          :options="featuredOptions"
          :placeholder="t('admin.activities.filters.allFeatured')"
        />
      </div>
    </template>

    <template #table>
      <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
        <table class="admin-table min-w-[1120px]">
          <thead>
            <tr>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('name')">
                  {{ t('admin.activities.index.headers.name') }}
                  {{ listState.sortMark('name') }}
                </button>
              </th>
              <th>{{ t('admin.activities.index.headers.organization') }}</th>
              <th>{{ t('admin.activities.index.headers.location') }}</th>
              <th>{{ t('admin.activities.index.headers.category') }}</th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('status')">
                  {{ t('admin.activities.index.headers.status') }}
                  {{ listState.sortMark('status') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('is_featured')">
                  {{ t('admin.activities.index.headers.featured') }}
                  {{ listState.sortMark('is_featured') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('published_at')">
                  {{ t('admin.activities.index.headers.publishedAt') }}
                  {{ listState.sortMark('published_at') }}
                </button>
              </th>
              <th class="text-right">{{ t('admin.activities.index.headers.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="admin-muted py-5 text-center text-sm">
                {{ t('common.loading') }}
              </td>
            </tr>
            <tr v-else-if="!activities.length">
              <td colspan="8" class="admin-muted py-5 text-center text-sm">
                {{ t('admin.activities.index.empty') }}
              </td>
            </tr>
            <tr v-for="activity in activities" :key="activity.id">
              <td>
                <div class="space-y-1">
                  <p class="font-medium">{{ activity.name }}</p>
                  <p class="admin-muted text-xs font-mono">{{ activity.slug }}</p>
                </div>
              </td>
              <td>{{ activity.organization?.name || t('common.dash') }}</td>
              <td>{{ resolveLocationLabel(activity) }}</td>
              <td>{{ resolveCategoryLabel(activity) }}</td>
              <td>{{ resolveStatusLabel(activity.status) }}</td>
              <td>
                {{
                  activity.is_featured
                    ? t('admin.activities.labels.featuredYes')
                    : t('admin.activities.labels.featuredNo')
                }}
              </td>
              <td>{{ resolvePublishedAt(activity.published_at) }}</td>
              <td>
                <AdminCrudActions
                  :show-to="`/admin/activities/${activity.id}`"
                  :edit-to="`/admin/activities/${activity.id}/edit`"
                  :can-show="true"
                  :can-edit="hasPermission('admin.activities.update')"
                  :can-delete="canDeleteActivities"
                  :deleting="deletingId === activity.id"
                  align="end"
                  @delete="removeActivity(activity)"
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
          v-for="activity in activities"
          :key="activity.id"
          class="admin-card rounded-xl p-4"
        >
          <h4 class="text-base font-medium">{{ activity.name }}</h4>
          <p class="admin-muted mt-1 text-xs font-mono">{{ activity.slug }}</p>
          <p class="admin-muted mt-2 text-xs">
            {{
              t('admin.activities.index.card.organization', {
                value: activity.organization?.name || t('common.dash'),
              })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.activities.index.card.location', { value: resolveLocationLabel(activity) })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.activities.index.card.category', { value: resolveCategoryLabel(activity) })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.activities.index.card.status', {
                value: resolveStatusLabel(activity.status),
              })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.activities.index.card.featured', {
                value: activity.is_featured
                  ? t('admin.activities.labels.featuredYes')
                  : t('admin.activities.labels.featuredNo'),
              })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.activities.index.card.publishedAt', {
                value: resolvePublishedAt(activity.published_at),
              })
            }}
          </p>
          <div class="mt-3">
            <AdminCrudActions
              :show-to="`/admin/activities/${activity.id}`"
              :edit-to="`/admin/activities/${activity.id}/edit`"
              :can-show="true"
              :can-edit="hasPermission('admin.activities.update')"
              :can-delete="canDeleteActivities"
              :deleting="deletingId === activity.id"
              @delete="removeActivity(activity)"
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
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import { useDebouncedSearch } from '~/composables/useAsyncSelectOptions';
import type { AdminActivity } from '~/composables/useAdminActivities';

const { t, locale } = useI18n();
const { hasPermission } = usePermissions();
const route = useRoute();
const isHydrated = ref(false);

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.activities.read',
});

const activitiesApi = useAdminActivities();
const statusFilter = ref('');
const featuredFilter = ref<string | boolean>('');
const canCreateActivities = computed(() => hasPermission('admin.activities.create'));
const canDeleteActivities = computed(() => hasPermission('admin.activities.delete'));

const {
  listState,
  items: activities,
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
  fetchItems: fetchActivities,
  onToggleSort,
  onResetFilters: baseResetFilters,
  onUpdatePerPage,
  removeItem,
  confirmRemoveItem,
  cancelRemoveItem,
} = useAdminCrudIndex<AdminActivity>({
  settingsKey: 'activities',
  defaultSortBy: 'created_at',
  allowedSortBy: ['name', 'status', 'is_featured', 'published_at', 'created_at'],
  listErrorMessage: t('admin.activities.errors.loadList'),
  deleteErrorMessage: t('admin.activities.errors.delete'),
  list: activitiesApi.list,
  remove: activitiesApi.remove,
  getItemId: (activity) => activity.id,
  buildCustomListQuery: () => ({
    status: statusFilter.value || undefined,
    is_featured: featuredFilter.value === '' ? '' : Boolean(featuredFilter.value),
  }),
  buildCustomQuery: () => ({
    status: statusFilter.value || undefined,
    is_featured: featuredFilter.value === '' ? undefined : featuredFilter.value ? '1' : '0',
  }),
  readCustomStateFromQuery: () => {
    statusFilter.value = String(route.query.status ?? '');

    const featuredValue = route.query.is_featured;
    if (featuredValue === '1' || featuredValue === 'true') {
      featuredFilter.value = true;
    } else if (featuredValue === '0' || featuredValue === 'false') {
      featuredFilter.value = false;
    } else {
      featuredFilter.value = '';
    }
  },
  resetCustomFilters: () => {
    statusFilter.value = '';
    featuredFilter.value = '';
  },
});

const cardSortFields = computed(() => [
  { value: 'created_at', label: t('admin.activities.index.sort.createdAt') },
  { value: 'name', label: t('admin.activities.index.sort.name') },
  { value: 'status', label: t('admin.activities.index.sort.status') },
  { value: 'published_at', label: t('admin.activities.index.sort.publishedAt') },
  { value: 'is_featured', label: t('admin.activities.index.sort.featured') },
]);

const statusOptions = computed(() => [
  { value: 'draft', label: t('admin.activities.status.draft') },
  { value: 'pending_review', label: t('admin.activities.status.pendingReview') },
  { value: 'published', label: t('admin.activities.status.published') },
  { value: 'archived', label: t('admin.activities.status.archived') },
]);

const featuredOptions = computed(() => [
  { value: true, label: t('admin.activities.filters.featuredOnly') },
  { value: false, label: t('admin.activities.filters.notFeatured') },
]);

const onResetFilters = () => {
  baseResetFilters();
};

const onModeChange = (value: 'table' | 'table-cards' | 'cards') => {
  contentMode.value = value;
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
};

const debouncedFetch = useDebouncedSearch(() => {
  void fetchActivities(1);
}, 300);

watch(
  () => listState.searchInput.value,
  () => {
    debouncedFetch();
  }
);

watch([statusFilter, featuredFilter], () => {
  void fetchActivities(1);
});

const resolveStatusLabel = (status: string): string => {
  if (status === 'draft') return t('admin.activities.status.draft');
  if (status === 'pending_review') return t('admin.activities.status.pendingReview');
  if (status === 'published') return t('admin.activities.status.published');
  if (status === 'archived') return t('admin.activities.status.archived');

  return status;
};

const resolveLocationLabel = (activity: AdminActivity): string => {
  const city = activity.location?.city?.name;
  const address = activity.location?.address;

  if (city && address) {
    return `${city}, ${address}`;
  }

  return city || address || t('common.dash');
};

const resolveCategoryLabel = (activity: AdminActivity): string => {
  const leaf = activity.primary_category?.name;
  const root = activity.primary_category?.parent?.name;

  if (root && leaf) {
    return `${root} / ${leaf}`;
  }

  return leaf || t('common.dash');
};

const resolvePublishedAt = (value?: string | null): string => {
  if (!value) {
    return t('common.dash');
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat(locale.value, {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(date);
};

const removeActivity = (activity: AdminActivity) => {
  removeItem(activity, {
    canDelete: canDeleteActivities.value,
    confirmMessage: t('admin.activities.confirmDelete', { name: activity.name }),
    confirmLabel: t('admin.actions.delete'),
    cancelLabel: t('common.cancel'),
  });
};

onMounted(() => {
  isHydrated.value = true;
});
</script>
