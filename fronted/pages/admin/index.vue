<template>
  <section class="mx-auto w-full max-w-6xl space-y-6 admin-dashboard">
    <div class="admin-panel rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.dashboard.title') }}</h2>
      <p class="mt-2 text-sm admin-muted-text">
        {{ t('admin.dashboard.subtitle') }}
      </p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <article class="admin-panel rounded-2xl p-5">
        <h3 class="text-base font-semibold">{{ t('admin.dashboard.usersTitle') }}</h3>
        <p class="mt-2 text-sm admin-muted-text">
          {{ t('admin.dashboard.usersSubtitle') }}
        </p>
      </article>

      <article class="admin-panel rounded-2xl p-5">
        <h3 class="text-base font-semibold">{{ t('admin.dashboard.contentTitle') }}</h3>
        <p class="mt-2 text-sm admin-muted-text">
          {{ t('admin.dashboard.contentSubtitle') }}
        </p>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
});
</script>

<style lang="scss" scoped src="./index.scss"></style>
