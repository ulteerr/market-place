<template>
  <AdminEntityIndex
    page-class="organizations-page"
    max-width-class="max-w-6xl"
    :title="t('admin.organizations.index.title')"
    :subtitle="t('admin.organizations.index.subtitle')"
    create-to="/admin/organizations/new"
    :show-create="canWriteOrganizations"
    :create-label="t('admin.organizations.index.createLabel')"
    :search-value="listState.searchInput.value"
    :search-placeholder="t('admin.organizations.index.searchPlaceholder')"
    :show-apply="false"
    :per-page="listState.perPage.value"
    :per-page-options="listState.perPageOptions"
    :loading="loading"
    :shown-count="organizations.length"
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
    @page="fetchOrganizations"
  >
    <template #table>
      <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
        <table class="admin-table min-w-[980px]">
          <thead>
            <tr>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('name')">
                  {{ t('admin.organizations.index.headers.name') }} {{ listState.sortMark('name') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('status')">
                  {{ t('admin.organizations.index.headers.status') }}
                  {{ listState.sortMark('status') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('ownership_status')">
                  {{ t('admin.organizations.index.headers.ownership') }}
                  {{ listState.sortMark('ownership_status') }}
                </button>
              </th>
              <th>{{ t('admin.organizations.index.headers.owner') }}</th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('created_at')">
                  {{ t('admin.organizations.index.headers.createdAt') }}
                  {{ listState.sortMark('created_at') }}
                </button>
              </th>
              <th class="text-right">{{ t('admin.organizations.index.headers.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="admin-muted py-5 text-center text-sm">
                {{ t('common.loading') }}
              </td>
            </tr>
            <tr v-else-if="!organizations.length">
              <td colspan="6" class="admin-muted py-5 text-center text-sm">
                {{ t('admin.organizations.index.empty') }}
              </td>
            </tr>
            <tr v-for="organization in organizations" :key="organization.id">
              <td class="font-medium">{{ organization.name }}</td>
              <td>{{ resolveStatusLabel(organization.status) }}</td>
              <td>{{ resolveOwnershipLabel(organization.ownership_status) }}</td>
              <td class="max-w-[260px] truncate" :title="resolveOwnerLabel(organization)">
                <AdminLink
                  v-if="organization.owner?.id"
                  :to="`/admin/users/${organization.owner.id}`"
                >
                  {{ resolveOwnerLabel(organization) }}
                </AdminLink>
                <span v-else>{{ resolveOwnerLabel(organization) }}</span>
              </td>
              <td>{{ formatDate(organization.created_at) }}</td>
              <td>
                <AdminCrudActions
                  :show-to="`/admin/organizations/${organization.id}`"
                  :edit-to="`/admin/organizations/${organization.id}/edit`"
                  :can-show="canReadOrganizations"
                  :can-edit="canWriteOrganizations"
                  :can-delete="canDeleteOrganizations"
                  :deleting="deletingId === organization.id"
                  align="end"
                  @delete="removeOrganization(organization)"
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
          v-for="organization in organizations"
          :key="organization.id"
          class="admin-card rounded-xl p-4"
        >
          <h4 class="text-base font-medium">{{ organization.name }}</h4>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.organizations.index.card.status', {
                value: resolveStatusLabel(organization.status),
              })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.organizations.index.card.ownership', {
                value: resolveOwnershipLabel(organization.ownership_status),
              })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.organizations.index.card.owner', { value: resolveOwnerLabel(organization) })
            }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{
              t('admin.organizations.index.card.createdAt', {
                value: formatDate(organization.created_at),
              })
            }}
          </p>
          <div class="mt-3">
            <AdminCrudActions
              :show-to="`/admin/organizations/${organization.id}`"
              :edit-to="`/admin/organizations/${organization.id}/edit`"
              :can-show="canReadOrganizations"
              :can-edit="canWriteOrganizations"
              :can-delete="canDeleteOrganizations"
              :deleting="deletingId === organization.id"
              @delete="removeOrganization(organization)"
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
import AdminCrudActions from '~/components/admin/Listing/AdminCrudActions.vue';
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex.vue';
import AdminLink from '~/components/admin/AdminLink.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import type {
  AdminOrganization,
  OrganizationOwnershipStatus,
  OrganizationStatus,
} from '~/composables/useAdminOrganizations';
import { getAdminOrganizationOwnerName } from '~/composables/useAdminOrganizations';

