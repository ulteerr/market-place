<template>
  <article v-if="canReadChangeLog" class="admin-card rounded-2xl p-5 lg:p-6">
    <div class="mb-4">
      <h3 class="text-lg font-semibold">{{ title || t('admin.changelog.title') }}</h3>
      <p class="admin-muted mt-1 text-sm">{{ t('admin.changelog.subtitle') }}</p>
    </div>

    <p v-if="!entityId" class="admin-muted text-sm">{{ t('common.dash') }}</p>
    <div v-else-if="loading" class="admin-changelog-skeleton space-y-3" aria-hidden="true">
      <div v-for="item in 3" :key="item" class="admin-changelog-item rounded-xl p-3">
        <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
          <div class="skeleton-meta-wrap">
            <div class="skeleton-line is-badge" />
            <div class="skeleton-line is-meta-short" />
            <div class="skeleton-line is-meta-short" />
            <div class="skeleton-line is-meta-short" />
          </div>
          <div class="skeleton-actions">
            <div class="skeleton-line is-action" />
            <div class="skeleton-line is-action" />
          </div>
        </div>
        <div class="space-y-2.5">
          <div class="skeleton-line is-meta" />
          <div class="skeleton-line is-meta medium" />
          <div class="skeleton-line is-meta long" />
        </div>
      </div>
    </div>
    <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>
    <p v-else-if="!entries.length" class="admin-muted text-sm">{{ t('admin.changelog.empty') }}</p>

    <ul v-else class="admin-changelog-list space-y-3">
      <li v-for="entry in entries" :key="entry.id" class="admin-changelog-item rounded-xl p-3">
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
          <div class="flex flex-wrap items-center gap-2 text-xs">
            <span :class="['admin-changelog-badge', `is-${resolveEntryEvent(entry)}`]">
              {{ eventLabel(entry) }}
            </span>
            <span class="admin-muted">{{ t('admin.changelog.version') }} #{{ entry.version }}</span>
            <span class="admin-muted">{{ formatDate(entry.created_at) }}</span>
            <span class="admin-muted">{{ t('admin.changelog.actor') }}:</span>
            <AdminLink v-if="resolveActorLink(entry)" :to="resolveActorLink(entry)!">
              {{ resolveActorLabel(entry) }}
            </AdminLink>
            <span v-else class="admin-muted">{{ resolveActorLabel(entry) }}</span>
            <span v-if="rollbackSourceVersion(entry) !== null" class="admin-muted">
              {{
                t('admin.changelog.rollback.fromVersion', { version: rollbackSourceVersion(entry) })
              }}
            </span>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              class="admin-button-secondary rounded-md px-3 py-1.5 text-xs"
              @click="toggleJson(entry.id)"
            >
              {{
                expandedEntryIds.has(entry.id)
                  ? t('admin.changelog.hideJson')
                  : t('admin.changelog.showJson')
              }}
            </button>
            <button
              v-if="canRollback(entry)"
              type="button"
              class="admin-button rounded-md px-3 py-1.5 text-xs"
              :disabled="rollbackLoading"
              @click="openRollbackModal(entry)"
            >
              {{ t('admin.actions.rollback') }}
            </button>
          </div>
        </div>

        <p v-if="entry.changed_fields?.length" class="admin-muted text-xs">
          {{ t('admin.changelog.changedFields') }}:
          {{ entry.changed_fields.map((field) => resolveFieldLabel(field)).join(', ') }}
        </p>
        <ul v-if="getChangedRows(entry).length" class="mt-2 space-y-1">
          <li
            v-for="row in getChangedRows(entry)"
            :key="`${entry.id}-${row.key}`"
            class="admin-diff-row text-xs"
          >
            <span class="admin-muted admin-diff-label">{{ row.label }}:</span>
            <span v-if="!row.hideBefore" class="admin-diff-before admin-diff-value">
              <template v-if="row.beforePreview">
                <UiImagePreview
                  :src="row.beforePreview"
                  :alt="row.label"
                  :preview-alt="row.label"
                  variant="table"
                  :fallback-text="t('common.dash')"
                  :preview-title="t('admin.changelog.preview.title')"
                  :open-aria-label="t('admin.changelog.preview.open')"
                  class="admin-diff-image"
                />
              </template>
              <template v-else>{{ t('admin.changelog.was') }} {{ row.before }}</template>
            </span>
            <span v-if="!row.hideBefore" class="admin-muted admin-diff-arrow">→</span>
            <span class="admin-diff-after admin-diff-value">
              <template v-if="row.afterPreview">
                <UiImagePreview
                  :src="row.afterPreview"
                  :alt="row.label"
                  :preview-alt="row.label"
                  variant="table"
                  :fallback-text="t('common.dash')"
                  :preview-title="t('admin.changelog.preview.title')"
                  :open-aria-label="t('admin.changelog.preview.open')"
                  class="admin-diff-image"
                />
              </template>
              <template v-else>{{
                row.hideBefore ? row.after : `${t('admin.changelog.now')} ${row.after}`
              }}</template>
            </span>
          </li>
        </ul>

        <div v-if="expandedEntryIds.has(entry.id)" class="mt-2">
          <div
            v-if="entry.event === 'create'"
            class="admin-changelog-side admin-changelog-side-after rounded-lg p-2"
          >
            <p class="admin-changelog-label mb-1 text-xs">
              {{ t('admin.changelog.createdData') }}
            </p>
            <pre class="admin-changelog-json">{{ toJson(entry.after) }}</pre>
          </div>
          <div v-else class="grid gap-2 md:grid-cols-2">
            <div class="admin-changelog-side admin-changelog-side-before rounded-lg p-2">
              <p class="admin-changelog-label mb-1 text-xs">{{ t('admin.changelog.before') }}</p>
              <pre class="admin-changelog-json">{{ toJson(entry.before) }}</pre>
            </div>
            <div class="admin-changelog-side admin-changelog-side-after rounded-lg p-2">
              <p class="admin-changelog-label mb-1 text-xs">{{ t('admin.changelog.after') }}</p>
              <pre class="admin-changelog-json">{{ toJson(entry.after) }}</pre>
            </div>
          </div>
        </div>
      </li>
    </ul>

    <div
      v-if="listMode === 'paginated' && entityId && !loading && entries.length"
      class="mt-4 flex flex-wrap items-center gap-2"
    >
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

    <UiModal
      v-model="rollbackModalOpen"
      mode="confirm"
      :title="t('admin.changelog.rollback.title')"
      :message="rollbackMessage"
      :confirm-label="t('admin.actions.rollback')"
      :cancel-label="t('common.cancel')"
      :loading-label="t('common.loading')"
      :confirm-loading="rollbackLoading"
      destructive
      @confirm="confirmRollback"
    />
  </article>
