<template>
  <section v-if="!isHydrated" class="admin-index-page mx-auto w-full max-w-7xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.activityLeads.index.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.activityLeads.index.subtitle') }}</p>
    </div>
  </section>

  <AdminEntityIndex
    v-else
    page-class="activity-leads-page"
    max-width-class="max-w-7xl"
    :title="t('admin.activityLeads.index.title')"
    :subtitle="t('admin.activityLeads.index.subtitle')"
    :search-value="listState.searchInput.value"
    :search-placeholder="t('admin.activityLeads.index.searchPlaceholder')"
    :show-apply="false"
    :per-page="listState.perPage.value"
    :per-page-options="listState.perPageOptions"
    :loading="loading"
    :shown-count="leads.length"
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
    :table-skeleton-columns="8"
    @update:search-value="(value) => (listState.searchInput.value = value)"
    @update:per-page="onUpdatePerPage"
    @update:mode="onModeChange"
    @toggle-desktop="onToggleDesktopMode"
    @reset="onResetFilters"
    @sort="onToggleSort"
    @page="fetchLeads"
  >
    <template #filters>
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <UiSelect
          v-model="statusFilter"
          :label="t('admin.activityLeads.filters.status')"
          :options="statusOptions"
          :placeholder="t('admin.activityLeads.filters.allStatuses')"
        />
        <UiSelect
          v-model="requestTypeFilter"
          :label="t('admin.activityLeads.filters.requestType')"
          :options="requestTypeOptions"
          :placeholder="t('admin.activityLeads.filters.allRequestTypes')"
        />
      </div>
    </template>

    <template #table>
      <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
        <table class="admin-table min-w-[1160px]">
          <thead>
            <tr>
              <th>{{ t('admin.activityLeads.index.headers.activity') }}</th>
              <th>{{ t('admin.activityLeads.index.headers.subject') }}</th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('status')">
                  {{ t('admin.activityLeads.index.headers.status') }}
                  {{ listState.sortMark('status') }}
                </button>
              </th>
              <th>{{ t('admin.activityLeads.index.headers.requestType') }}</th>
              <th>{{ t('admin.activityLeads.index.headers.channels') }}</th>
              <th>{{ t('admin.activityLeads.index.headers.message') }}</th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('created_at')">
                  {{ t('admin.activityLeads.index.headers.createdAt') }}
                  {{ listState.sortMark('created_at') }}
                </button>
              </th>
              <th class="text-right">{{ t('admin.activityLeads.index.headers.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="admin-muted py-5 text-center text-sm">
                {{ t('common.loading') }}
              </td>
            </tr>
            <tr v-else-if="!leads.length">
              <td colspan="8" class="admin-muted py-5 text-center text-sm">
                {{ t('admin.activityLeads.index.empty') }}
              </td>
            </tr>
            <tr v-for="lead in leads" :key="lead.id">
              <td>
                <div class="space-y-1">
                  <p class="font-medium">{{ lead.activity?.name || t('common.dash') }}</p>
                  <p class="admin-muted text-xs font-mono">
                    {{ lead.activity?.slug || lead.activity_id }}
                  </p>
                </div>
              </td>
              <td>
                <div class="space-y-1">
                  <p class="font-medium">{{ resolveSubjectLabel(lead) }}</p>
                  <p class="admin-muted text-xs">{{ resolveSubjectMeta(lead) }}</p>
                </div>
              </td>
              <td>{{ resolveStatusLabel(lead.status) }}</td>
              <td>{{ resolveRequestTypeLabel(lead.request_for_type) }}</td>
              <td>{{ resolveChannelsLabel(lead) }}</td>
              <td class="max-w-[260px]">
                <p class="truncate">{{ lead.message || t('common.dash') }}</p>
              </td>
              <td>{{ formatDate(lead.created_at) }}</td>
              <td class="text-right">
                <div class="lead-actions">
                  <div :data-test="`admin-activity-lead-status-${lead.id}`">
                    <UiSelect
                      :model-value="statusDrafts[lead.id] ?? lead.status"
                      :options="statusOptions"
                      :disabled="!canUpdateLeads || updatingLeadId === lead.id"
                      @update:model-value="onDraftStatusChange(lead.id, $event)"
                    />
                  </div>
                  <button
                    type="button"
                    class="admin-button admin-button-secondary"
                    :data-test="`admin-activity-lead-save-${lead.id}`"
                    :disabled="!canUpdateLeads || updatingLeadId === lead.id"
                    @click="saveStatus(lead)"
                  >
                    {{
                      updatingLeadId === lead.id
                        ? t('common.loading')
                        : t('admin.activityLeads.index.actions.saveStatus')
                    }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <template #cards>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article v-for="lead in leads" :key="lead.id" class="admin-card rounded-xl p-4">
          <h4 class="text-base font-medium">{{ resolveSubjectLabel(lead) }}</h4>
          <p class="admin-muted mt-1 text-xs">{{ lead.activity?.name || t('common.dash') }}</p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.activityLeads.index.card.status', { value: resolveStatusLabel(lead.status) })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.activityLeads.index.card.requestType', {
                value: resolveRequestTypeLabel(lead.request_for_type),
              })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.activityLeads.index.card.channels', { value: resolveChannelsLabel(lead) })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.activityLeads.index.card.createdAt', { value: formatDate(lead.created_at) })
            }}
          </p>
          <p class="admin-muted mt-2 text-xs">{{ lead.message || t('common.dash') }}</p>
          <div class="mt-3 space-y-2">
            <div :data-test="`admin-activity-lead-status-${lead.id}`">
              <UiSelect
                :model-value="statusDrafts[lead.id] ?? lead.status"
                :options="statusOptions"
                :disabled="!canUpdateLeads || updatingLeadId === lead.id"
                @update:model-value="onDraftStatusChange(lead.id, $event)"
              />
            </div>
            <button
              type="button"
              class="admin-button admin-button-secondary w-full"
              :data-test="`admin-activity-lead-save-${lead.id}`"
              :disabled="!canUpdateLeads || updatingLeadId === lead.id"
              @click="saveStatus(lead)"
            >
              {{
                updatingLeadId === lead.id
                  ? t('common.loading')
                  : t('admin.activityLeads.index.actions.saveStatus')
              }}
            </button>
          </div>
        </article>
      </div>
    </template>
  </AdminEntityIndex>
