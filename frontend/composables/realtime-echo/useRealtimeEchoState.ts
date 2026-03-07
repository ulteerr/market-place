import type { Ref } from 'vue';

export const useRealtimeEchoState = (): Ref<string> | null => {
  const nuxtApp = useNuxtApp();

  return nuxtApp.$realtimeEchoState ?? null;
};
