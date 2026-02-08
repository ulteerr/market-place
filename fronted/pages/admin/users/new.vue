<template>
  <section class="users-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">Новый пользователь</h2>
      <p class="admin-muted mt-2 text-sm">Создание пользователя в `/api/admin/users`.</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <form class="space-y-3" @submit.prevent="submitForm">
        <UiInput v-model="form.first_name" label="Имя" required :disabled="saving" :error="fieldErrors.first_name" />
        <UiInput v-model="form.last_name" label="Фамилия" required :disabled="saving" :error="fieldErrors.last_name" />
        <UiInput v-model="form.middle_name" label="Отчество" :disabled="saving" :error="fieldErrors.middle_name" />
        <UiInput v-model="form.email" preset="email" label="Email" required :disabled="saving" :error="fieldErrors.email" />
        <UiInput v-model="form.phone" preset="phone" label="Телефон" :disabled="saving" :error="fieldErrors.phone" />
        <UiInput v-model="form.password" preset="password" password-toggle label="Пароль" required :disabled="saving" :error="fieldErrors.password" />
        <UiInput v-model="form.password_confirmation" preset="password" password-toggle label="Подтверждение пароля" required :disabled="saving" />

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
            {{ saving ? 'Сохраняем...' : 'Создать' }}
          </button>
          <NuxtLink to="/admin/users" class="admin-button-secondary rounded-lg px-4 py-2 text-sm">Отмена</NuxtLink>
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput.vue'
import UiSelect from '~/components/ui/FormControls/UiSelect.vue'
import type { AdminRole } from '~/composables/useAdminRoles'
import type { CreateUserPayload } from '~/composables/useAdminUsers'
import { getApiErrorPayload, getApiErrorMessage, getFieldError } from '~/composables/useAdminCrudCommon'

definePageMeta({
  layout: 'admin'
})

const usersApi = useAdminUsers()
const rolesApi = useAdminRoles()

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

const submitForm = async () => {
  saving.value = true
  resetErrors()

  try {
    const payload: CreateUserPayload = {
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      middle_name: form.middle_name.trim() || null,
      email: form.email.trim(),
      phone: form.phone.trim() || null,
      password: form.password,
      password_confirmation: form.password_confirmation,
      roles: [...form.roles]
    }

    await usersApi.create(payload)
    await navigateTo('/admin/users')
  } catch (error) {
    const payload = getApiErrorPayload(error)
    formError.value = getApiErrorMessage(error, 'Не удалось создать пользователя.')
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

onMounted(fetchRoles)
</script>

<style lang="scss" scoped src="./new.scss"></style>
