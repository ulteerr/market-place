import { createPresenceHeartbeatController } from '~/composables/presence-heartbeat/runtime';

export default defineNuxtPlugin(() => {
  const { isAuthenticated } = useAuth();
  const config = useRuntimeConfig();

  const controller = createPresenceHeartbeatController({
    baseIntervalMs:
      Math.max(1, Number(config.public.presenceHeartbeatIntervalSeconds ?? 30)) * 1000,
    maxBackoffMs:
      Math.max(1, Number(config.public.presenceHeartbeatMaxBackoffSeconds ?? 300)) * 1000,
    pauseWhenHidden: config.public.presenceHeartbeatPauseWhenHidden !== false,
    isAuthenticated: () => isAuthenticated.value,
    isDocumentVisible: () => document.visibilityState === 'visible',
    subscribeVisibilityChange: (handler) => {
      document.addEventListener('visibilitychange', handler);

      return () => document.removeEventListener('visibilitychange', handler);
    },
    sendHeartbeat: async () => {
      const api = useApi();
      await api('/api/presence/heartbeat', {
        method: 'POST',
      });
    },
    onError: () => {
      // Keep UI silent on transient heartbeat errors.
    },
  });

  const stopWatcher = watch(
    isAuthenticated,
    (value) => {
      if (value) {
        controller.start();
        return;
      }

      controller.stop();
    },
    { immediate: true }
  );

  if (typeof window !== 'undefined') {
    window.addEventListener(
      'beforeunload',
      () => {
        stopWatcher();
        controller.destroy();
      },
      { once: true }
    );
  }
});
