<template>
  <AdminEntityIndex
    page-class="action-logs-page"
    max-width-class="max-w-7xl"
    :title="t('admin.actionLogs.title')"
    :subtitle="t('admin.actionLogs.subtitle')"
    :show-create="false"
    :search-value="searchInput"
    :search-placeholder="t('admin.actionLogs.filters.searchPlaceholder')"
    :show-apply="false"
    :per-page="perPage"
    :per-page-options="perPageOptions"
    :loading="loading"
    :shown-count="items.length"
    :total-count="pagination.total"
    :load-error="loadError"
    :mode="contentMode"
    :table-on-desktop="tableOnDesktop"
    :card-sort-fields="cardSortFields"
    :active-sort-by="sortBy"
    :sort-mark="sortMark"
    :show-pagination="showPagination"
    :current-page="pagination.current_page"
    :last-page="pagination.last_page"
    :pagination-per-page="pagination.per_page"
    :pagination-items="paginationItems"
    :table-skeleton-columns="7"
    @update:search-value="(value) => (searchInput = value)"
    @update:per-page="onPerPageChange"
    @update:mode="onModeChange"
    @toggle-desktop="onToggleDesktopMode"
    @reset="onResetFilters"
    @sort="onSort"
    @page="onPageChange"
  >
    <template #filters>
      <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
        <UiSelect
          :model-value="eventFilter"
          :label="t('admin.actionLogs.filters.event')"
          :options="eventOptions"
          :searchable="false"
          :placeholder="t('admin.actionLogs.filters.anyEvent')"
          @update:model-value="onEventChange"
        />
        <UiSelect
          :model-value="modelFilter"
          :label="t('admin.actionLogs.filters.model')"
          :options="modelOptions"
          :searchable="false"
          :placeholder="t('admin.actionLogs.filters.anyModel')"
          @update:model-value="onModelChange"
        />
        <UiInput
          :model-value="userFilter"
          :label="t('admin.actionLogs.filters.user')"
          :placeholder="t('admin.actionLogs.filters.userPlaceholder')"
          @update:model-value="(value) => (userFilter = String(value || ''))"
        />
        <UiDatePicker
          mode="range"
          :model-value="dateRangeFilter"
          :label="t('admin.actionLogs.filters.dateRange')"
          :placeholder-start="t('admin.actionLogs.filters.dateFrom')"
          :placeholder-end="t('admin.actionLogs.filters.dateTo')"
          @update:model-value="onDateRangeChange"
        />
      </div>
    </template>

    <template #table>
      <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
        <table class="admin-table min-w-[960px]">
          <thead>
            <tr>
              <th>
                <button type="button" class="th-sort" @click="onSort('created_at')">
                  {{ t('admin.actionLogs.headers.createdAt') }} {{ getSortIndicator('created_at') }}
                </button>
              </th>
              <th>
                <button type="button" class="th-sort" @click="onSort('event')">
                  {{ t('admin.actionLogs.headers.event') }} {{ getSortIndicator('event') }}
                </button>
              </th>
              <th>
                <button type="button" class="th-sort" @click="onSort('model_type')">
                  {{ t('admin.actionLogs.headers.model') }} {{ getSortIndicator('model_type') }}
                </button>
              </th>
              <th>
                <button type="button" class="th-sort" @click="onSort('user_id')">
                  {{ t('admin.actionLogs.headers.user') }} {{ getSortIndicator('user_id') }}
                </button>
              </th>
              <th>{{ t('admin.actionLogs.headers.changedFields') }}</th>
              <th>{{ t('admin.actionLogs.headers.ip') }}</th>
              <th class="text-right">{{ t('admin.actions.show') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!items.length">
              <td colspan="7" class="py-6 text-center text-sm admin-muted">
                {{ t('admin.actionLogs.empty') }}
              </td>
            </tr>
            <template v-for="item in items" :key="item.id">
              <tr>
                <td>{{ formatDate(item.created_at) }}</td>
                <td>
                  <span class="event-chip" :class="`is-${item.event}`">{{
                    resolveEventLabel(item.event)
                  }}</span>
                </td>
                <td>{{ resolveModelTitle(item) }}</td>
                <td>
                  <AdminLink v-if="resolveActorLink(item)" :to="resolveActorLink(item)">
                    {{ resolveUserLabel(item) }}
                  </AdminLink>
                  <span v-else>{{ resolveUserLabel(item) }}</span>
                </td>
                <td class="max-w-[320px] truncate" :title="resolveChangedFields(item)">
                  {{ resolveChangedFields(item) }}
                </td>
                <td>{{ item.ip_address || t('common.dash') }}</td>
                <td class="text-right">
                  <button
                    v-if="hasJsonPayload(item)"
                    type="button"
                    class="admin-button-secondary rounded-md px-3 py-1.5 text-xs"
                    @click="toggleJson(item.id)"
                  >
                    {{
                      isJsonExpanded(item.id)
                        ? t('admin.changelog.hideJson')
                        : t('admin.changelog.showJson')
                    }}
                  </button>
                </td>
              </tr>
              <tr v-if="isJsonExpanded(item.id)">
                <td colspan="7" class="json-row-cell">
                  <div
                    class="json-grid"
                    :class="{ 'json-grid-single': !item.before || !item.after }"
                  >
                    <div v-if="item.before" class="json-side json-side-before rounded-lg p-2">
                      <p class="json-label mb-1 text-xs">{{ t('admin.changelog.before') }}</p>
                      <pre class="json-content">{{ toJson(item.before) }}</pre>
                    </div>
                    <div v-if="item.after" class="json-side json-side-after rounded-lg p-2">
                      <p class="json-label mb-1 text-xs">{{ t('admin.changelog.after') }}</p>
                      <pre class="json-content">{{ toJson(item.after) }}</pre>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </template>

    <template #cards>
      <div
        v-if="!items.length"
        class="admin-muted rounded-xl border border-[var(--border)] p-6 text-sm"
      >
        {{ t('admin.actionLogs.empty') }}
      </div>
      <div v-else class="grid gap-3 sm:grid-cols-2">
        <article v-for="item in items" :key="item.id" class="admin-card rounded-xl p-4">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="event-chip" :class="`is-${item.event}`">{{
              resolveEventLabel(item.event)
            }}</span>
            <span class="admin-muted text-xs">{{ formatDate(item.created_at) }}</span>
          </div>
          <p class="mt-2 text-sm">{{ resolveModelTitle(item) }}</p>
          <p class="mt-1 text-sm">
            <span class="admin-muted">{{ t('admin.actionLogs.headers.user') }}: </span>
            <AdminLink v-if="resolveActorLink(item)" :to="resolveActorLink(item)">
              {{ resolveUserLabel(item) }}
            </AdminLink>
            <span v-else>{{ resolveUserLabel(item) }}</span>
          </p>
          <p class="mt-1 text-sm">
            <span class="admin-muted">{{ t('admin.actionLogs.headers.changedFields') }}: </span>
            {{ resolveChangedFields(item) }}
          </p>
          <p class="mt-1 text-sm">
            <span class="admin-muted">IP: </span>{{ item.ip_address || t('common.dash') }}
          </p>
          <div class="mt-3">
            <button
              v-if="hasJsonPayload(item)"
              type="button"
              class="admin-button-secondary rounded-md px-3 py-1.5 text-xs"
              @click="toggleJson(item.id)"
            >
              {{
                isJsonExpanded(item.id)
                  ? t('admin.changelog.hideJson')
                  : t('admin.changelog.showJson')
              }}
            </button>
          </div>
          <div
            v-if="isJsonExpanded(item.id)"
            class="mt-3 json-grid"
            :class="{ 'json-grid-single': !item.before || !item.after }"
          >
            <div v-if="item.before" class="json-side json-side-before rounded-lg p-2">
              <p class="json-label mb-1 text-xs">{{ t('admin.changelog.before') }}</p>
              <pre class="json-content">{{ toJson(item.before) }}</pre>
            </div>
            <div v-if="item.after" class="json-side json-side-after rounded-lg p-2">
              <p class="json-label mb-1 text-xs">{{ t('admin.changelog.after') }}</p>
              <pre class="json-content">{{ toJson(item.after) }}</pre>
            </div>
          </div>
        </article>
      </div>
    </template>
  </AdminEntityIndex>
</template>

<script setup lang="ts">
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex.vue';
import AdminLink from '~/components/admin/AdminLink.vue';
import UiInput from '~/components/ui/FormControls/UiInput.vue';
import UiDatePicker from '~/components/ui/FormControls/UiDatePicker.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect.vue';
import type { AdminActionLogItem } from '~/composables/useAdminActionLogs';
import type { AdminCrudContentMode } from '~/composables/useUserSettings';
import {
  buildPaginationItems,
  getApiErrorMessage,
  type PaginationPayload,
} from '~/composables/useAdminCrudCommon';

const { t, locale } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.action-log.read',
});

