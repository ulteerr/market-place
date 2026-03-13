<template>
  <div class="account-layout min-h-screen flex flex-col">
    <ClientOnly>
      <AdminToolbar />
    </ClientOnly>
    <AppHeader />

    <main class="account-layout__main flex-1">
      <slot />
    </main>

    <AppFooter />
  </div>
</template>

<script setup lang="ts">
import AdminToolbar from '~/components/layout/AdminToolbar/AdminToolbar.vue';
import AppHeader from '~/components/layout/AppHeader/AppHeader.vue';
import AppFooter from '~/components/layout/AppFooter/AppFooter.vue';
</script>

<style lang="scss" src="./account.scss"></style>
