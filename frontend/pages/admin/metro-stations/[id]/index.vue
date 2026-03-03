<template>
  <section class="admin-show-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.metro.stations.show.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.metro.stations.show.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="item">
        <dl class="grid gap-3 sm:grid-cols-2">
          <div class="min-w-0">
            <dt class="admin-muted text-xs">{{ t('admin.metro.stations.fields.name') }}</dt>
            <dd class="break-words">{{ item.name }}</dd>
          </div>
          <div class="min-w-0">
            <dt class="admin-muted text-xs">{{ t('admin.metro.stations.fields.externalId') }}</dt>
            <dd class="break-words">{{ item.external_id || t('common.dash') }}</dd>
          </div>
          <div class="min-w-0">
            <dt class="admin-muted text-xs">{{ t('admin.metro.stations.fields.lineId') }}</dt>
            <dd class="break-words">{{ item.line_id || t('common.dash') }}</dd>
          </div>
          <div class="min-w-0">
            <dt class="admin-muted text-xs">{{ t('admin.metro.stations.fields.isClosed') }}</dt>
            <dd class="break-words">
              {{
                item.is_closed === null
                  ? t('common.dash')
                  : item.is_closed
                    ? t('admin.metro.stations.status.closedYes')
                    : t('admin.metro.stations.status.closedNo')
              }}
            </dd>
          </div>
          <div class="min-w-0">
            <dt class="admin-muted text-xs">{{ t('admin.metro.stations.fields.metroLine') }}</dt>
            <dd class="min-w-0">
              <AdminMetroLineBadge
                :to="`/admin/metro-lines/${item.metro_line_id}`"
                :name="metroLineName"
                :color="metroLineColor"
                label-class="min-w-0 break-words"
              />
            </dd>
          </div>
          <div class="min-w-0">
            <dt class="admin-muted text-xs">{{ t('admin.metro.stations.fields.cityId') }}</dt>
            <dd class="font-mono text-xs break-all">
              <AdminLink :to="`/admin/metro-stations?search=${encodeURIComponent(item.city_id)}`">
                {{ item.city_id }}
              </AdminLink>
            </dd>
          </div>
        </dl>

        <div class="mt-5 flex flex-wrap gap-2">
          <NuxtLink
            :to="`/admin/metro-stations/${item.id}/edit`"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            >{{ t('common.edit') }}</NuxtLink
          >
          <NuxtLink
            to="/admin/metro-stations"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
            >{{ t('common.backToList') }}</NuxtLink
          >
        </div>
      </template>
    </article>

    <AdminChangeLogPanel model="metro_station" :entity-id="String(route.params.id || '')" />

    <AdminActionLogPanel model="metro_station" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import AdminLink from '~/components/admin/AdminLink.vue';
import AdminMetroLineBadge from '~/components/admin/Metro/AdminMetroLineBadge.vue';
import type { AdminMetroStation } from '~/composables/useAdminMetroStations';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.panel.access',
});

const route = useRoute();
const api = useAdminMetroStations();
const item = ref<AdminMetroStation | null>(null);
const loading = ref(false);
const loadError = ref('');

const metroLineName = computed(() => {
  return item.value?.metro_line?.name || item.value?.metro_line_id || t('common.dash');
});

const metroLineColor = computed(() => {
  return item.value?.metro_line?.color || null;
});

const fetchItem = async () => {
  const id = String(route.params.id || '');
  if (!id) {
    loadError.value = t('admin.metro.stations.show.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';
  try {
    item.value = await api.show(id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.metro.stations.show.errors.load'));
  } finally {
    loading.value = false;
  }
};

onMounted(fetchItem);
</script>

<style lang="scss" scoped src="../../_shared/admin-show-page.scss"></style>
