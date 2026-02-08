<template>
  <section class="mx-auto w-full max-w-6xl space-y-6 admin-dashboard">
    <div class="admin-panel rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">Панель управления</h2>
      <p class="mt-2 text-sm admin-muted-text">
        Выберите модуль в левом меню. Здесь отображается контент выбранного раздела.
      </p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <article class="admin-panel rounded-2xl p-5">
        <h3 class="text-base font-semibold">Пользователи</h3>
        <p class="mt-2 text-sm admin-muted-text">
          Управление ролями, доступами и профилями администраторов.
        </p>
      </article>

      <article class="admin-panel rounded-2xl p-5">
        <h3 class="text-base font-semibold">Контент</h3>
        <p class="mt-2 text-sm admin-muted-text">
          Редактирование разделов, настройка видимости и модерирование.
        </p>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})
</script>

<style lang="scss" scoped src="./index.scss"></style>
