import type { RealtimeEchoRuntime } from '~/composables/realtime-echo/runtime';

export const useRealtimeEcho = (): RealtimeEchoRuntime | null => {
  const nuxtApp = useNuxtApp();

  return nuxtApp.$realtimeEcho ?? null;
};
