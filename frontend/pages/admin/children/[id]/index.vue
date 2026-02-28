<template>
  <section class="mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.children.show.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.children.show.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="child">
        <dl class="grid gap-3 sm:grid-cols-2">
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.children.show.labels.fullName') }}</dt>
            <dd>{{ resolveChildFullName(child) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.children.show.labels.birthDate') }}</dt>
            <dd>{{ child.birth_date || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.children.show.labels.gender') }}</dt>
            <dd>{{ resolveGenderLabel(child.gender) }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="admin-muted text-xs">{{ t('admin.children.show.labels.user') }}</dt>
            <dd>
              <AdminLink :to="`/admin/users/${child.user?.id || child.user_id}`">
                {{ resolveUserLabel(child) }}
              </AdminLink>
            </dd>
          </div>
        </dl>

        <div class="mt-5 flex gap-2">
          <NuxtLink
            v-if="canWriteChildren"
            :to="`/admin/children/${child.id}/edit`"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            >{{ t('common.edit') }}</NuxtLink
          >
          <NuxtLink
            to="/admin/children"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
            >{{ t('common.backToList') }}</NuxtLink
          >
        </div>
      </template>
    </article>

    <AdminChangeLogPanel
      v-if="canReadChangeLog"
      model="child"
      :entity-id="child?.id || String(route.params.id || '')"
      @rolled-back="onChildRolledBack"
    />

    <AdminActionLogPanel model="child" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import AdminLink from '~/components/admin/AdminLink.vue';
import type { AdminChild } from '~/composables/useAdminChildren';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'org.children.read',
});

const route = useRoute();
const childrenApi = useAdminChildren();
const { hasPermission } = usePermissions();
const canWriteChildren = computed(() => hasPermission('org.children.write'));
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));

const child = ref<AdminChild | null>(null);
const loading = ref(false);
const loadError = ref('');

const resolveUserLabel = (item: AdminChild): string => {
  const fullName = [item.user?.last_name, item.user?.first_name, item.user?.middle_name]
    .filter((part): part is string => typeof part === 'string' && part.trim().length > 0)
    .join(' ');

  if (fullName) {
    return fullName;
  }

  return item.user?.email || item.user_id || t('common.dash');
};

const resolveChildFullName = (item: AdminChild): string => {
  return [item.last_name, item.first_name, item.middle_name]
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

const fetchChild = async () => {
  const id = String(route.params.id || '');

  if (!id) {
    loadError.value = t('admin.children.show.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    child.value = await childrenApi.show(id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.children.show.errors.load'));
  } finally {
    loading.value = false;
  }
};

onMounted(fetchChild);

const onChildRolledBack = async () => {
  await fetchChild();
};
</script>
