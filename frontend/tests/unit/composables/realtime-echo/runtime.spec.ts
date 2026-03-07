import { describe, expect, it } from 'vitest';
import { createRealtimeEchoRuntime } from '~/composables/realtime-echo/runtime';

describe('realtime echo runtime', () => {
  it('initializes echo with reverb config and bearer auth', async () => {
    const states: string[] = [];
    const captured: Record<string, unknown>[] = [];
    const handlers: Record<string, ((payload?: unknown) => void) | undefined> = {};

    const runtime = createRealtimeEchoRuntime({
      config: {
        enabled: true,
        apiBase: 'http://localhost:8080',
        appKey: 'local-app-key',
        wsHost: 'localhost',
        wsPort: 8083,
        wssPort: 8083,
        forceTls: false,
        authEndpoint: '/broadcasting/auth',
        getAuthToken: () => 'token-123',
      },
      onStateChange: (state) => states.push(state),
      createEcho: async (options) => {
        captured.push(options);
        return {
          connector: {
            pusher: {
              connection: {
                bind: (event, callback) => {
                  handlers[event] = callback;
                },
              },
            },
          },
        };
      },
    });

    const instance = await runtime.connect();
    expect(instance).not.toBeNull();
    expect(captured).toHaveLength(1);
    expect(captured[0]).toMatchObject({
      broadcaster: 'reverb',
      key: 'local-app-key',
      wsHost: 'localhost',
      wsPort: 8083,
      wssPort: 8083,
      forceTLS: false,
      authEndpoint: 'http://localhost:8080/broadcasting/auth',
      auth: {
        headers: {
          Authorization: 'Bearer token-123',
        },
      },
    });

    handlers.connected?.();
    expect(states).toContain('connecting');
    expect(states).toContain('connected');
  });

  it('does not initialize echo when token is absent', async () => {
    let called = false;

    const runtime = createRealtimeEchoRuntime({
      config: {
        enabled: true,
        apiBase: 'http://localhost:8080',
        appKey: 'local-app-key',
        wsHost: 'localhost',
        wsPort: 8083,
        wssPort: 8083,
        forceTls: false,
        authEndpoint: '/broadcasting/auth',
        getAuthToken: () => null,
      },
      createEcho: async () => {
        called = true;
        return {};
      },
    });

    const instance = await runtime.connect();
    expect(instance).toBeNull();
    expect(called).toBe(false);
  });

  it('returns null and reports error when reverb is unavailable', async () => {
    const errors: unknown[] = [];

    const runtime = createRealtimeEchoRuntime({
      config: {
        enabled: true,
        apiBase: 'http://localhost:8080',
        appKey: 'local-app-key',
        wsHost: 'localhost',
        wsPort: 8083,
        wssPort: 8083,
        forceTls: false,
        authEndpoint: '/broadcasting/auth',
        getAuthToken: () => 'token-123',
      },
      onError: (error) => errors.push(error),
      createEcho: async () => {
        throw new Error('reverb offline');
      },
    });

    await expect(runtime.connect()).resolves.toBeNull();
    expect(errors).toHaveLength(1);
  });
});
