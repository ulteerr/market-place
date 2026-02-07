<template>
  <div>
    <PageHero
      eyebrow="Auth"
      title="Вход в админ-панель"
      description="Единая форма входа: логика общая, шаблоны могут отличаться по верстке."
    />

    <section class="px-4 py-10">
      <LoginForm
        variant="page"
        :require-admin-access="true"
        success-redirect-to="/admin"
        denied-redirect-to="/"
      />
    </section>
  </div>
</template>

<script setup lang="ts">
import PageHero from '~/components/ui/PageHero/PageHero.vue'
import LoginForm from '~/components/auth/LoginForm/LoginForm.vue'

definePageMeta({
  middleware: () => {
    const { isAuthenticated, canAccessAdminPanel } = useAuth()

    if (!isAuthenticated.value) {
      return
    }

    return navigateTo(canAccessAdminPanel.value ? '/admin' : '/')
  }
})
</script>