</template>

<script setup lang="ts">
import type {
  AdminChangeLogEntry,
  ChangeLogEvent,
  ChangeLogMediaSnapshotItem,
  ChangeLogListMode,
} from '~/composables/useAdminChangeLog';
import AdminLink from '~/components/admin/AdminLink.vue';
import UiImagePreview from '~/components/ui/ImagePreview/UiImagePreview.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';
import { resolveAssetUrl } from '~/composables/asset-url';

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
    perPage: 20,
    refreshToken: 0,
  }
);

const emit = defineEmits<{
  (
    event: 'rolled-back',
    payload: { model_type: string; model_id: string; rolled_back_from_id: string }
  ): void;
}>();

const { t, te, locale } = useI18n();
const { user: authUser } = useAuth();
const { hasPermission } = usePermissions();
const changeLogApi = useAdminChangeLog();
const config = useRuntimeConfig();

const entries = ref<AdminChangeLogEntry[]>([]);
const expandedEntryIds = reactive(new Set<string>());
const loading = ref(false);
const loadError = ref('');
const page = ref(1);
const lastPage = ref(1);
const perPage = ref(props.perPage);
const listMode = ref<ChangeLogListMode>('latest');
const rollbackModalOpen = ref(false);
const rollbackLoading = ref(false);
const rollbackEntry = ref<AdminChangeLogEntry | null>(null);
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));

