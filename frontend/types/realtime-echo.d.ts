import type { RealtimeEchoRuntime } from '~/composables/realtime-echo/runtime';
import type { Ref } from 'vue';

declare module '#app' {
  interface NuxtApp {
    $realtimeEcho: RealtimeEchoRuntime;
    $realtimeEchoState: Ref<string>;
  }
}

declare module 'vue' {
  interface ComponentCustomProperties {
    $realtimeEcho: RealtimeEchoRuntime;
    $realtimeEchoState: Ref<string>;
  }
}

export {};