const { t, locale } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'org.company.profile.read',
});

const organizationsApi = useAdminOrganizations();
const { hasPermission } = usePermissions();
const canReadOrganizations = computed(() => hasPermission('org.company.profile.read'));
const canWriteOrganizations = computed(() => hasPermission('org.company.profile.update'));
const canDeleteOrganizations = computed(() => hasPermission('org.company.profile.delete'));

const {
  listState,
  items: organizations,
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
  fetchItems: fetchOrganizations,
  onToggleSort,
  onResetFilters,
  onUpdatePerPage,
  removeItem,
  confirmRemoveItem,
  cancelRemoveItem,
} = useAdminCrudIndex<AdminOrganization>({
  settingsKey: 'organizations',
  defaultSortBy: 'created_at',
  defaultPerPage: 10,
  listErrorMessage: t('admin.organizations.errors.loadList'),
  deleteErrorMessage: t('admin.organizations.errors.delete'),
  list: organizationsApi.list,
  remove: organizationsApi.remove,
  getItemId: (organization) => organization.id,
});

const formatDate = (date: string | null | undefined): string => {
  if (!date) {
    return t('common.dash');
  }

  const parsed = new Date(date);
  if (Number.isNaN(parsed.getTime())) {
    return date;
  }

  return new Intl.DateTimeFormat(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(parsed);
};

const resolveStatusLabel = (status: OrganizationStatus | null | undefined): string => {
  if (!status) {
    return t('common.dash');
  }

  const key = `admin.organizations.status.${status}`;
  return t(key);
};

const resolveOwnershipLabel = (status: OrganizationOwnershipStatus | null | undefined): string => {
  if (!status) {
    return t('common.dash');
  }

  const key = `admin.organizations.ownership.${status}`;
  return t(key);
};

const resolveOwnerLabel = (organization: AdminOrganization): string => {
  return getAdminOrganizationOwnerName(organization) || t('common.dash');
};

const cardSortFields = computed(() => [
  { value: 'name', label: t('admin.organizations.index.sort.name') },
  { value: 'status', label: t('admin.organizations.index.sort.status') },
  { value: 'ownership_status', label: t('admin.organizations.index.sort.ownership') },
  { value: 'created_at', label: t('admin.organizations.index.sort.createdAt') },
]);

const onModeChange = (mode: 'table' | 'table-cards' | 'cards') => {
  contentMode.value = mode;
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
};

const removeOrganization = (organization: AdminOrganization) => {
  removeItem(organization, {
    canDelete: canDeleteOrganizations.value,
    confirmTitle: t('admin.actions.delete'),
    confirmMessage: t('admin.organizations.confirmDelete', { name: organization.name }),
    confirmLabel: t('admin.actions.delete'),
    cancelLabel: t('common.cancel'),
  });
};

const searchAutoReady = ref(false);
let searchAutoTimer: ReturnType<typeof setTimeout> | null = null;

watch(
  () => listState.searchInput.value,
  (nextValue) => {
    if (!searchAutoReady.value) {
      return;
    }

    if (nextValue.trim() === listState.search.value) {
      return;
    }

    if (searchAutoTimer) {
      clearTimeout(searchAutoTimer);
    }

    searchAutoTimer = setTimeout(() => {
      fetchOrganizations(listState.applySearch());
    }, 300);
  }
);

onMounted(() => {
  searchAutoReady.value = true;
});

onBeforeUnmount(() => {
  if (searchAutoTimer) {
    clearTimeout(searchAutoTimer);
    searchAutoTimer = null;
  }
});
</script>
