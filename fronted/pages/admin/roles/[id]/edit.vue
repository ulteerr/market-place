<template>
  <section class="roles-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">Редактирование роли</h2>
      <p class="admin-muted mt-2 text-sm">Edit-страница роли.</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">Загрузка...</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <form v-else class="space-y-3" @submit.prevent="submitForm">
        <UiInput v-model="form.code" label="Code" required :disabled="saving || isSystem" :error="fieldErrors.code" />
        <UiInput v-model="form.label" label="Label" :disabled="saving || isSystem" :error="fieldErrors.label" />

        <p v-if="isSystem" class="admin-muted text-sm">Системные роли редактировать нельзя.</p>
        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button type="submit" class="admin-button rounded-lg px-4 py-2 text-sm" :disabled="saving || isSystem">
            {{ saving ? 'Сохраняем...' : 'Сохранить' }}
          </button>
          <NuxtLink :to="`/admin/roles/${route.params.id}`" class="admin-button-secondary rounded-lg px-4 py-2 text-sm">Отмена</NuxtLink>
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput.vue'
import type { UpdateRolePayload } from '~/composables/useAdminRoles'
import { getApiErrorPayload, getApiErrorMessage, getFieldError } from '~/composables/useAdminCrudCommon'

definePageMeta({
  layout: 'admin'
})

const route = useRoute()
const rolesApi = useAdminRoles()

const loading = ref(false)
const loadError = ref('')
const saving = ref(false)
const formError = ref('')
const isSystem = ref(false)

const form = reactive({
  code: '',
  label: ''
})

const fieldErrors = reactive<Record<string, string>>({
  code: '',
  label: ''
})

const resetErrors = () => {
  formError.value = ''
  fieldErrors.code = ''
  fieldErrors.label = ''
}

const fetchRole = async () => {
  const id = String(route.params.id || '')

  if (!id) {
    loadError.value = 'Некорректный идентификатор роли.'
    return
  }

  loading.value = true
  loadError.value = ''

  try {
    const role = await rolesApi.show(id)
    form.code = role.code
    form.label = role.label || ''
    isSystem.value = role.is_system
  } catch (error) {
    loadError.value = getApiErrorMessage(error, 'Не удалось загрузить роль.')
  } finally {
    loading.value = false
  }
}

const submitForm = async () => {
  if (isSystem.value) {
    return
  }

  saving.value = true
  resetErrors()

  try {
    const id = String(route.params.id || '')
    const payload: UpdateRolePayload = {
      code: form.code.trim(),
      label: form.label.trim() || null
    }

    await rolesApi.update(id, payload)
    await navigateTo(`/admin/roles/${id}`)
  } catch (error) {
    const payload = getApiErrorPayload(error)
    formError.value = getApiErrorMessage(error, 'Не удалось обновить роль.')
    fieldErrors.code = getFieldError(payload.errors, 'code')
    fieldErrors.label = getFieldError(payload.errors, 'label')
  } finally {
    saving.value = false
  }
}

onMounted(fetchRole)
</script>

<style lang="scss" scoped src="./edit.scss"></style>
