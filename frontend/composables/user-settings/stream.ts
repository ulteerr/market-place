import { SETTINGS_STREAM_RECONNECT_DELAY } from '~/composables/user-settings/constants';
import { sleep } from '~/composables/user-settings/runtime';
import type { UserSettings } from '~/composables/user-settings/types';

interface SettingsStreamControllerOptions {
  streamUrl: string;
  getIsAuthenticated: () => boolean;
  getToken: () => string | null;
  onSettings: (settings: Partial<UserSettings>) => void;
}

const parseSettingsEvent = (rawMessage: string): Partial<UserSettings> | null => {
  const lines = rawMessage
    .split('\n')
    .map((line) => line.trimEnd())
    .filter((line) => line.length > 0);

  if (lines.length === 0) {
    return null;
  }

  let eventName = 'message';
  const dataChunks: string[] = [];

  for (const line of lines) {
    if (line.startsWith('event:')) {
      eventName = line.slice(6).trim();
      continue;
    }

    if (line.startsWith('data:')) {
      dataChunks.push(line.slice(5).trim());
    }
  }

  if (eventName !== 'settings' || dataChunks.length === 0) {
    return null;
  }

  try {
    const payload = JSON.parse(dataChunks.join('\n')) as { settings?: Partial<UserSettings> };
    return payload.settings ?? null;
  } catch {
    return null;
  }
};

export const createSettingsStreamController = (options: SettingsStreamControllerOptions) => {
  let controller: AbortController | null = null;
  let streamPromise: Promise<void> | null = null;
  let streamEnabled = false;

  const connect = async (): Promise<void> => {
    while (streamEnabled && options.getIsAuthenticated() && options.getToken()) {
      controller = new AbortController();

      try {
        const response = await fetch(options.streamUrl, {
          method: 'GET',
          credentials: 'include',
          cache: 'no-store',
          headers: {
            Accept: 'text/event-stream',
            Authorization: `Bearer ${options.getToken()}`,
          },
          signal: controller.signal,
        });

        if (!response.ok || !response.body) {
          throw new Error('Settings stream is unavailable');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (streamEnabled && options.getIsAuthenticated()) {
          const { done, value } = await reader.read();

          if (done) {
            break;
          }

          buffer += decoder.decode(value, { stream: true });
          const messages = buffer.split('\n\n');
          buffer = messages.pop() ?? '';

          for (const message of messages) {
            const nextSettings = parseSettingsEvent(message);
            if (nextSettings) {
              options.onSettings(nextSettings);
            }
          }
        }
      } catch {
        // reconnect below
      } finally {
        if (controller) {
          controller.abort();
          controller = null;
        }
      }

      if (streamEnabled && options.getIsAuthenticated() && options.getToken()) {
        await sleep(SETTINGS_STREAM_RECONNECT_DELAY);
      }
    }
  };

  const start = () => {
    if (!process.client || !options.getIsAuthenticated() || !options.getToken() || streamPromise) {
      return;
    }

    streamEnabled = true;
    streamPromise = connect().finally(() => {
      streamPromise = null;
    });
  };

  const stop = () => {
    streamEnabled = false;

    if (controller) {
      controller.abort();
      controller = null;
    }
  };

  return {
    start,
    stop,
  };
};
