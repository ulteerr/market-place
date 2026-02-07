<template>
  <section :class="[styles.wrap, isPopup ? styles.wrapPopup : styles.wrapPage]">
    <h1 :class="styles.title">{{ isAuthenticated ? 'Статус' : 'Вход' }}</h1>
    <p :class="styles.description">{{ isAuthenticated ? authenticatedDescriptionText : descriptionText }}</p>

    <div v-if="isAuthenticated" :class="styles.statusBox">
      <NuxtLink to="/" :class="styles.statusLink">Вернуться на главную</NuxtLink>
    </div>

    <form v-else :class="styles.form" @submit.prevent="onSubmit">
      <div>
        <label :for="emailInputId" :class="styles.label">Email</label>
        <input :id="emailInputId" v-model="email" type="email" required :class="styles.input" />
      </div>

      <div>
        <label :for="passwordInputId" :class="styles.label">Пароль</label>
        <input :id="passwordInputId" v-model="password" type="password" required :class="styles.input" />
      </div>

      <p v-if="error" :class="styles.error">{{ error }}</p>

      <button type="submit" :class="styles.button" :disabled="pending">
        {{ pending ? 'Входим...' : 'Войти' }}
      </button>
    </form>
  </section>
</template>

<script setup lang="ts">
import styles from './LoginForm.module.scss'

type LoginFormVariant = 'page' | 'popup'

const props = withDefaults(
  defineProps<{
    variant?: LoginFormVariant
    description?: string
    requireAdminAccess?: boolean
    successRedirectTo?: string
    deniedRedirectTo?: string
  }>(),
  {
    variant: 'page',
    description: '',
    requireAdminAccess: false,
    successRedirectTo: '/admin',
    deniedRedirectTo: '/'
  }
)

const { isAuthenticated, canAccessAdminPanel, login } = useAuth()

const email = ref('')
const password = ref('')
const pending = ref(false)
const error = ref('')
const uid = useId()
const emailInputId = `email-${uid}`
const passwordInputId = `password-${uid}`
const isPopup = computed(() => props.variant === 'popup')
const canPassAccessCheck = computed(() => {
  if (!props.requireAdminAccess) {
    return true
  }

  return canAccessAdminPanel.value
})
const nextPath = computed(() =>
  canPassAccessCheck.value ? props.successRedirectTo : props.deniedRedirectTo
)
const descriptionText = computed(() => {
  if (props.description) {
    return props.description
  }

  return isPopup.value
    ? 'Авторизуйтесь, чтобы продолжить.'
    : 'Авторизуйтесь, чтобы открыть административный раздел.'
})
const authenticatedDescriptionText = computed(() => {
  if (props.requireAdminAccess && !canAccessAdminPanel.value) {
    return 'Вы авторизованы, но у вас нет доступа к административному разделу.'
  }

  return 'Вы уже авторизованы.'
})

const onSubmit = async () => {
  if (isAuthenticated.value) {
    return
  }

  pending.value = true
  error.value = ''

  try {
    await login(email.value, password.value)
    await navigateTo(nextPath.value)
  } catch {
    error.value = 'Не удалось авторизоваться. Проверьте email и пароль.'
  } finally {
    pending.value = false
  }
}
</script>