const resolveEntryEvent = (entry: AdminChangeLogEntry): ChangeLogEvent => {
  if (rollbackSourceVersion(entry) !== null) {
    return 'restore';
  }

  return entry.event;
};

const eventLabel = (entry: AdminChangeLogEntry): string => {
  return t(`admin.changelog.events.${resolveEntryEvent(entry)}`);
};

const rollbackSourceVersion = (entry: AdminChangeLogEntry): number | null => {
  const meta = entry.meta ?? {};
  const fromMeta = Number(meta.rolled_back_from_version);

  if (Number.isFinite(fromMeta) && fromMeta >= 1) {
    return Math.floor(fromMeta);
  }

  if (!entry.rolled_back_from_id) {
    return null;
  }

  const sourceEntry = entries.value.find((item) => item.id === entry.rolled_back_from_id);
  if (!sourceEntry) {
    return null;
  }

  return sourceEntry.version;
};

const isCurrentActor = (entry: AdminChangeLogEntry): boolean => {
  return Boolean(entry.actor?.id && authUser.value?.id && entry.actor.id === authUser.value.id);
};

const resolveActorLabel = (entry: AdminChangeLogEntry): string => {
  if (isCurrentActor(entry)) {
    return t('admin.changelog.actorMe');
  }

  return entry.actor?.full_name || entry.actor_id || t('common.dash');
};

