import { createRealtimeEchoRuntime } from '~/composables/realtime-echo/runtime';

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig();
  const { isAuthenticated, token } = useAuth();
  const { reportRealtimeEvent } = useRealtimeObservability();
  const connectionState = ref('disconnected');

  const reverbPort = Math.max(1, Number(config.public.reverbPort ?? 8083));
  const reverbScheme = String(config.public.reverbScheme ?? 'http').toLowerCase();
  const forceTls = reverbScheme === 'https';
  let lastConnectionState = '';
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
        void reportRealtimeEvent('websocket_connect_ok', 'ok', 'info');
        return;
      }

      if (state === 'error' || state === 'unavailable' || state === 'disconnected') {
        void reportRealtimeEvent('websocket_connect_error', 'error', 'warning', { state });
      }
    },
    onError: () => {
      void reportRealtimeEvent('websocket_connect_error', 'error', 'warning');
    },
  });

  const stopWatcher = watch(
    [isAuthenticated, token],
    async ([authenticated]) => {
      if (!authenticated) {
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
