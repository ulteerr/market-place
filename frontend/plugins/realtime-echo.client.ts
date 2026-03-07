import { createRealtimeEchoRuntime } from '~/composables/realtime-echo/runtime';

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig();
  const { isAuthenticated, token } = useAuth();
  const { reportRealtimeEvent } = useRealtimeObservability();
  const connectionState = ref('disconnected');
  const CONNECT_ERROR_STABILITY_MS = 5_000;

  const reverbPort = Math.max(1, Number(config.public.reverbPort ?? 8083));
  const reverbScheme = String(config.public.reverbScheme ?? 'http').toLowerCase();
  const forceTls = reverbScheme === 'https';
  let lastConnectionState = '';
  let pendingConnectErrorState: string | null = null;
  let pendingConnectErrorTimer: ReturnType<typeof setTimeout> | null = null;

  const clearPendingConnectError = () => {
    if (pendingConnectErrorTimer !== null) {
      clearTimeout(pendingConnectErrorTimer);
      pendingConnectErrorTimer = null;
    }
    pendingConnectErrorState = null;
  };

  const scheduleConnectErrorReport = (state: string) => {
    if (!isAuthenticated.value) {
      return;
    }

    pendingConnectErrorState = state;
    if (pendingConnectErrorTimer !== null) {
      return;
    }

    pendingConnectErrorTimer = setTimeout(() => {
      pendingConnectErrorTimer = null;
      if (!isAuthenticated.value) {
        pendingConnectErrorState = null;
        return;
      }

      if (connectionState.value === 'connected' || connectionState.value === 'connecting') {
        pendingConnectErrorState = null;
        return;
      }

      void reportRealtimeEvent('websocket_connect_error', 'error', 'warning', {
        state: pendingConnectErrorState ?? connectionState.value,
      });
      pendingConnectErrorState = null;
    }, CONNECT_ERROR_STABILITY_MS);
  };

  const runtime = createRealtimeEchoRuntime({
    config: {
      enabled: Boolean(config.public.reverbEnabled) && Boolean(config.public.reverbAppKey),
      apiBase: String(config.public.apiBase ?? ''),
      appKey: String(config.public.reverbAppKey ?? ''),
      wsHost: String(config.public.reverbHost ?? 'localhost'),
      wsPort: reverbPort,
      wssPort: reverbPort,
      forceTls,
      authEndpoint: String(config.public.reverbAuthEndpoint ?? '/broadcasting/auth'),
      getAuthToken: () => token.value,
    },
    onStateChange: (state) => {
      connectionState.value = state;
      if (state === lastConnectionState) {
        return;
      }

      lastConnectionState = state;
      if (state === 'connected') {
        clearPendingConnectError();
        void reportRealtimeEvent('websocket_connect_ok', 'ok', 'info');
        return;
      }

      if (state === 'error' || state === 'unavailable' || state === 'disconnected') {
        scheduleConnectErrorReport(state);
      }
    },
    onError: () => {
      scheduleConnectErrorReport('error');
    },
  });

  const stopWatcher = watch(
    [isAuthenticated, token],
    async ([authenticated]) => {
      if (!authenticated) {
        clearPendingConnectError();
        runtime.disconnect();
        return;
      }

      await runtime.connect();
    },
    { immediate: true }
  );

  if (typeof window !== 'undefined') {
    window.addEventListener(
      'beforeunload',
      () => {
        clearPendingConnectError();
        stopWatcher();
        runtime.disconnect();
      },
      { once: true }
    );
  }

  return {
    provide: {
      realtimeEcho: runtime,
      realtimeEchoState: readonly(connectionState),
    },
  };
});
