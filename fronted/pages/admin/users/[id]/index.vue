<template>
  <section class="users-show-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">Профиль пользователя</h2>
      <p class="admin-muted mt-2 text-sm">Show-страница пользователя.</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">Загрузка...</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="user">
        <dl class="grid gap-3 sm:grid-cols-2">
          <div><dt class="admin-muted text-xs">Имя</dt><dd>{{ user.first_name }}</dd></div>
          <div><dt class="admin-muted text-xs">Фамилия</dt><dd>{{ user.last_name }}</dd></div>
          <div><dt class="admin-muted text-xs">Отчество</dt><dd>{{ user.middle_name || '—' }}</dd></div>
          <div><dt class="admin-muted text-xs">Email</dt><dd>{{ user.email }}</dd></div>
          <div><dt class="admin-muted text-xs">Телефон</dt><dd>{{ user.phone || '—' }}</dd></div>
          <div><dt class="admin-muted text-xs">Роли</dt><dd>{{ (user.roles || []).join(', ') || '—' }}</dd></div>
        </dl>

        <div class="mt-5 flex gap-2">
          <NuxtLink :to="`/admin/users/${user.id}/edit`" class="admin-button rounded-lg px-4 py-2 text-sm">Edit</NuxtLink>
          <NuxtLink to="/admin/users" class="admin-button-secondary rounded-lg px-4 py-2 text-sm">К списку</NuxtLink>
        </div>
      </template>
    </article>
  </section>
</template>

<script setup lang="ts">
import type { AdminUser } from '~/composables/useAdminUsers'
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon'

definePageMeta({
  layout: 'admin'
})

const route = useRoute()
const usersApi = useAdminUsers()

const user = ref<AdminUser | null>(null)
const loading = ref(false)
const loadError = ref('')

const fetchUser = async () => {
  const id = String(route.params.id || '')

  if (!id) {
    loadError.value = 'Некорректный идентификатор пользователя.'
    return
  }

  loading.value = true
  loadError.value = ''

  try {
    user.value = await usersApi.show(id)
  } catch (error) {
    loadError.value = getApiErrorMessage(error, 'Не удалось загрузить пользователя.')
  } finally {
    loading.value = false
  }
}

onMounted(fetchUser)
</script>

<style lang="scss" scoped src="./index.scss"></style>
