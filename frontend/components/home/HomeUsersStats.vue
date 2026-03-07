<template>
  <section class="home-users-stats grid gap-4 md:grid-cols-2" data-test="home-users-stats">
    <article
      class="admin-panel home-users-stats__card rounded-2xl p-5"
      data-test="home-users-total"
    >
      <p class="home-users-stats__label">{{ totalLabel }}</p>
      <p class="home-users-stats__value">{{ loading ? loadingText : formatValue(totalUsers) }}</p>
      <p v-if="!loading && error" class="home-users-stats__error">{{ errorText }}</p>
    </article>

    <article
      class="admin-panel home-users-stats__card rounded-2xl p-5"
      data-test="home-users-online"
    >
      <p class="home-users-stats__label">{{ onlineLabel }}</p>
      <p class="home-users-stats__value">{{ loading ? loadingText : formatValue(onlineUsers) }}</p>
      <p v-if="!loading && !error" class="home-users-stats__hint">{{ onlineHint }}</p>
      <p v-else-if="!loading && error" class="home-users-stats__error">{{ errorText }}</p>
    </article>
  </section>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    totalUsers: number;
    onlineUsers: number;
    totalLabel: string;
    onlineLabel: string;
    onlineHint?: string;
    loading?: boolean;
    error?: string;
    loadingText?: string;
    errorText?: string;
  }>(),
  {
    onlineHint: '',
    loading: false,
    error: '',
    loadingText: '...',
    errorText: 'Не удалось загрузить',
  }
);

const formatValue = (value: number): string => new Intl.NumberFormat('en-US').format(value);

const totalUsers = computed(() => props.totalUsers);
const onlineUsers = computed(() => props.onlineUsers);
const totalLabel = computed(() => props.totalLabel);
const onlineLabel = computed(() => props.onlineLabel);
const onlineHint = computed(() => props.onlineHint);
const loading = computed(() => props.loading);
const error = computed(() => props.error);
const loadingText = computed(() => props.loadingText);
const errorText = computed(() => props.errorText);
</script>

<style lang="scss" src="./HomeUsersStats.scss"></style>