</template>

<script setup lang="ts">
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex/AdminEntityIndex.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import type {
  ActivityLead,
  ActivityLeadRequestType,
  ActivityLeadStatus,
} from '~/composables/useActivityLeads';
import {
  resolveActivityLeadChannelsLabel,
  resolveActivityLeadSubjectLabel,
  useActivityLeads,
} from '~/composables/useActivityLeads';
import { useAdminCrudIndex } from '~/composables/useAdminCrudIndex';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';

const { t, locale } = useI18n();
const route = useRoute();
const { hasPermission } = usePermissions();
const isHydrated = ref(false);

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.activity-leads.read',
});

const activityLeadsApi = useActivityLeads();
const canUpdateLeads = computed(() => hasPermission('admin.activity-leads.update'));
const statusFilter = ref<ActivityLeadStatus | ''>('');
const requestTypeFilter = ref<ActivityLeadRequestType | ''>('');
const updatingLeadId = ref<string | null>(null);
const statusDrafts = reactive<Record<string, ActivityLeadStatus>>({});

const readCustomStateFromQuery = () => {
  const rawStatus = route.query.status;
  statusFilter.value =
    rawStatus === 'new' ||
    rawStatus === 'in_progress' ||
    rawStatus === 'contacted' ||
    rawStatus === 'registered' ||
    rawStatus === 'cancelled'
      ? rawStatus
      : '';

  const rawRequestType = route.query.request_for_type;
  requestTypeFilter.value =
    rawRequestType === 'self' || rawRequestType === 'child' ? rawRequestType : '';
};