const route = useRoute();
const router = useRouter();
const actionLogsApi = useAdminActionLogs();
const { user: authUser } = useAuth();
const crudPreferences = useAdminCrudPreferences();

const loading = ref(false);
const loadError = ref('');
const items = ref<AdminActionLogItem[]>([]);
const perPageOptions = [10, 20, 30, 50, 100];
const isHydrating = ref(true);
const expandedJsonIds = ref<Set<string>>(new Set());
let autoApplyTimeout: ReturnType<typeof setTimeout> | null = null;

const searchInput = ref('');
const searchApplied = ref('');
const eventFilter = ref<string>('all');
const modelFilter = ref<string>('all');
const userFilter = ref('');
const dateRangeFilter = ref<[string | null, string | null]>([null, null]);
const page = ref(1);
const perPage = ref(20);
const contentMode = ref<AdminCrudContentMode>('table');
const tableOnDesktop = ref(true);
const sortBy = ref<'created_at' | 'event' | 'model_type' | 'user_id'>('created_at');
const sortDir = ref<'asc' | 'desc'>('desc');

const pagination = reactive<PaginationPayload<AdminActionLogItem>>({
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
});

const eventOptions = computed(() => [
  { value: 'all', label: t('admin.actionLogs.filters.eventAll') },
  { value: 'create', label: t('admin.actionLogs.filters.eventCreate') },
  { value: 'update', label: t('admin.actionLogs.filters.eventUpdate') },
  { value: 'delete', label: t('admin.actionLogs.filters.eventDelete') },
]);

