<template>
  <section class="roles-show-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">Ветка метро</h2>
      <p class="admin-muted mt-2 text-sm">Детали ветки метро</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">Загрузка...</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="item">
        <dl class="grid gap-3 sm:grid-cols-2">
          <div>
            <dt class="admin-muted text-xs">Название</dt>
            <dd>{{ item.name }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">External ID</dt>
            <dd>{{ item.external_id || '—' }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">Line ID</dt>
            <dd>{{ item.line_id || '—' }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">Color</dt>
            <dd>{{ item.color || '—' }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">City ID</dt>
            <dd class="font-mono text-xs">{{ item.city_id }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">Source</dt>
            <dd>{{ item.source }}</dd>
          </div>
        </dl>

        <div class="mt-5 flex gap-2">
          <NuxtLink
            :to="`/admin/metro-lines/${item.id}/edit`"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            >Редактировать</NuxtLink
          >
          <NuxtLink
            to="/admin/metro-lines"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
            >К списку</NuxtLink
          >
        </div>
      </template>
    </article>
  </section>
</template>

<script setup lang="ts">
import type { AdminMetroLine } from '~/composables/useAdminMetroLines';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';

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
    loadError.value = 'Некорректный ID';
    return;
  }

  loading.value = true;
  loadError.value = '';
  try {
    item.value = await api.show(id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, 'Не удалось загрузить ветку метро.');
  } finally {
    loading.value = false;
  }
};

onMounted(fetchItem);
</script>

<style lang="scss" scoped src="../../roles/[id]/index.scss"></style>
