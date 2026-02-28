<template>
  <section class="roles-show-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.metro.lines.show.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.metro.lines.show.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="item">
        <dl class="grid gap-3 sm:grid-cols-2">
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.metro.lines.fields.name') }}</dt>
            <dd>{{ item.name }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.metro.lines.fields.externalId') }}</dt>
            <dd>{{ item.external_id || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.metro.lines.fields.lineId') }}</dt>
            <dd>{{ item.line_id || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.metro.lines.fields.color') }}</dt>
            <dd>
              <span v-if="item.color" class="inline-flex items-center gap-2">
                <AdminColorDot :color="item.color" />
              </span>
              <span v-else>{{ t('common.dash') }}</span>
            </dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.metro.lines.fields.cityId') }}</dt>
            <dd class="font-mono text-xs">{{ item.city_id }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.metro.lines.fields.source') }}</dt>
            <dd>{{ item.source }}</dd>
          </div>
        </dl>

        <div class="mt-5 flex gap-2">
          <NuxtLink
            :to="`/admin/metro-lines/${item.id}/edit`"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            >{{ t('common.edit') }}</NuxtLink
          >
          <NuxtLink
            to="/admin/metro-lines"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
            >{{ t('common.backToList') }}</NuxtLink
          >
        </div>
      </template>
    </article>

    <AdminChangeLogPanel model="metro_line" :entity-id="String(route.params.id || '')" />

    <AdminActionLogPanel model="metro_line" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import AdminColorDot from '~/components/admin/Metro/AdminColorDot.vue';
import type { AdminMetroLine } from '~/composables/useAdminMetroLines';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.panel.access',
});

const route = useRoute();
const api = useAdminMetroLines();

const item = ref<AdminMetroLine | null>(null);
const loading = ref(false);
const loadError = ref('');

const fetchItem = async () => {
  const id = String(route.params.id || '');
  if (!id) {
    loadError.value = t('admin.metro.lines.show.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';
  try {
    item.value = await api.show(id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.metro.lines.show.errors.load'));
  } finally {
    loading.value = false;
  }
};

onMounted(fetchItem);
</script>

<style lang="scss" scoped src="../../roles/[id]/index.scss"></style>
