<template>
  <section class="roles-show-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">Роль</h2>
      <p class="admin-muted mt-2 text-sm">Show-страница роли.</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">Загрузка...</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="role">
        <dl class="grid gap-3 sm:grid-cols-2">
          <div><dt class="admin-muted text-xs">Code</dt><dd class="font-mono text-xs">{{ role.code }}</dd></div>
          <div><dt class="admin-muted text-xs">Label</dt><dd>{{ role.label || '—' }}</dd></div>
          <div><dt class="admin-muted text-xs">Тип</dt><dd>{{ role.is_system ? 'Системная' : 'Пользовательская' }}</dd></div>
        </dl>

        <div class="mt-5 flex gap-2">
          <NuxtLink :to="`/admin/roles/${role.id}/edit`" class="admin-button rounded-lg px-4 py-2 text-sm">Edit</NuxtLink>
          <NuxtLink to="/admin/roles" class="admin-button-secondary rounded-lg px-4 py-2 text-sm">К списку</NuxtLink>
        </div>
      </template>
    </article>
  </section>
</template>

<script setup lang="ts">
import type { AdminRole } from '~/composables/useAdminRoles'
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon'

definePageMeta({
  layout: 'admin'
})

const route = useRoute()
const rolesApi = useAdminRoles()

const role = ref<AdminRole | null>(null)
const loading = ref(false)
const loadError = ref('')

const fetchRole = async () => {
  const id = String(route.params.id || '')

  if (!id) {
    loadError.value = 'Некорректный идентификатор роли.'
    return
  }

  loading.value = true
  loadError.value = ''

  try {
    role.value = await rolesApi.show(id)
  } catch (error) {
    loadError.value = getApiErrorMessage(error, 'Не удалось загрузить роль.')
  } finally {
    loading.value = false
  }
}

onMounted(fetchRole)
</script>

<style lang="scss" scoped src="./index.scss"></style>
