<template>
  <AdminEntityIndex
    page-class="children-page"
    max-width-class="max-w-6xl"
    :title="t('admin.children.index.title')"
    :subtitle="t('admin.children.index.subtitle')"
    create-to="/admin/children/new"
    :show-create="canWriteChildren"
    :create-label="t('admin.children.index.createLabel')"
    :search-value="listState.searchInput.value"
    :search-placeholder="t('admin.children.index.searchPlaceholder')"
    :show-apply="false"
    :per-page="listState.perPage.value"
    :per-page-options="listState.perPageOptions"
    :loading="loading"
    :shown-count="children.length"
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
    :table-skeleton-columns="4"
    @update:search-value="(value) => (listState.searchInput.value = value)"
    @update:per-page="onUpdatePerPage"
    @update:mode="onModeChange"
    @toggle-desktop="onToggleDesktopMode"
    @reset="onResetFilters"
    @sort="onToggleSort"
    @page="fetchChildren"
  >
    <template #table>
      <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
        <table class="admin-table min-w-[760px]">
          <thead>
            <tr>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('last_name')">
                  {{ t('admin.children.index.headers.fullName') }}
                  {{ listState.sortMark('last_name') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('birth_date')">
                  {{ t('admin.children.index.headers.birthDate') }}
                  {{ listState.sortMark('birth_date') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('gender')">
                  {{ t('admin.children.index.headers.gender') }} {{ listState.sortMark('gender') }}
                </button>
              </th>
              <th>
                <button type="button" class="sort-btn" @click="onToggleSort('user_id')">
                  {{ t('admin.children.index.headers.user') }} {{ listState.sortMark('user_id') }}
                </button>
              </th>
              <th class="text-right">{{ t('admin.children.index.headers.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="admin-muted py-5 text-center text-sm">
                {{ t('common.loading') }}
              </td>
            </tr>
            <tr v-else-if="!children.length">
              <td colspan="5" class="admin-muted py-5 text-center text-sm">
                {{ t('admin.children.index.empty') }}
              </td>
            </tr>
            <tr v-for="child in children" :key="child.id">
              <td>{{ resolveChildFullName(child) }}</td>
              <td>{{ formatDate(child.birth_date) }}</td>
              <td>{{ resolveGenderLabel(child.gender) }}</td>
              <td class="max-w-[240px] truncate" :title="resolveUserLabel(child)">
                <AdminLink v-if="resolveUserLink(child)" :to="resolveUserLink(child)">
                  {{ resolveUserLabel(child) }}
                </AdminLink>
                <span v-else>{{ resolveUserLabel(child) }}</span>
              </td>
              <td>
                <AdminCrudActions
                  :show-to="`/admin/children/${child.id}`"
                  :edit-to="`/admin/children/${child.id}/edit`"
                  :can-show="canReadChildren"
                  :can-edit="canWriteChildren"
                  :can-delete="canWriteChildren"
                  :deleting="deletingId === child.id"
                  align="end"
                  @delete="removeChild(child)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <template #cards>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article v-for="child in children" :key="child.id" class="admin-card rounded-xl p-4">
          <h4 class="text-base font-medium">{{ resolveChildFullName(child) }}</h4>
          <p class="admin-muted mt-1 text-xs">
            {{ t('admin.children.index.card.birthDate', { value: formatDate(child.birth_date) }) }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{ t('admin.children.index.card.gender', { value: resolveGenderLabel(child.gender) }) }}
          </p>
          <p class="admin-muted mt-1 text-xs">
            {{ t('admin.children.index.card.user', { value: resolveUserLabel(child) }) }}
          </p>
          <div class="mt-3">
            <AdminCrudActions
              :show-to="`/admin/children/${child.id}`"
              :edit-to="`/admin/children/${child.id}/edit`"
              :can-show="canReadChildren"
              :can-edit="canWriteChildren"
              :can-delete="canWriteChildren"
              :deleting="deletingId === child.id"
              @delete="removeChild(child)"
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
import AdminLink from '~/components/admin/AdminLink.vue';
import AdminCrudActions from '~/components/admin/Listing/AdminCrudActions.vue';
import AdminEntityIndex from '~/components/admin/Listing/AdminEntityIndex.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import type { AdminChild } from '~/composables/useAdminChildren';
const { t, locale } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'org.children.read',
});

const childrenApi = useAdminChildren();
const { hasPermission } = usePermissions();
const canReadChildren = computed(() => hasPermission('org.children.read'));
const canWriteChildren = computed(() => hasPermission('org.children.write'));
const {
  listState,
  items: children,
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
  fetchItems: fetchChildren,
  onToggleSort,
  onResetFilters,
  onUpdatePerPage,
  removeItem,
  confirmRemoveItem,
  cancelRemoveItem,
} = useAdminCrudIndex<AdminChild>({
  settingsKey: 'children',
  defaultSortBy: 'created_at',
  defaultPerPage: 10,
  listErrorMessage: t('admin.children.errors.loadList'),
  deleteErrorMessage: t('admin.children.errors.delete'),
  list: childrenApi.list,
  remove: childrenApi.remove,
  getItemId: (child) => child.id,
});

const formatDate = (date: string | null): string => {
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
  }).format(parsed);
};

const resolveUserLabel = (child: AdminChild): string => {
  const fullName = [child.user?.last_name, child.user?.first_name, child.user?.middle_name]
    .filter((part): part is string => typeof part === 'string' && part.trim().length > 0)
    .join(' ');

  if (fullName) {
    return fullName;
  }

  return child.user?.email || child.user_id || t('common.dash');
};

const resolveChildFullName = (child: AdminChild): string => {
  return [child.last_name, child.first_name, child.middle_name]
    .filter((part): part is string => typeof part === 'string' && part.trim().length > 0)
    .join(' ');
};

const resolveGenderLabel = (gender: string | null | undefined): string => {
  if (gender === 'male') {
    return t('admin.genders.male');
  }
  if (gender === 'female') {
    return t('admin.genders.female');
  }

  return t('common.dash');
};

const resolveUserLink = (child: AdminChild): string | null => {
  const userId = child.user?.id || child.user_id;
  return userId ? `/admin/users/${userId}` : null;
};

const cardSortFields = computed(() => [
  { value: 'last_name', label: t('admin.children.index.sort.lastName') },
  { value: 'first_name', label: t('admin.children.index.sort.firstName') },
  { value: 'middle_name', label: t('admin.children.index.sort.middleName') },
  { value: 'gender', label: t('admin.children.index.sort.gender') },
  { value: 'birth_date', label: t('admin.children.index.sort.birthDate') },
  { value: 'user_id', label: t('admin.children.index.sort.user') },
]);

const onModeChange = (mode: 'table' | 'table-cards' | 'cards') => {
  contentMode.value = mode;
};

const onToggleDesktopMode = () => {
  tableOnDesktop.value = !tableOnDesktop.value;
};

const removeChild = async (child: AdminChild) => {
  removeItem(child, {
    canDelete: canWriteChildren.value,
    confirmTitle: t('admin.actions.delete'),
    confirmMessage: t('admin.children.confirmDelete', { name: resolveChildFullName(child) }),
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
      fetchChildren(listState.applySearch());
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