const resolveActorLink = (entry: AdminChangeLogEntry): string | null => {
  if (isCurrentActor(entry)) {
    return '/admin/profile';
  }

  if (entry.actor?.id) {
    return `/admin/users/${entry.actor.id}`;
  }

  return null;
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

const toJson = (value: unknown): string => {
  if (!value || (typeof value === 'object' && Object.keys(value as object).length === 0)) {
    return t('common.dash');
  }

  try {
    return JSON.stringify(value, null, 2);
  } catch {
    return String(value);
  }
};

const prettifyFieldName = (field: string): string => {
  return field
    .replaceAll('_', ' ')
    .replaceAll('.', ' ')
    .trim()
    .replace(/\s+/g, ' ')
    .replace(/^./, (char) => char.toUpperCase());
};

const resolveFieldLabel = (field: string): string => {
  const modelKey = props.model === 'profile' ? 'profile' : props.model;
  const modelPath = `admin.changelog.fields.${modelKey}.${field}`;
  const commonPath = `admin.changelog.fields.common.${field}`;

  if (te(modelPath)) {
    return t(modelPath);
  }

  if (te(commonPath)) {
    return t(commonPath);
  }

  return prettifyFieldName(field);
};

const formatScalar = (value: unknown): string => {
  if (value === null || value === undefined || value === '') {
    return t('common.dash');
  }

  if (typeof value === 'boolean') {
    return value ? 'true' : 'false';
  }

  if (typeof value === 'object') {
    return t('admin.changelog.complexChanged');
  }

  return String(value);
};

const resolvePermissionCodeLabel = (code: string): string => {
  const normalizedCode = code.replaceAll('-', '_');
  const path = `admin.permissions.codes.${normalizedCode}`;

  if (te(path)) {
    return t(path);
  }

  return code;
};

const isPermissionOverridePath = (field: string, nestedPath?: string): boolean => {
  return field === 'permission_overrides' && (nestedPath === 'allow' || nestedPath === 'deny');
};

const formatArrayValue = (
  value: unknown[],
  context?: { field?: string; nestedPath?: string }
): string => {
  if (!value.length) {
    return t('common.dash');
  }

  const allPrimitive = value.every(
    (item) =>
      item === null ||
      item === undefined ||
      typeof item === 'string' ||
      typeof item === 'number' ||
      typeof item === 'boolean'
  );

  if (allPrimitive) {
    return value
      .map((item) => {
        if (item === null || item === undefined || item === '') {
          return t('common.dash');
        }

        if (typeof item === 'boolean') {
          return item ? 'true' : 'false';
        }

        if (
          typeof item === 'string' &&
          context &&
          isPermissionOverridePath(context.field ?? '', context.nestedPath)
        ) {
          return resolvePermissionCodeLabel(item);
        }

        return String(item);
      })
      .join(', ');
  }

  return t('admin.changelog.complexChanged');
};

const formatDiffValue = (
  value: unknown,
  context?: { field?: string; nestedPath?: string }
): string => {
  if (Array.isArray(value)) {
    return formatArrayValue(value, context);
  }

  return formatScalar(value);
};

const isPlainObject = (value: unknown): value is Record<string, unknown> => {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
};

const isUnsetValue = (value: unknown): boolean => {
  if (value === null || value === undefined || value === '') {
    return true;
  }

  if (Array.isArray(value)) {
    return value.length === 0;
  }

  if (isPlainObject(value)) {
    return Object.keys(value).length === 0;
  }

  return false;
};

const isEmptyContainer = (value: unknown): boolean => {
  if (Array.isArray(value)) {
    return value.length === 0;
  }

  if (isPlainObject(value)) {
    return Object.keys(value).length === 0;
  }

  return false;
};

const areValuesEqual = (left: unknown, right: unknown): boolean => {
  if (left === right) {
    return true;
  }

  if (isEmptyContainer(left) && isEmptyContainer(right)) {
    return true;
  }

  const leftIsStructured = Array.isArray(left) || isPlainObject(left);
  const rightIsStructured = Array.isArray(right) || isPlainObject(right);

  if (leftIsStructured && rightIsStructured) {
    return JSON.stringify(left) === JSON.stringify(right);
  }

  return false;
};

const tryParseJsonString = (value: unknown): unknown => {
  if (typeof value !== 'string') {
    return value;
  }

  const trimmed = value.trim();
  if (!trimmed.startsWith('{') && !trimmed.startsWith('[')) {
    return value;
  }

  try {
    return JSON.parse(trimmed);
  } catch {
    return value;
  }
};

const normalizeComparableValue = (value: unknown): unknown => {
  return tryParseJsonString(value);
};

const formatSetValue = (value: unknown): string => {
  return `${t('admin.changelog.set')} ${formatScalar(value)}`.trim();
};

const resolveNestedFieldLabel = (field: string, nestedPath: string): string => {
  if (field === 'permission_overrides') {
    if (nestedPath === 'allow') {
      return t('admin.permissions.allow');
    }

    if (nestedPath === 'deny') {
      return t('admin.permissions.deny');
    }
  }

  return prettifyFieldName(nestedPath);
};

const formatBytes = (value: number | null | undefined): string => {
  if (!value || value <= 0) {
    return '';
  }

  const units = ['B', 'KB', 'MB', 'GB'];
  let size = value;
  let index = 0;

  while (size >= 1024 && index < units.length - 1) {
    size /= 1024;
    index += 1;
  }

  return `${size.toFixed(size >= 100 || index === 0 ? 0 : 1)} ${units[index]}`;
};

const normalizeMediaMap = (
  value: Record<string, ChangeLogMediaSnapshotItem | null> | null | undefined
): Record<string, ChangeLogMediaSnapshotItem | null> => {
  if (!value || typeof value !== 'object') {
    return {};
  }

  return value;
};

const resolveMediaPreviewUrl = (
  item: ChangeLogMediaSnapshotItem | null | undefined
): string | null => {
  if (!item?.path) {
    return null;
  }

  const rawPath = item.path.trim();
  if (!rawPath) {
    return null;
  }

  if (rawPath.startsWith('http://') || rawPath.startsWith('https://')) {
    return resolveAssetUrl(config.public.apiBase, rawPath);
  }

  const normalized = rawPath.replace(/^\/+/, '');
  const storagePath = normalized.startsWith('storage/')
    ? `/${normalized}`
    : `/storage/${normalized}`;
  return resolveAssetUrl(config.public.apiBase, storagePath);
};

const collectObjectDiff = (
  beforeValue: Record<string, unknown>,
  afterValue: Record<string, unknown>,
  prefix = ''
): Array<{ path: string; before: unknown; after: unknown }> => {
  const keys = new Set([...Object.keys(beforeValue), ...Object.keys(afterValue)]);
  const rows: Array<{ path: string; before: unknown; after: unknown }> = [];

  for (const key of keys) {
    const nextPath = prefix ? `${prefix}.${key}` : key;
    const beforeItem = beforeValue[key];
    const afterItem = afterValue[key];

    if (isPlainObject(beforeItem) || isPlainObject(afterItem)) {
      const nestedBefore = isPlainObject(beforeItem) ? beforeItem : {};
      const nestedAfter = isPlainObject(afterItem) ? afterItem : {};
      rows.push(...collectObjectDiff(nestedBefore, nestedAfter, nextPath));
      continue;
    }

    if (!areValuesEqual(beforeItem, afterItem)) {
      rows.push({
        path: nextPath,
        before: beforeItem,
        after: afterItem,
      });
    }
  }

  return rows;
};

const getChangedRows = (
  entry: AdminChangeLogEntry
): Array<{
  key: string;
  label: string;
  before: string;
  after: string;
  hideBefore: boolean;
  beforePreview: string | null;
  afterPreview: string | null;
}> => {
  if (entry.event === 'create') {
    return [];
  }

  const fields = entry.changed_fields ?? [];
  const beforeMap = (entry.before ?? {}) as Record<string, unknown>;
  const afterMap = (entry.after ?? {}) as Record<string, unknown>;
  const mediaBeforeMap = normalizeMediaMap(entry.media_before);
  const mediaAfterMap = normalizeMediaMap(entry.media_after);

  const rows: Array<{
    key: string;
    label: string;
    before: string;
    after: string;
    hideBefore: boolean;
    beforePreview: string | null;
    afterPreview: string | null;
  }> = [];

  for (const field of fields) {
    const beforeValue = normalizeComparableValue(beforeMap[field]);
    const afterValue = normalizeComparableValue(afterMap[field]);
    const fieldLabel = resolveFieldLabel(field);

    if (areValuesEqual(beforeValue, afterValue)) {
      continue;
    }

    if (isPlainObject(beforeValue) || isPlainObject(afterValue)) {
      const nestedBefore = isPlainObject(beforeValue) ? beforeValue : {};
      const nestedAfter = isPlainObject(afterValue) ? afterValue : {};
      const nestedRows = collectObjectDiff(nestedBefore, nestedAfter);

      for (const nestedRow of nestedRows) {
        const isInitialSet = isUnsetValue(nestedRow.before) && !isUnsetValue(nestedRow.after);

        rows.push({
          key: `${field}.${nestedRow.path}`,
          label: `${fieldLabel} · ${resolveNestedFieldLabel(field, nestedRow.path)}`,
          before: formatDiffValue(nestedRow.before, { field, nestedPath: nestedRow.path }),
          after: isInitialSet
            ? formatSetValue(nestedRow.after)
            : formatDiffValue(nestedRow.after, { field, nestedPath: nestedRow.path }),
          hideBefore: isInitialSet,
          beforePreview: null,
          afterPreview: null,
        });
      }

      continue;
    }

    if (Array.isArray(beforeValue) || Array.isArray(afterValue)) {
      if (areValuesEqual(beforeValue, afterValue)) {
        continue;
      }

      const isInitialSet = isUnsetValue(beforeValue) && !isUnsetValue(afterValue);

      rows.push({
        key: field,
        label: fieldLabel,
        before: formatDiffValue(beforeValue),
        after: isInitialSet ? formatSetValue(afterValue) : formatDiffValue(afterValue),
        hideBefore: isInitialSet,
        beforePreview: null,
        afterPreview: null,
      });
      continue;
    }

    const isInitialSet = isUnsetValue(beforeValue) && !isUnsetValue(afterValue);
    const avatarBeforePreview =
      field === 'avatar_id' ? resolveMediaPreviewUrl(mediaBeforeMap.avatar ?? null) : null;
    const avatarAfterPreview =
      field === 'avatar_id' ? resolveMediaPreviewUrl(mediaAfterMap.avatar ?? null) : null;

    rows.push({
      key: field,
      label: fieldLabel,
      before: formatDiffValue(beforeValue),
      after: isInitialSet ? formatSetValue(afterValue) : formatDiffValue(afterValue),
      hideBefore: isInitialSet,
      beforePreview: avatarBeforePreview,
      afterPreview: avatarAfterPreview,
    });
  }

  return rows;
};

const canRollback = (entry: AdminChangeLogEntry): boolean => {
  if (!hasPermission('admin.changelog.rollback')) {
    return false;
  }

  const modelPermission = resolveModelRollbackPermission(props.model);
  if (modelPermission && !hasPermission(modelPermission)) {
    return false;
  }

  if (entry.event === 'update') {
    return getChangedRows(entry).length > 0;
  }

  return true;
};

const resolveModelRollbackPermission = (model: string): string | null => {
  if (model === 'user') {
    return 'admin.users.update';
  }

  if (model === 'role') {
    return 'admin.roles.update';
  }

  return null;
};

const goToPage = (targetPage: number) => {
  page.value = Math.max(1, targetPage);
};

const toggleJson = (entryId: string) => {
  if (expandedEntryIds.has(entryId)) {
    expandedEntryIds.delete(entryId);
    return;
  }

  expandedEntryIds.add(entryId);
};

const openRollbackModal = (entry: AdminChangeLogEntry) => {
  rollbackEntry.value = entry;
  rollbackModalOpen.value = true;
};

const rollbackMessage = computed(() => {
  if (!rollbackEntry.value) {
    return '';
  }

  return t('admin.changelog.rollback.confirm', {
    version: rollbackEntry.value.version,
    event: eventLabel(rollbackEntry.value),
  });
});

const fetchChangeLog = async () => {
  if (!canReadChangeLog.value) {
    entries.value = [];
    loadError.value = '';
    lastPage.value = 1;
    return;
  }

  if (!props.entityId) {
    entries.value = [];
    loadError.value = '';
    lastPage.value = 1;
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    const response = await changeLogApi.list({
      model: props.model,
      entity_id: props.entityId,
      page: page.value,
      per_page: props.perPage,
    });

    entries.value = response.data;
    lastPage.value = Math.max(1, response.last_page ?? 1);
    perPage.value = response.per_page ?? props.perPage;
    listMode.value = response.list_mode ?? 'latest';
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.changelog.errors.load'));
  } finally {
    loading.value = false;
  }
};

const confirmRollback = async () => {
  if (!rollbackEntry.value) {
    return;
  }

  rollbackLoading.value = true;
  loadError.value = '';

  try {
    const result = await changeLogApi.rollback(rollbackEntry.value.id);
    rollbackModalOpen.value = false;
    rollbackEntry.value = null;
    emit('rolled-back', result);
    await fetchChangeLog();
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.changelog.errors.rollback'));
  } finally {
    rollbackLoading.value = false;
  }
};

watch(
  () => [props.model, props.entityId],
  () => {
    page.value = 1;
    expandedEntryIds.clear();
    void fetchChangeLog();
  },
  { immediate: true }
);

watch(
  () => props.refreshToken,
  () => {
    if (!props.entityId) {
      return;
    }

    void fetchChangeLog();
  }
);

watch(page, () => {
  void fetchChangeLog();
});
</script>

<style lang="scss" scoped>
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

.admin-changelog-item {
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--surface) 75%, transparent);
}

