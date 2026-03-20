<template>
  <section class="admin-show-page mx-auto w-full max-w-4xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.categories.show.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.categories.show.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <div v-if="loading" class="admin-page-skeleton space-y-3" aria-hidden="true">
        <div class="skeleton-line is-title" />
        <div class="grid gap-3 sm:grid-cols-2">
          <div v-for="index in 6" :key="index" class="space-y-2">
            <div class="skeleton-line is-label" />
            <div class="skeleton-line is-value" />
          </div>
        </div>
      </div>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="category">
        <dl class="grid gap-3 sm:grid-cols-2">
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.categories.fields.name') }}</dt>
            <dd>{{ category.name }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.categories.fields.slug') }}</dt>
            <dd class="font-mono text-sm">{{ category.slug }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.categories.fields.parent') }}</dt>
            <dd>{{ category.parent?.name || t('admin.categories.index.root') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.categories.fields.sortOrder') }}</dt>
            <dd>{{ category.sort_order }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.categories.fields.isActive') }}</dt>
            <dd>{{ resolveStatusLabel(category.is_active) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.categories.show.labels.children') }}</dt>
            <dd>{{ category.children_count ?? category.children?.length ?? 0 }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.categories.show.labels.activities') }}</dt>
            <dd>{{ category.activities_count ?? 0 }}</dd>
          </div>
        </dl>

        <div v-if="category.children?.length" class="mt-5">
          <h3 class="text-sm font-semibold">{{ t('admin.categories.show.childrenTitle') }}</h3>
          <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <article
              v-for="child in category.children"
              :key="child.id"
              class="rounded-xl border border-[var(--admin-border)] p-3"
            >
              <p class="font-medium">{{ child.name }}</p>
              <p class="admin-muted mt-1 text-xs">
                {{ t('admin.categories.index.card.slug', { value: child.slug }) }}
              </p>
              <NuxtLink
                :to="`/admin/categories?parent_id=${child.id}`"
                class="admin-button-secondary mt-3 inline-flex rounded-md px-2 py-1 text-xs"
              >
                {{ t('admin.categories.show.openChildren') }}
              </NuxtLink>
            </article>
          </div>
        </div>

        <div class="mt-5 flex gap-2">
          <NuxtLink
            v-if="canWriteCategories"
            :to="`/admin/categories/${category.id}/edit`"
            class="admin-button rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.edit') }}
          </NuxtLink>
          <NuxtLink
            to="/admin/categories"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.backToList') }}
          </NuxtLink>
        </div>
      </template>
    </article>

    <AdminChangeLogPanel
      v-if="canReadChangeLog"
      model="category"
      :entity-id="String(route.params.id || '')"
      @rolled-back="fetchCategory"
    />

    <AdminActionLogPanel model="category" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import type { AdminCategory } from '~/composables/useAdminCategories';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';

const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.categories.read',
});

const route = useRoute();
const categoriesApi = useAdminCategories();
const { hasPermission } = usePermissions();

const canWriteCategories = computed(() => hasPermission('admin.categories.update'));
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));

const category = ref<(AdminCategory & { children?: AdminCategory[] }) | null>(null);
const loading = ref(false);
const loadError = ref('');

const resolveStatusLabel = (isActive: boolean): string => {
  return isActive ? t('admin.categories.status.active') : t('admin.categories.status.inactive');
};

const fetchCategory = async () => {
  const id = String(route.params.id || '');

  if (!id) {
    loadError.value = t('admin.categories.show.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    category.value = await categoriesApi.show(id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.categories.show.errors.load'));
  } finally {
    loading.value = false;
  }
};

onMounted(fetchCategory);
</script>

<style lang="scss" scoped src="../../_shared/admin-show-page.scss"></style>
<style lang="scss" scoped>
.admin-page-skeleton {
  .skeleton-line {
    display: block;
    border-radius: 999px;
    background: linear-gradient(
      90deg,
      rgba(148, 163, 184, 0.12) 0%,
      rgba(148, 163, 184, 0.28) 50%,
      rgba(148, 163, 184, 0.12) 100%
    );
    background-size: 200% 100%;
    animation: admin-page-skeleton-shimmer 1.2s ease-in-out infinite;
  }

  .skeleton-line.is-title {
    width: 38%;
    height: 1.25rem;
  }

  .skeleton-line.is-label {
    width: 32%;
    height: 0.75rem;
  }

  .skeleton-line.is-value {
    width: 78%;
    height: 1rem;
  }
}

@keyframes admin-page-skeleton-shimmer {
  0% {
    background-position: 200% 0;
  }

  100% {
    background-position: -200% 0;
  }
}
</style>
