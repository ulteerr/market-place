<template>
  <section :class="styles.page">
    <div :class="styles.inner">
      <div :class="styles.head">
        <p :class="styles.eyebrow">Admin Auth</p>
        <h1 :class="styles.title">Вход в админ-панель</h1>
        <p :class="styles.description">Авторизуйтесь, чтобы перейти к управлению системой.</p>
      </div>

      <LoginForm
        variant="page"
        :require-admin-access="true"
        success-redirect-to="/admin"
        denied-redirect-to="/"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import LoginForm from '~/components/auth/LoginForm/LoginForm.vue';
import styles from './login.module.scss';

definePageMeta({
  middleware: () => {
    const { isAuthenticated, canAccessAdminPanel } = useAuth();

    if (!isAuthenticated.value) {
      return;
    }

    return navigateTo(canAccessAdminPanel.value ? '/admin' : '/');
  },
});
</script>
