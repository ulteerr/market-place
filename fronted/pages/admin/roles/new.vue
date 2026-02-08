<template>
  <section class="roles-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">Новая роль</h2>
      <p class="admin-muted mt-2 text-sm">Создание роли в `/api/admin/roles`.</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <form class="space-y-3" @submit.prevent="submitForm">
        <UiInput v-model="form.code" label="Code" required :disabled="saving" :error="fieldErrors.code" />
        <UiInput v-model="form.label" label="Label" :disabled="saving" :error="fieldErrors.label" />

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button type="submit" class="admin-button rounded-lg px-4 py-2 text-sm" :disabled="saving">
            {{ saving ? 'Сохраняем...' : 'Создать' }}
          </button>
          <NuxtLink to="/admin/roles" class="admin-button-secondary rounded-lg px-4 py-2 text-sm">Отмена</NuxtLink>
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput.vue'
import type { CreateRolePayload } from '~/composables/useAdminRoles'
import { getApiErrorPayload, getApiErrorMessage, getFieldError } from '~/composables/useAdminCrudCommon'

definePageMeta({
  layout: 'admin'
})

const rolesApi = useAdminRoles()

const saving = ref(false)
const formError = ref('')

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

const submitForm = async () => {
  saving.value = true
  resetErrors()

  try {
    const payload: CreateRolePayload = {
      code: form.code.trim(),
      label: form.label.trim() || null
    }

    await rolesApi.create(payload)
    await navigateTo('/admin/roles')
  } catch (error) {
    const payload = getApiErrorPayload(error)
    formError.value = getApiErrorMessage(error, 'Не удалось создать роль.')
    fieldErrors.code = getFieldError(payload.errors, 'code')
    fieldErrors.label = getFieldError(payload.errors, 'label')
  } finally {
    saving.value = false
  }
}
</script>

<style lang="scss" scoped src="./new.scss"></style>
