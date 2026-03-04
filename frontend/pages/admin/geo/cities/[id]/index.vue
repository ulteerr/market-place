<template>
  <section class="admin-show-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.geo.cities.show.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.geo.cities.show.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="item">
        <dl class="grid gap-3 sm:grid-cols-2">
          <div class="min-w-0">
            <dt class="admin-muted text-xs">{{ t('admin.geo.cities.fields.name') }}</dt>
            <dd class="break-words">{{ item.name }}</dd>
          </div>
          <div class="min-w-0">
            <dt class="admin-muted text-xs">{{ t('admin.geo.cities.fields.country') }}</dt>
            <dd class="text-xs break-words">
              <AdminLink v-if="item.country_id" :to="`/admin/geo/countries/${item.country_id}`">
                {{ countryName }}
              </AdminLink>
              <span v-else>{{ t('common.dash') }}</span>
            </dd>
          </div>
          <div class="min-w-0">
            <dt class="admin-muted text-xs">{{ t('admin.geo.cities.fields.region') }}</dt>
            <dd class="text-xs break-words">
              <AdminLink v-if="item.region_id" :to="`/admin/geo/regions/${item.region_id}`">
                {{ regionName }}
              </AdminLink>
              <span v-else>{{ t('common.dash') }}</span>
            </dd>
          </div>
        </dl>

        <div class="mt-5 flex gap-2">
          <NuxtLink
            :to="`/admin/geo/cities/${item.id}/edit`"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            >{{ t('common.edit') }}</NuxtLink
          >
          <NuxtLink
            to="/admin/geo/cities"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
            >{{ t('common.backToList') }}</NuxtLink
          >
        </div>
      </template>
    </article>

    <AdminChangeLogPanel model="geo_city" :entity-id="String(route.params.id || '')" />

    <AdminActionLogPanel model="geo_city" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import AdminLink from '~/components/admin/AdminLink/AdminLink.vue';
import type { AdminGeoCity } from '~/composables/useAdminGeoCities';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';

const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.geo.read',
});

const route = useRoute();
const api = useAdminGeoCities();
const item = ref<AdminGeoCity | null>(null);
const loading = ref(false);
const loadError = ref('');

const countryName = computed(() => {
  return item.value?.country?.name || item.value?.country_id || t('common.dash');
});

const regionName = computed(() => {
  return item.value?.region?.name || item.value?.region_id || t('common.dash');
});

const fetchItem = async () => {
  const id = String(route.params.id || '');
  if (!id) {
    loadError.value = t('admin.geo.cities.show.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';
  try {
    item.value = await api.show(id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.geo.cities.show.errors.load'));
  } finally {
    loading.value = false;
  }
};

onMounted(fetchItem);
</script>

<style lang="scss" scoped src="../../../_shared/admin-show-page.scss"></style>
