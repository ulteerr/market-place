<template>
  <section class="roles-show-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.roles.show.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.roles.show.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="role">
        <dl class="grid gap-3 sm:grid-cols-2">
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.roles.show.labels.code') }}</dt>
            <dd class="font-mono text-xs">{{ role.code }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.roles.show.labels.label') }}</dt>
            <dd>{{ role.label || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.roles.show.labels.type') }}</dt>
            <dd>
              {{
                role.is_system
                  ? t('admin.roles.index.type.system')
                  : t('admin.roles.index.type.custom')
              }}
            </dd>
          </div>
        </dl>

        <div class="mt-5 flex gap-2">
          <NuxtLink
            :to="`/admin/roles/${role.id}/edit`"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            >{{ t('common.edit') }}</NuxtLink
          >
          <NuxtLink to="/admin/roles" class="admin-button-secondary rounded-lg px-4 py-2 text-sm">{{
            t('common.backToList')
          }}</NuxtLink>
        </div>
      </template>
    </article>

    <AdminChangeLogPanel
      model="role"
      :entity-id="role?.id || String(route.params.id || '')"
      @rolled-back="onRoleRolledBack"
    />
  </section>
</template>

<script setup lang="ts">
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import type { AdminRole } from '~/composables/useAdminRoles';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
});

const route = useRoute();
const rolesApi = useAdminRoles();

const role = ref<AdminRole | null>(null);
const loading = ref(false);
const loadError = ref('');

const fetchRole = async () => {
  const id = String(route.params.id || '');

  if (!id) {
    loadError.value = t('admin.roles.show.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    role.value = await rolesApi.show(id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.roles.show.errors.load'));
  } finally {
    loading.value = false;
  }
};

onMounted(fetchRole);

const onRoleRolledBack = async () => {
  await fetchRole();
};
</script>

<style lang="scss" scoped src="./index.scss"></style>
