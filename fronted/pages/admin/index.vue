<template>
  <div>
    <PageHero
      eyebrow="Admin"
      title="Панель управления"
      description="Раздел администрирования доступен только после авторизации."
    />

    <section class="mx-auto w-full max-w-6xl px-4 py-10">
      <div class="grid gap-4 md:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-semibold">Управление пользователями</h2>
          <p class="mt-2 text-sm text-slate">CRUD-поток для пользователей, ролей и прав доступа.</p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-semibold">Управление контентом</h2>
          <p class="mt-2 text-sm text-slate">Редактирование разделов, модерация и административные действия.</p>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import PageHero from '~/components/ui/PageHero/PageHero.vue'
</script>