const modelOptions = computed(() => [
  { value: 'all', label: t('admin.actionLogs.filters.modelAll') },
  { value: 'user', label: t('admin.actionLogs.filters.modelUser') },
  { value: 'role', label: t('admin.actionLogs.filters.modelRole') },
]);

const showPagination = computed(() => pagination.last_page > 1);
const paginationItems = computed(() =>
  buildPaginationItems(pagination.current_page, pagination.last_page, 5)
);
const cardSortFields = computed(() => [
  { value: 'created_at', label: t('admin.actionLogs.headers.createdAt') },
  { value: 'event', label: t('admin.actionLogs.headers.event') },
  { value: 'model_type', label: t('admin.actionLogs.headers.model') },
  { value: 'user_id', label: t('admin.actionLogs.headers.user') },
]);

const resolveUserLabel = (item: AdminActionLogItem): string => {
  return item.user?.full_name || item.user?.email || item.user_id || t('common.dash');
};

const resolveEventLabel = (event: string): string => {
  if (event === 'create') {
    return t('admin.actionLogs.events.create');
  }
  if (event === 'update') {
    return t('admin.actionLogs.events.update');
  }
  if (event === 'delete') {
    return t('admin.actionLogs.events.delete');
  }

  return event;
};

const resolveModelLabel = (modelType: string): string => {
  if (modelType.endsWith('\\User') || modelType === 'user') {
    return t('admin.actionLogs.models.user');
  }
  if (modelType.endsWith('\\Role') || modelType === 'role') {
    return t('admin.actionLogs.models.role');
  }

  const tail = modelType.split('\\').pop();
  return tail || modelType;
};

