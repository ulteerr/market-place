type EchoLike = {
  disconnect?: () => void;
  leaveAllChannels?: () => void;
  connector?: {
    pusher?: {
      connection?: {
        bind?: (event: string, callback: (payload?: unknown) => void) => void;
      };
    };
  };
};

export interface RealtimeEchoConfig {
  enabled: boolean;
  apiBase: string;
  appKey: string;
  wsHost: string;
  wsPort: number;
  wssPort: number;
  forceTls: boolean;
  authEndpoint: string;
  getAuthToken: () => string | null | undefined;
}

export interface RealtimeEchoRuntimeOptions {
  config: RealtimeEchoConfig;
  onStateChange?: (state: string) => void;
  onError?: (error: unknown) => void;
  createEcho?: (options: Record<string, unknown>) => Promise<EchoLike>;
}

export interface RealtimeEchoRuntime {
  connect: () => Promise<EchoLike | null>;
  disconnect: () => void;
  refreshConnection: () => Promise<EchoLike | null>;
  getInstance: () => EchoLike | null;
}

const normalizePath = (value: string): string => {
  const trimmed = value.trim();
  if (!trimmed) {
    return '/broadcasting/auth';
  }

  return trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
};

const defaultCreateEcho = async (options: Record<string, unknown>): Promise<EchoLike> => {
  const [{ default: Echo }, { default: Pusher }] = await Promise.all([
    import('laravel-echo'),
    import('pusher-js'),
  ]);

  (globalThis as { Pusher?: unknown }).Pusher = Pusher;

  return new Echo(options) as EchoLike;
};

export const createRealtimeEchoRuntime = (
  options: RealtimeEchoRuntimeOptions
): RealtimeEchoRuntime => {
  const createEcho = options.createEcho ?? defaultCreateEcho;
  const { config } = options;

  let instance: EchoLike | null = null;
  let activeToken: string | null = null;

  const notifyState = (state: string): void => {
    options.onStateChange?.(state);
  };

  const bindConnectionEvents = (echo: EchoLike): void => {
    const connection = echo.connector?.pusher?.connection;
    if (!connection?.bind) {
      return;
    }

    connection.bind('connected', () => notifyState('connected'));
    connection.bind('disconnected', () => notifyState('disconnected'));
    connection.bind('unavailable', () => notifyState('unavailable'));
    connection.bind('error', (payload?: unknown) => {
      notifyState('error');
      options.onError?.(payload);
    });
  };

  const disconnect = (): void => {
    if (!instance) {
      return;
    }

    try {
      instance.leaveAllChannels?.();
      instance.disconnect?.();
    } catch (error) {
      options.onError?.(error);
    } finally {
      instance = null;
      activeToken = null;
      notifyState('disconnected');
    }
  };

  const connect = async (): Promise<EchoLike | null> => {
    if (!config.enabled) {
      return null;
    }

    const token = config.getAuthToken();
    if (!token) {
      disconnect();
      return null;
    }

    if (instance && activeToken === token) {
      return instance;
    }

    disconnect();
    notifyState('connecting');

    try {
      const nextInstance = await createEcho({
        broadcaster: 'reverb',
        key: config.appKey,
        wsHost: config.wsHost,
        wsPort: config.wsPort,
        wssPort: config.wssPort,
        forceTLS: config.forceTls,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: `${config.apiBase}${normalizePath(config.authEndpoint)}`,
        auth: {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      });

      instance = nextInstance;
      activeToken = token;
      bindConnectionEvents(nextInstance);
      notifyState('connected');

      return nextInstance;
    } catch (error) {
      options.onError?.(error);
      notifyState('error');
      instance = null;
      activeToken = null;
      return null;
    }
  };

  return {
    connect,
    disconnect,
    refreshConnection: async (): Promise<EchoLike | null> => {
      disconnect();
      return connect();
    },
    getInstance: (): EchoLike | null => instance,
  };
};
