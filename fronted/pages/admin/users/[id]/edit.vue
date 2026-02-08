<template>
  <section class="users-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">Редактирование пользователя</h2>
      <p class="admin-muted mt-2 text-sm">Edit-страница пользователя.</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">Загрузка...</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <form v-else class="space-y-3" @submit.prevent="submitForm">
        <UiInput v-model="form.first_name" label="Имя" required :disabled="saving" :error="fieldErrors.first_name" />
        <UiInput v-model="form.last_name" label="Фамилия" required :disabled="saving" :error="fieldErrors.last_name" />
        <UiInput v-model="form.middle_name" label="Отчество" :disabled="saving" :error="fieldErrors.middle_name" />
        <UiInput v-model="form.email" preset="email" label="Email" required :disabled="saving" :error="fieldErrors.email" />
        <UiInput v-model="form.phone" preset="phone" label="Телефон" :disabled="saving" :error="fieldErrors.phone" />
        <UiInput v-model="form.password" preset="password" password-toggle label="Новый пароль" :disabled="saving" :error="fieldErrors.password" />
        <UiInput v-model="form.password_confirmation" preset="password" password-toggle label="Подтверждение нового пароля" :disabled="saving" />

        <UiSelect
          v-model="form.roles"
          label="Роли"
          :options="roleOptions"
          placeholder="Выберите роли"
          multiple
          searchable
          :disabled="saving || loadingRoles"
          :error="fieldErrors.roles"
        />

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button type="submit" class="admin-button rounded-lg px-4 py-2 text-sm" :disabled="saving">
            {{ saving ? 'Сохраняем...' : 'Сохранить' }}
          </button>
          <NuxtLink :to="`/admin/users/${route.params.id}`" class="admin-button-secondary rounded-lg px-4 py-2 text-sm">Отмена</NuxtLink>
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput.vue'
import UiSelect from '~/components/ui/FormControls/UiSelect.vue'
import type { AdminRole } from '~/composables/useAdminRoles'
import type { UpdateUserPayload } from '~/composables/useAdminUsers'
import { getApiErrorPayload, getApiErrorMessage, getFieldError } from '~/composables/useAdminCrudCommon'

definePageMeta({
  layout: 'admin'
})

const route = useRoute()
const usersApi = useAdminUsers()
const rolesApi = useAdminRoles()

const loading = ref(false)
const loadError = ref('')
const saving = ref(false)
const loadingRoles = ref(false)
const formError = ref('')
const roles = ref<AdminRole[]>([])

const form = reactive({
  first_name: '',
  last_name: '',
  middle_name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  roles: [] as string[]
})

const fieldErrors = reactive<Record<string, string>>({
  first_name: '',
  last_name: '',
  middle_name: '',
  email: '',
  phone: '',
  password: '',
  roles: ''
})

const roleOptions = computed(() => {
  return roles.value.map((role) => ({
    label: role.label ? `${role.code} (${role.label})` : role.code,
    value: role.code
  }))
})

const resetErrors = () => {
  formError.value = ''
  fieldErrors.first_name = ''
  fieldErrors.last_name = ''
  fieldErrors.middle_name = ''
  fieldErrors.email = ''
  fieldErrors.phone = ''
  fieldErrors.password = ''
  fieldErrors.roles = ''
}

const fetchRoles = async () => {
  loadingRoles.value = true

  try {
    const page = await rolesApi.list({
      per_page: 100,
      sort_by: 'code',
      sort_dir: 'asc'
    })
    roles.value = page.data
  } finally {
    loadingRoles.value = false
  }
}

const fetchUser = async () => {
  const id = String(route.params.id || '')

  if (!id) {
    loadError.value = 'Некорректный идентификатор пользователя.'
    return
  }

  loading.value = true
  loadError.value = ''

  try {
    const user = await usersApi.show(id)
    form.first_name = user.first_name || ''
    form.last_name = user.last_name || ''
    form.middle_name = user.middle_name || ''
    form.email = user.email || ''
    form.phone = user.phone || ''
    form.roles = [...(user.roles || [])]
  } catch (error) {
    loadError.value = getApiErrorMessage(error, 'Не удалось загрузить пользователя.')
  } finally {
    loading.value = false
  }
}

const submitForm = async () => {
  saving.value = true
  resetErrors()

  try {
    const id = String(route.params.id || '')
    const payload: UpdateUserPayload = {
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      middle_name: form.middle_name.trim() || null,
      email: form.email.trim(),
      phone: form.phone.trim() || null,
      roles: [...form.roles]
    }

    if (form.password.trim()) {
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
    }

    await usersApi.update(id, payload)
    await navigateTo(`/admin/users/${id}`)
  } catch (error) {
    const payload = getApiErrorPayload(error)
    formError.value = getApiErrorMessage(error, 'Не удалось обновить пользователя.')
    fieldErrors.first_name = getFieldError(payload.errors, 'first_name')
    fieldErrors.last_name = getFieldError(payload.errors, 'last_name')
    fieldErrors.middle_name = getFieldError(payload.errors, 'middle_name')
    fieldErrors.email = getFieldError(payload.errors, 'email')
    fieldErrors.phone = getFieldError(payload.errors, 'phone')
    fieldErrors.password = getFieldError(payload.errors, 'password')
    fieldErrors.roles = getFieldError(payload.errors, 'roles') || getFieldError(payload.errors, 'roles.0')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await Promise.all([fetchUser(), fetchRoles()])
})
</script>

<style lang="scss" scoped src="./edit.scss"></style>