const {
  listState,
  items: leads,
  loading,
  loadError,
  contentMode,
  tableOnDesktop,
  pagination,
  showPagination,
  paginationItems,
  fetchItems: fetchLeads,
  onToggleSort,
  onResetFilters: baseResetFilters,
  onUpdatePerPage,
} = useAdminCrudIndex<ActivityLead, Record<string, unknown>>({
  settingsKey: 'activity-leads',
  defaultSortBy: 'created_at',
  defaultSortDir: 'desc',
  allowedSortBy: ['created_at', 'updated_at', 'status'],
  listErrorMessage: t('admin.activityLeads.errors.loadList'),
  deleteErrorMessage: t('admin.activityLeads.errors.loadList'),
  list: (params, context) => activityLeadsApi.adminList(params, context),
  remove: async () => undefined,
  getItemId: (lead) => lead.id,
  readCustomStateFromQuery,
  buildCustomListQuery: () => ({
    status: statusFilter.value || undefined,
    request_for_type: requestTypeFilter.value || undefined,
  }),
  buildCustomQuery: () => ({
    status: statusFilter.value || undefined,
    request_for_type: requestTypeFilter.value || undefined,
  }),
  resetCustomFilters: () => {
    statusFilter.value = '';
    requestTypeFilter.value = '';
  },
});

const statusOptions = computed(() => [
  { value: 'new', label: t('admin.activityLeads.status.new') },
  { value: 'in_progress', label: t('admin.activityLeads.status.inProgress') },
  { value: 'contacted', label: t('admin.activityLeads.status.contacted') },
  { value: 'registered', label: t('admin.activityLeads.status.registered') },
  { value: 'cancelled', label: t('admin.activityLeads.status.cancelled') },
]);

const requestTypeOptions = computed(() => [
  { value: 'self', label: t('admin.activityLeads.requestType.self') },
  { value: 'child', label: t('admin.activityLeads.requestType.child') },
]);

const cardSortFields = computed(() => [
  { value: 'created_at', label: t('admin.activityLeads.index.sort.createdAt') },
  { value: 'status', label: t('admin.activityLeads.index.sort.status') },
]);

const onModeChange = (value: string | number | (string | number)[]) => {
  const nextValue = Array.isArray(value) ? value[0] : value;
  if (nextValue === 'table' || nextValue === 'table-cards' || nextValue === 'cards') {
    contentMode.value = nextValue;
  }
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
};

const onResetFilters = () => {
  baseResetFilters();
};

watch(
  leads,
  (items) => {
    items.forEach((lead) => {
      statusDrafts[lead.id] = lead.status;
    });
  },
  { immediate: true }
);

watch([statusFilter, requestTypeFilter], () => {
  if (!isHydrated.value) {
    return;
  }
  void fetchLeads(1);
});

const onDraftStatusChange = (leadId: string, value: string | number | (string | number)[]) => {
  const nextValue = Array.isArray(value) ? value[0] : value;
  if (
    nextValue === 'new' ||
    nextValue === 'in_progress' ||
    nextValue === 'contacted' ||
    nextValue === 'registered' ||
    nextValue === 'cancelled'
  ) {
    statusDrafts[leadId] = nextValue;
  }
};

const formatDate = (value: string | null | undefined): string => {
  if (!value) {
    return t('common.dash');
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(parsed);
};

const resolveStatusLabel = (status: ActivityLeadStatus): string =>
  t(`admin.activityLeads.status.${status === 'in_progress' ? 'inProgress' : status}`);
const resolveRequestTypeLabel = (type: ActivityLeadRequestType): string =>
  t(`admin.activityLeads.requestType.${type}`);
const resolveChannelsLabel = (lead: ActivityLead): string => resolveActivityLeadChannelsLabel(lead);
const resolveSubjectLabel = (lead: ActivityLead): string => resolveActivityLeadSubjectLabel(lead);
const resolveSubjectMeta = (lead: ActivityLead): string =>
  lead.subject?.email ||
  lead.subject?.phone ||
  lead.activity?.organization?.name ||
  t('common.dash');

const saveStatus = async (lead: ActivityLead) => {
  const nextStatus = statusDrafts[lead.id] ?? lead.status;
  if (nextStatus === lead.status) {
    return;
  }

  updatingLeadId.value = lead.id;
  try {
    await activityLeadsApi.adminUpdateStatus(lead.id, nextStatus);
    await fetchLeads(pagination.current_page || 1);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.activityLeads.errors.updateStatus'));
  } finally {
    updatingLeadId.value = null;
  }
};

onMounted(() => {
  isHydrated.value = true;
});
</script>

<style lang="scss" scoped src="../_shared/admin-index-page.scss"></style>
<style lang="scss" scoped>
.lead-actions {
  display: grid;
  gap: 0.5rem;
  justify-items: end;
  min-width: 180px;
}
</style>
