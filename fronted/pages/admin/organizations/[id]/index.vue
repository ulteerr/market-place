<template>
  <section class="mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.organizations.show.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.organizations.show.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="organization">
        <dl class="grid gap-3 sm:grid-cols-2">
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.name') }}</dt>
            <dd>{{ organization.name }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.status') }}</dt>
            <dd>{{ resolveStatusLabel(organization.status) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">
              {{ t('admin.organizations.fields.ownershipStatus') }}
            </dt>
            <dd>{{ resolveOwnershipLabel(organization.ownership_status) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.sourceType') }}</dt>
            <dd>{{ resolveSourceLabel(organization.source_type) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.email') }}</dt>
            <dd>{{ organization.email || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.phone') }}</dt>
            <dd>{{ organization.phone || t('common.dash') }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.address') }}</dt>
            <dd>{{ organization.address || t('common.dash') }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.description') }}</dt>
            <dd>{{ organization.description || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.owner') }}</dt>
            <dd>
              <AdminLink
                v-if="organization.owner?.id"
                :to="`/admin/users/${organization.owner.id}`"
              >
                {{ resolveOwnerLabel(organization) }}
              </AdminLink>
              <span v-else>{{ resolveOwnerLabel(organization) }}</span>
            </dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.ownerUserId') }}</dt>
            <dd>{{ organization.owner_user_id || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.createdAt') }}</dt>
            <dd>{{ formatDate(organization.created_at) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.claimedAt') }}</dt>
            <dd>{{ formatDate(organization.claimed_at) }}</dd>
          </div>
        </dl>

        <div class="mt-5 flex gap-2">
          <NuxtLink
            v-if="canWriteOrganizations"
            :to="`/admin/organizations/${organization.id}/edit`"
            class="admin-button rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.edit') }}
          </NuxtLink>
          <NuxtLink
            to="/admin/organizations"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.backToList') }}
          </NuxtLink>
        </div>
      </template>
    </article>

    <AdminChangeLogPanel
      v-if="canReadChangeLog"
      model="organization"
      :entity-id="organization?.id || String(route.params.id || '')"
      @rolled-back="onRolledBack"
    />
  </section>
</template>

<script setup lang="ts">
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import AdminLink from '~/components/admin/AdminLink.vue';
import type {
  AdminOrganization,
  OrganizationOwnershipStatus,
  OrganizationSourceType,
  OrganizationStatus,
} from '~/composables/useAdminOrganizations';
import { getAdminOrganizationOwnerName } from '~/composables/useAdminOrganizations';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';

const { t, locale } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'org.company.profile.read',
});

const route = useRoute();
const organizationsApi = useAdminOrganizations();
const { hasPermission } = usePermissions();

const canWriteOrganizations = computed(() => hasPermission('org.company.profile.update'));
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));

const organization = ref<AdminOrganization | null>(null);
const loading = ref(false);
const loadError = ref('');

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

  return t(`admin.organizations.status.${status}`);
};

const resolveOwnershipLabel = (status: OrganizationOwnershipStatus | null | undefined): string => {
  if (!status) {
    return t('common.dash');
  }

  return t(`admin.organizations.ownership.${status}`);
};

const resolveSourceLabel = (source: OrganizationSourceType | null | undefined): string => {
  if (!source) {
    return t('common.dash');
  }

  const sourceMap: Record<OrganizationSourceType, string> = {
    manual: 'admin.organizations.source.manual',
    import: 'admin.organizations.source.import',
    parsed: 'admin.organizations.source.parsed',
    self_registered: 'admin.organizations.source.selfRegistered',
  };

  return t(sourceMap[source]);
};

const resolveOwnerLabel = (item: AdminOrganization): string => {
  return getAdminOrganizationOwnerName(item) || t('common.dash');
};

const fetchOrganization = async () => {
  const id = String(route.params.id || '');

  if (!id) {
    loadError.value = t('admin.organizations.show.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    organization.value = await organizationsApi.show(id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.organizations.show.errors.load'));
  } finally {
    loading.value = false;
  }
};

const onRolledBack = async () => {
  await fetchOrganization();
};

onMounted(fetchOrganization);
</script>
