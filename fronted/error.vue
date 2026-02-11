<template>
  <div class="mx-auto flex min-h-[60vh] w-full max-w-3xl items-center px-4 py-12">
    <section class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-600">Ошибка</p>
      <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ errorTitle }}</h1>
      <p class="mt-3 text-sm text-slate-600">{{ errorMessage }}</p>

      <NuxtLink
        to="/"
        class="mt-6 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
      >
        Вернуться на главную
      </NuxtLink>
    </section>
  </div>
</template>

<script setup lang="ts">
import type { NuxtError } from '#app';

const props = defineProps<{ error: NuxtError }>();

const errorTitle = computed(() => {
  if (props.error?.statusCode === 403) {
    return '403: Доступ запрещен';
  }

  return `${props.error?.statusCode ?? 500}: Ошибка`;
});

const errorMessage = computed(() => {
  if (props.error?.statusCode === 403) {
    return 'У вас нет доступа к этой странице.';
  }

  return props.error?.message || 'Произошла ошибка при открытии страницы.';
});
</script>
