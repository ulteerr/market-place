<template>
  <div>
    <PageHero
      eyebrow="Public"
      title="Marketplace"
      description="Публичная часть приложения. Backend предоставляет API и аутентификацию."
    />

    <section class="mx-auto grid w-full max-w-6xl gap-4 px-4 py-10 md:grid-cols-2">
      <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Каталог</h2>
        <p class="mt-2 text-sm text-slate">Публичные страницы каталога и карточек товаров.</p>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Админ-раздел</h2>
        <p class="mt-2 text-sm text-slate">
          Переходите в <NuxtLink to="/admin" class="text-ember underline">/admin</NuxtLink>.
          Для неавторизованных пользователей будет выполнен редирект на <code>/login</code>.
        </p>
      </article>
    </section>
  </div>
</template>

<script setup lang="ts">
import PageHero from '~/components/ui/PageHero/PageHero.vue'
</script>
