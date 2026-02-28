<template>
  <article v-if="canReadActionLog" class="admin-card rounded-2xl p-5 lg:p-6">
    <div class="mb-4">
      <h3 class="text-lg font-semibold">{{ title || t('admin.actionLogs.title') }}</h3>
      <p class="admin-muted mt-1 text-sm">{{ t('admin.actionLogs.subtitle') }}</p>
    </div>

    <p v-if="!entityId" class="admin-muted text-sm">{{ t('common.dash') }}</p>
    <p v-else-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
    <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>
    <p v-else-if="!items.length" class="admin-muted text-sm">{{ t('admin.actionLogs.empty') }}</p>

    <ul v-else class="space-y-3">
      <li v-for="item in items" :key="item.id" class="admin-action-log-item rounded-xl p-3">
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
          <div class="flex flex-wrap items-center gap-2 text-xs">
            <span :class="['admin-action-log-badge', `is-${item.event}`]">
              {{ resolveEventLabel(item.event) }}
            </span>
            <span class="admin-muted">{{ formatDate(item.created_at) }}</span>
            <span class="admin-muted">{{ resolveModelTitle(item) }}</span>
            <span class="admin-muted">{{ t('admin.actionLogs.headers.user') }}:</span>
            <AdminLink v-if="resolveActorLink(item)" :to="resolveActorLink(item)!">
              {{ resolveUserLabel(item) }}
            </AdminLink>
            <span v-else class="admin-muted">{{ resolveUserLabel(item) }}</span>
          </div>
          <button
            v-if="hasJsonPayload(item)"
            type="button"
            class="admin-button-secondary rounded-md px-3 py-1.5 text-xs"
            @click="toggleJson(item.id)"
          >
            {{
              expandedEntryIds.has(item.id)
                ? t('admin.changelog.hideJson')
                : t('admin.changelog.showJson')
            }}
          </button>
        </div>

        <p class="admin-muted text-xs">
          {{ t('admin.actionLogs.headers.changedFields') }}: {{ resolveChangedFields(item) }}
        </p>

        <div v-if="expandedEntryIds.has(item.id)" class="mt-2 grid gap-2 md:grid-cols-2">
          <div
            v-if="item.before"
            class="admin-action-log-side admin-action-log-side-before rounded-lg p-2"
          >
            <p class="admin-action-log-label mb-1 text-xs">{{ t('admin.changelog.before') }}</p>
            <pre class="admin-action-log-json">{{ toJson(item.before) }}</pre>
          </div>
          <div
            v-if="item.after"
            class="admin-action-log-side admin-action-log-side-after rounded-lg p-2"
          >
            <p class="admin-action-log-label mb-1 text-xs">{{ t('admin.changelog.after') }}</p>
            <pre class="admin-action-log-json">{{ toJson(item.after) }}</pre>
          </div>
        </div>
      </li>
    </ul>

    <div v-if="entityId && !loading && items.length" class="mt-4 flex flex-wrap items-center gap-2">
      <button
        v-if="page > 1"
        type="button"
        class="admin-button-secondary rounded-md px-3 py-1.5 text-xs"
        :disabled="loading"
        @click="goToPage(page - 1)"
      >
        {{ t('admin.pagination.back') }}
      </button>
      <span class="admin-muted text-xs">
        {{
          t('admin.pagination.summary', {
            current: page,
            last: lastPage,
            perPage,
          })
        }}
      </span>
      <button
        v-if="page < lastPage"
        type="button"
        class="admin-button-secondary rounded-md px-3 py-1.5 text-xs"
        :disabled="loading"
        @click="goToPage(page + 1)"
      >
        {{ t('admin.pagination.forward') }}
      </button>
    </div>
  </article>
</template>

<script setup lang="ts">
import AdminLink from '~/components/admin/AdminLink.vue';
import type { AdminActionLogItem } from '~/composables/useAdminActionLogs';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';

const props = withDefaults(
  defineProps<{
    model: string;
    entityId?: string | null;
    title?: string;
    perPage?: number;
    refreshToken?: number | string;
  }>(),
  {
    entityId: null,
    title: '',
    perPage: 10,
    refreshToken: 0,
  }
);

const { t, locale } = useI18n();
const { user: authUser } = useAuth();
const { hasPermission } = usePermissions();
const actionLogApi = useAdminActionLogs();

const items = ref<AdminActionLogItem[]>([]);
const expandedEntryIds = reactive(new Set<string>());
const loading = ref(false);
const loadError = ref('');
const page = ref(1);
const lastPage = ref(1);
const perPage = ref(props.perPage);
const canReadActionLog = computed(() => hasPermission('admin.action-log.read'));

const resolveEventLabel = (event: string): string => {
  if (event === 'create') return t('admin.actionLogs.events.create');
  if (event === 'update') return t('admin.actionLogs.events.update');
  if (event === 'delete') return t('admin.actionLogs.events.delete');
  return event;
};

const resolveModelLabel = (modelType: string): string => {
  if (modelType.endsWith('\\User')) return t('admin.actionLogs.models.user');
  if (modelType.endsWith('\\Role')) return t('admin.actionLogs.models.role');
  if (modelType.endsWith('\\Child')) return t('admin.actionLogs.models.child');
  if (modelType.endsWith('\\Organization')) return t('admin.actionLogs.models.organization');
  if (modelType.endsWith('\\MetroLine')) return t('admin.actionLogs.models.metroLine');
  if (modelType.endsWith('\\MetroStation')) return t('admin.actionLogs.models.metroStation');
  if (modelType.endsWith('\\Country')) return t('admin.actionLogs.models.geoCountry');
  if (modelType.endsWith('\\Region')) return t('admin.actionLogs.models.geoRegion');
  if (modelType.endsWith('\\City')) return t('admin.actionLogs.models.geoCity');
  if (modelType.endsWith('\\District')) return t('admin.actionLogs.models.geoDistrict');

  const tail = modelType.split('\\').pop();
  return tail || modelType;
};

const resolveModelTitle = (item: AdminActionLogItem): string => {
  return `${resolveModelLabel(item.model_type)} #${item.model_id}`;
};

const resolveUserLabel = (item: AdminActionLogItem): string => {
  return item.user?.full_name || item.user?.email || item.user_id || t('common.dash');
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

const toggleJson = (id: string): void => {
  if (expandedEntryIds.has(id)) {
    expandedEntryIds.delete(id);
  } else {
    expandedEntryIds.add(id);
  }
};

const toJson = (value: unknown): string => {
  try {
    return JSON.stringify(value ?? {}, null, 2);
  } catch {
    return String(value ?? '');
  }
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

const fetchItems = async () => {
  if (!props.entityId || !canReadActionLog.value) {
    items.value = [];
    lastPage.value = 1;
    loadError.value = '';
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    const payload = await actionLogApi.list({
      page: page.value,
      per_page: perPage.value,
      model: props.model,
      model_id: props.entityId,
      sort_by: 'created_at',
      sort_dir: 'desc',
    });

    items.value = payload.data;
    lastPage.value = payload.last_page;
    expandedEntryIds.clear();
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.actionLogs.errors.load'));
  } finally {
    loading.value = false;
  }
};

const goToPage = async (nextPage: number) => {
  page.value = nextPage;
  await fetchItems();
};

watch(
  () => [props.model, props.entityId, props.refreshToken, canReadActionLog.value],
  () => {
    page.value = 1;
    void fetchItems();
  },
  { immediate: true }
);
</script>

<style lang="scss" scoped src="./AdminActionLogPanel.scss"></style>