const resolveModelTitle = (item: AdminActionLogItem): string => {
  const before = item.before ?? {};
  const after = item.after ?? {};
  const modelLabel = resolveModelLabel(item.model_type);

  if (item.model_type.endsWith('\\User') || item.model_type === 'user') {
    return `${modelLabel} #${item.model_id}`;
  }

  if (item.model_type.endsWith('\\Role') || item.model_type === 'role') {
    const code =
      (typeof after.code === 'string' && after.code.trim()) ||
      (typeof before.code === 'string' && before.code.trim()) ||
      '';
    const title =
      (typeof after.label === 'string' && after.label.trim()) ||
      (typeof before.label === 'string' && before.label.trim()) ||
      '';
    if (title && code) {
      return `${modelLabel}: ${title} (${code})`;
    }
    return title
      ? `${modelLabel}: ${title}`
      : code
        ? `${modelLabel}: ${code}`
        : `${modelLabel} #${item.model_id}`;
  }

  return `${modelLabel} #${item.model_id}`;
};

const resolveActorLink = (item: AdminActionLogItem): string | null => {
  if (!item.user?.id) {
    return null;
  }
  const meId = authUser.value?.id;
  if (meId && item.user.id === meId) {
    return '/admin/profile';
  }
  return `/admin/users/${item.user.id}`;
};

const resolveChangedFields = (item: AdminActionLogItem): string => {
  if (!item.changed_fields || !item.changed_fields.length) {
    return t('common.dash');
  }

  return item.changed_fields.join(', ');
};

const hasJsonPayload = (item: AdminActionLogItem): boolean =>
  Boolean(item.before && Object.keys(item.before).length > 0) ||
  Boolean(item.after && Object.keys(item.after).length > 0);

const isJsonExpanded = (id: string): boolean => expandedJsonIds.value.has(id);

const toggleJson = (id: string): void => {
  const next = new Set(expandedJsonIds.value);
  if (next.has(id)) {
    next.delete(id);
  } else {
    next.add(id);
  }
  expandedJsonIds.value = next;
};

const toJson = (value: unknown): string => {
  try {
    return JSON.stringify(value ?? {}, null, 2);
  } catch {
    return String(value ?? '');
  }
};

const getSortIndicator = (field: 'created_at' | 'event' | 'model_type' | 'user_id'): string => {
  if (sortBy.value !== field) {
    return '';
  }
  return sortDir.value === 'asc' ? '↑' : '↓';
};

const onSort = async (field: string) => {
  if (
    field !== 'created_at' &&
    field !== 'event' &&
    field !== 'model_type' &&
    field !== 'user_id'
  ) {
    return;
  }

  if (sortBy.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = field;
    sortDir.value = 'asc';
  }
  page.value = 1;
  await syncQuery();
  await fetchItems();
};

const sortMark = (field: string): string => {
  if (sortBy.value !== field) {
    return '';
  }
  return sortDir.value === 'asc' ? '↑' : '↓';
};

const onModeChange = (mode: AdminCrudContentMode) => {
  contentMode.value = mode;
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
};

const formatDate = (value: string): string => {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat(locale.value, {
    dateStyle: 'short',
    timeStyle: 'medium',
  }).format(date);
};

const syncQuery = async () => {
  const nextQuery: Record<string, string> = {};

  if (searchApplied.value.trim()) {
    nextQuery.search = searchApplied.value.trim();
  }
  if (eventFilter.value !== 'all') {
    nextQuery.event = eventFilter.value;
  }
  if (modelFilter.value !== 'all') {
    nextQuery.model = modelFilter.value;
  }
  if (userFilter.value.trim()) {
    nextQuery.user = userFilter.value.trim();
  }
  if (dateRangeFilter.value[0]) {
    nextQuery.date_from = dateRangeFilter.value[0];
  }
  if (dateRangeFilter.value[1]) {
    nextQuery.date_to = dateRangeFilter.value[1];
  }
  if (page.value > 1) {
    nextQuery.page = String(page.value);
  }
  nextQuery.per_page = String(perPage.value);
  nextQuery.sort_by = sortBy.value;
  nextQuery.sort_dir = sortDir.value;

  await router.replace({ query: nextQuery });
};

