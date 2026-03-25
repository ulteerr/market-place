<template>
  <div class="default-layout min-h-screen flex flex-col">
    <ClientOnly>
      <AdminToolbar />
    </ClientOnly>
    <AppHeader />

    <main class="flex-1">
      <slot />
    </main>

    <AppFooter />
  </div>
</template>

<script setup lang="ts">
import AdminToolbar from '~/components/layout/AdminToolbar/AdminToolbar.vue';
import AppHeader from '~/components/layout/AppHeader/AppHeader.vue';
import AppFooter from '~/components/layout/AppFooter/AppFooter.vue';
import { usePublicSchemaJsonLd } from '~/composables/schema/usePublicSchemaJsonLd';

usePublicSchemaJsonLd();
</script>

<style lang="scss" src="./default.scss"></style>