.admin-button,
.admin-button-secondary {
  border: 1px solid var(--border);
  transition:
    color 0.2s ease,
    border-color 0.2s ease,
    background 0.2s ease;
}

.admin-button {
  background: color-mix(in srgb, var(--surface) 85%, transparent);
  color: var(--text);
}

.admin-button-secondary {
  background: transparent;
  color: var(--muted);
}

.admin-button:hover,
.admin-button-secondary:hover {
  border-color: var(--accent);
  color: var(--accent);
}

.admin-changelog-badge {
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 0.1rem 0.5rem;
  font-weight: 600;
}

.admin-changelog-badge.is-create {
  border-color: color-mix(in srgb, #22c55e 55%, var(--border));
  color: #4ade80;
}

.admin-changelog-badge.is-update {
  border-color: color-mix(in srgb, #3b82f6 55%, var(--border));
  color: #93c5fd;
}

.admin-changelog-badge.is-restore {
  border-color: color-mix(in srgb, #a855f7 55%, var(--border));
  color: #c084fc;
}

.admin-changelog-json {
  margin: 0;
  padding: 0.5rem;
  border: 1px solid var(--border);
  border-radius: 0.6rem;
  background: color-mix(in srgb, var(--surface) 78%, transparent);
  color: var(--text);
  font-size: 0.75rem;
  line-height: 1.35;
  white-space: pre-wrap;
  word-break: break-word;
}

.admin-changelog-side {
  border: 1px solid var(--border);
}

.admin-changelog-label {
  font-weight: 600;
}

.admin-changelog-side-before {
  border-color: color-mix(in srgb, #f97316 55%, var(--border));
}

.admin-changelog-side-before .admin-changelog-label {
  color: #fb923c;
}

.admin-changelog-side-after {
  border-color: color-mix(in srgb, #22c55e 55%, var(--border));
}

.admin-changelog-side-after .admin-changelog-label {
  color: #4ade80;
}

.admin-diff-before {
  color: #fb923c;
  margin-left: 0.4rem;
}

.admin-diff-after {
  color: #4ade80;
  margin-left: 0.4rem;
}

.admin-diff-label {
  margin-right: 0.25rem;
  word-break: break-word;
}

.admin-diff-arrow {
  margin-left: 0.35rem;
  margin-right: 0.15rem;
}

.admin-diff-row {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 0.25rem;
}

.admin-diff-image {
  display: inline-block;
  vertical-align: middle;
}

.admin-diff-value {
  word-break: break-word;
}

.skeleton-line {
  display: inline-block;
  width: 100%;
  height: 0.85rem;
  border-radius: 999px;
  background: linear-gradient(
    90deg,
    rgba(148, 163, 184, 0.08) 25%,
    rgba(148, 163, 184, 0.18) 37%,
    rgba(148, 163, 184, 0.08) 63%
  );
  background-size: 400% 100%;
  animation: changelog-skeleton-shimmer 1.25s ease infinite;
}

.skeleton-line.is-badge {
  height: 1.6rem;
  width: 7.5rem;
}

.skeleton-meta-wrap {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
  min-width: 18rem;
}

.skeleton-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.skeleton-line.is-action {
  height: 2rem;
  width: 7rem;
}

.skeleton-line.is-meta {
  height: 0.95rem;
  width: 58%;
}

.skeleton-line.is-meta-short {
  height: 0.72rem;
  width: 6.2rem;
}

.skeleton-line.is-meta.medium {
  width: 46%;
}

.skeleton-line.is-meta.long {
  width: 74%;
}

@keyframes changelog-skeleton-shimmer {
  0% {
    background-position: 100% 0;
  }
  100% {
    background-position: 0 0;
  }
}
</style>