const fetchItems = async () => {
  loading.value = true;
  loadError.value = '';

  try {
    const payload = await actionLogsApi.list({
      page: page.value,
      per_page: perPage.value,
      search: searchApplied.value.trim() || undefined,
      event: eventFilter.value === 'all' ? undefined : eventFilter.value,
      model: modelFilter.value === 'all' ? undefined : modelFilter.value,
      user: userFilter.value.trim() || undefined,
      date_from: dateRangeFilter.value[0] || undefined,
      date_to: dateRangeFilter.value[1] || undefined,
      sort_by: sortBy.value,
      sort_dir: sortDir.value,
    });

    items.value = payload.data;
    pagination.data = payload.data;
    pagination.current_page = payload.current_page;
    pagination.last_page = payload.last_page;
    pagination.per_page = payload.per_page;
    pagination.total = payload.total;
    expandedJsonIds.value = new Set();
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.actionLogs.errors.load'));
  } finally {
    loading.value = false;
  }
};

const onResetFilters = async () => {
  searchInput.value = '';
  searchApplied.value = '';
  eventFilter.value = 'all';
  modelFilter.value = 'all';
  userFilter.value = '';
  dateRangeFilter.value = [null, null];
  page.value = 1;
  perPage.value = 20;
  sortBy.value = 'created_at';
  sortDir.value = 'desc';
  expandedJsonIds.value = new Set();

  await syncQuery();
  await fetchItems();
};

const onEventChange = async (value: string | number | (string | number)[]) => {
  const next = Array.isArray(value) ? value[0] : value;
  eventFilter.value = String(next || 'all');
  page.value = 1;
  await syncQuery();
  await fetchItems();
};

const onModelChange = async (value: string | number | (string | number)[]) => {
  const next = Array.isArray(value) ? value[0] : value;
  modelFilter.value = String(next || 'all');
  page.value = 1;
  await syncQuery();
  await fetchItems();
};

const onDateRangeChange = (value: string | null | [string | null, string | null]) => {
  if (!Array.isArray(value)) {
    dateRangeFilter.value = [null, null];
  } else {
    const [from, to] = value;
    if (from && to && from > to) {
      dateRangeFilter.value = [to, from];
    } else {
      dateRangeFilter.value = [from ?? null, to ?? null];
    }
  }
};

const onPerPageChange = async (value: number) => {
  perPage.value = value;
  page.value = 1;
  await syncQuery();
  await fetchItems();
};

const onPageChange = async (nextPage: number) => {
  page.value = nextPage;
  await syncQuery();
  await fetchItems();
};

onMounted(async () => {
  const viewPreference = crudPreferences.getViewPreference('action_logs');
  if (viewPreference.contentMode) {
    contentMode.value = viewPreference.contentMode;
  }
  if (viewPreference.tableOnDesktop !== undefined) {
    tableOnDesktop.value = viewPreference.tableOnDesktop;
  }

  searchInput.value = String(route.query.search ?? '');
  searchApplied.value = searchInput.value;
  eventFilter.value = String(route.query.event ?? 'all');
  modelFilter.value = String(route.query.model ?? 'all');
  userFilter.value = String(route.query.user ?? '');
  const queryDateFrom = String(route.query.date_from ?? '').trim();
  const queryDateTo = String(route.query.date_to ?? '').trim();
  let normalizedDateQuery = false;
  if (queryDateFrom && queryDateTo && queryDateFrom > queryDateTo) {
    dateRangeFilter.value = [queryDateTo, queryDateFrom];
    normalizedDateQuery = true;
  } else {
    dateRangeFilter.value = [queryDateFrom || null, queryDateTo || null];
  }

  const queryPage = Number(route.query.page ?? 1);
  page.value = Number.isFinite(queryPage) && queryPage > 0 ? queryPage : 1;

  const queryPerPage = Number(route.query.per_page ?? 20);
  perPage.value = perPageOptions.includes(queryPerPage) ? queryPerPage : 20;

  const querySortBy = String(route.query.sort_by ?? 'created_at');
  if (
    querySortBy === 'created_at' ||
    querySortBy === 'event' ||
    querySortBy === 'model_type' ||
    querySortBy === 'user_id'
  ) {
    sortBy.value = querySortBy;
  }

  const querySortDir = String(route.query.sort_dir ?? 'desc').toLowerCase();
  sortDir.value = querySortDir === 'asc' ? 'asc' : 'desc';

  await fetchItems();
  if (
    normalizedDateQuery ||
    !route.query.per_page ||
    !route.query.sort_by ||
    !route.query.sort_dir
  ) {
    await syncQuery();
  }
  isHydrating.value = false;
});

