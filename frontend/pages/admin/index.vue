<template>
  <section class="mx-auto w-full max-w-6xl admin-dashboard">
    <HomeUsersStats
      :total-users="usersStats.state.value.totalUsers"
      :online-users="usersStats.state.value.onlineUsers"
      :total-label="t('admin.dashboard.stats.totalUsers')"
      :online-label="t('admin.dashboard.stats.onlineUsers')"
      :online-hint="t('admin.dashboard.stats.onlineHint')"
      :loading="usersStats.loading.value"
      :error="usersStats.error.value"
      :loading-text="t('common.loading')"
      :error-text="t('admin.dashboard.stats.loadError')"
    />
  </section>
</template>

<script setup lang="ts">
import HomeUsersStats from '~/components/home/HomeUsersStats.vue';
import { createPresenceStatusRefreshController } from '~/composables/presence-status-refresh/runtime';
import { useRealtimeEcho } from '~/composables/realtime-echo/useRealtimeEcho';
import { useRealtimeEchoState } from '~/composables/realtime-echo/useRealtimeEchoState';
import { subscribeToUsersPresence } from '~/composables/realtime-presence/runtime';

const { t } = useI18n();
const { isAuthenticated, token } = useAuth();
const realtimeEchoState = useRealtimeEchoState();
const usersStats = useMainUsersStats();
const STATUS_REFRESH_INTERVAL_MS = 30_000;
let stopRealtimePresenceSubscription: (() => void) | null = null;
let realtimePresenceRetryTimer: ReturnType<typeof setTimeout> | null = null;
const pollingEnabled = ref(false);
const realtimePresenceSuppressed = ref(false);

definePageMeta({
  layout: 'admin',
});

const presenceStatusRefresh = createPresenceStatusRefreshController({
  intervalMs: STATUS_REFRESH_INTERVAL_MS,
  refresh: async () => {
    await usersStats.refresh();
  },
  isPageVisible: () => document.visibilityState === 'visible',
});

const setPollingEnabled = (enabled: boolean) => {
  if (pollingEnabled.value === enabled) {
    return;
  }

  pollingEnabled.value = enabled;
  if (enabled) {
    presenceStatusRefresh.start();
    return;
  }

  presenceStatusRefresh.stop();
};

const clearRealtimeRetryTimer = () => {
  if (realtimePresenceRetryTimer === null) {
    return;
  }

  clearTimeout(realtimePresenceRetryTimer);
  realtimePresenceRetryTimer = null;
};

const scheduleRealtimeRetry = () => {
  if (realtimePresenceRetryTimer !== null || stopRealtimePresenceSubscription) {
    return;
  }

  realtimePresenceRetryTimer = setTimeout(() => {
    realtimePresenceRetryTimer = null;
    void connectRealtimePresence();
  }, 2_000);
};

const connectRealtimePresence = async () => {
  if (
    !isAuthenticated.value ||
    !token.value ||
    stopRealtimePresenceSubscription ||
    realtimePresenceSuppressed.value
  ) {
    return;
  }

  const realtimeEcho = useRealtimeEcho();
  if (!realtimeEcho) {
    return;
  }

  const echo = await realtimeEcho.connect();
  if (!echo) {
    setPollingEnabled(true);
    scheduleRealtimeRetry();
    return;
  }

  clearRealtimeRetryTimer();
  stopRealtimePresenceSubscription = subscribeToUsersPresence(
    echo,
    () => {
      void usersStats.refresh();
    },
    {
      onSubscribed: () => {
        setPollingEnabled(false);
      },
      onError: () => {
        stopRealtimePresenceSubscription?.();
        stopRealtimePresenceSubscription = null;
        realtimePresenceSuppressed.value = true;
        setPollingEnabled(true);
        clearRealtimeRetryTimer();
      },
    }
  );
};

const handlePresenceVisibility = () => {
  void presenceStatusRefresh.handleVisibilityChange();
};

const handlePresenceWindowFocus = () => {
  void presenceStatusRefresh.handleWindowFocus();
};

onMounted(() => {
  void usersStats.refresh();
  document.addEventListener('visibilitychange', handlePresenceVisibility);
  window.addEventListener('focus', handlePresenceWindowFocus);
  setPollingEnabled(true);
  void connectRealtimePresence();
});

watch([isAuthenticated, token], ([authenticated, tokenValue]) => {
  if (!authenticated || !tokenValue) {
    realtimePresenceSuppressed.value = false;
    setPollingEnabled(false);
    clearRealtimeRetryTimer();
    stopRealtimePresenceSubscription?.();
    stopRealtimePresenceSubscription = null;
    return;
  }

  if (!stopRealtimePresenceSubscription) {
    setPollingEnabled(true);
    void connectRealtimePresence();
  }
});

if (realtimeEchoState) {
  watch(
    realtimeEchoState,
    (state) => {
      if (state === 'connected') {
        if (!stopRealtimePresenceSubscription) {
          void connectRealtimePresence();
        }
        return;
      }

      if (state === 'connecting') {
        return;
      }

      stopRealtimePresenceSubscription?.();
      stopRealtimePresenceSubscription = null;

      if (isAuthenticated.value && token.value) {
        setPollingEnabled(true);
        scheduleRealtimeRetry();
      }
    },
    { immediate: true }
  );
}

onBeforeUnmount(() => {
  realtimePresenceSuppressed.value = false;
  document.removeEventListener('visibilitychange', handlePresenceVisibility);
  window.removeEventListener('focus', handlePresenceWindowFocus);
  setPollingEnabled(false);
  clearRealtimeRetryTimer();
  stopRealtimePresenceSubscription?.();
  stopRealtimePresenceSubscription = null;
});
</script>

<style lang="scss" scoped src="./index.scss"></style>