watch([contentMode, tableOnDesktop], ([mode, desktop]) => {
  crudPreferences.updateViewPreference('action_logs', {
    contentMode: mode,
    tableOnDesktop: desktop,
  });
});

const scheduleAutoApply = () => {
  if (isHydrating.value) {
    return;
  }

  if (autoApplyTimeout) {
    clearTimeout(autoApplyTimeout);
  }

  autoApplyTimeout = setTimeout(async () => {
    searchApplied.value = searchInput.value;
    page.value = 1;
    await syncQuery();
    await fetchItems();
  }, 300);
};

watch([searchInput, userFilter], scheduleAutoApply);

watch(dateRangeFilter, async () => {
  if (isHydrating.value) {
    return;
  }

  page.value = 1;
  await syncQuery();
  await fetchItems();
});

onBeforeUnmount(() => {
  if (autoApplyTimeout) {
    clearTimeout(autoApplyTimeout);
    autoApplyTimeout = null;
  }
});
</script>

<style scoped lang="scss">
.admin-card {
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--surface) 80%, transparent);
}

.admin-muted {
  color: var(--muted);
}

.admin-error {
  color: #b91c1c;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
}

.admin-table th,
.admin-table td {
  border-bottom: 1px solid color-mix(in srgb, var(--border) 80%, transparent);
  padding: 0.75rem;
  text-align: left;
  font-size: 0.85rem;
}

.admin-table th {
  font-weight: 600;
  color: var(--muted);
}

.th-sort {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  color: inherit;
  background: transparent;
  border: 0;
  padding: 0;
  cursor: pointer;
}

.event-chip {
  display: inline-flex;
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 0.1rem 0.5rem;
  font-size: 0.75rem;
  text-transform: uppercase;
}

.event-chip.is-create {
  border-color: color-mix(in srgb, #22c55e 55%, var(--border));
  color: #4ade80;
}

.event-chip.is-update {
  border-color: color-mix(in srgb, #3b82f6 55%, var(--border));
  color: #93c5fd;
}

.event-chip.is-delete {
  border-color: color-mix(in srgb, #ef4444 55%, var(--border));
  color: #fca5a5;
}

.json-row-cell {
  padding: 0.75rem;
  background: color-mix(in srgb, var(--surface) 85%, transparent);
}

.json-grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: 1fr;
}

.json-grid-single {
  grid-template-columns: 1fr;
}

@media (min-width: 1024px) {
  .json-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.json-side {
  border: 1px solid var(--border);
  min-width: 0;
}

.json-side-before {
  border-color: color-mix(in srgb, #f97316 65%, var(--border));
}

.json-side-after {
  border-color: color-mix(in srgb, #22c55e 65%, var(--border));
}

.json-label {
  font-weight: 600;
}

.json-side-before .json-label {
  color: #fdba74;
}

.json-side-after .json-label {
  color: #4ade80;
}

.json-content {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
  border: 1px solid color-mix(in srgb, var(--border) 80%, transparent);
  border-radius: 0.75rem;
  padding: 0.75rem;
  font-size: 0.8rem;
  line-height: 1.4;
  background: color-mix(in srgb, var(--surface) 70%, transparent);
}
</style>
