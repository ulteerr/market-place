import { describe, expect, it } from 'vitest';
import {
  normalizeMeSettingsRealtimePayload,
  subscribeToMeSettingsRealtime,
} from '~/composables/user-settings/realtime';

describe('user settings realtime runtime', () => {
  it('normalizes valid payload and rejects invalid payload', () => {
    expect(
      normalizeMeSettingsRealtimePayload({
        user_id: 'u-1',
        settings: { theme: 'dark' },
        updated_at: '2026-03-08T00:00:00Z',
        version: 123,
      })
    ).toEqual({
      user_id: 'u-1',
      settings: { theme: 'dark' },
      updated_at: '2026-03-08T00:00:00Z',
      version: 123,
    });

    expect(normalizeMeSettingsRealtimePayload({ user_id: 'u-1' })).toBeNull();
    expect(normalizeMeSettingsRealtimePayload({ settings: { theme: 'dark' } })).toBeNull();
    expect(normalizeMeSettingsRealtimePayload(null)).toBeNull();
  });

  it('subscribes to me-settings channel and unsubscribes listener', () => {
    const eventHandlers = new Map<string, (payload: unknown) => void>();
    const stopEvents: string[] = [];
    let leftChannel: string | null = null;
    const payloads: unknown[] = [];

    const channel = {
      listen: (event: string, callback: (payload: unknown) => void) => {
        eventHandlers.set(event, callback);
        return channel;
      },
      stopListening: (event: string) => {
        stopEvents.push(event);
        return channel;
      },
    };

    const echo = {
      private: () => channel,
      leave: (channelName: string) => {
        leftChannel = channelName;
      },
    };

    const unsubscribe = subscribeToMeSettingsRealtime(echo, 'u-7', (payload) => {
      payloads.push(payload);
    });

    eventHandlers.get('.me.settings.updated')?.({
      user_id: 'u-7',
      settings: { theme: 'light' },
    });

    expect(payloads).toEqual([
      {
        user_id: 'u-7',
        settings: { theme: 'light' },
      },
    ]);

    unsubscribe();
    expect(stopEvents).toEqual(['.me.settings.updated']);
    expect(leftChannel).toBe('me-settings.u-7');
  });

  it('handles channel subscribed and error callbacks', () => {
    let subscribed = false;
    const errors: unknown[] = [];

    const channel = {
      listen: () => channel,
      stopListening: () => channel,
      subscribed: (callback: () => void) => {
        callback();
        return channel;
      },
      error: (callback: (error: unknown) => void) => {
        callback({ type: 'subscription_error' });
        return channel;
      },
    };

    const echo = {
      private: () => channel,
      leave: () => undefined,
    };

    const unsubscribe = subscribeToMeSettingsRealtime(echo, 'u-1', () => undefined, {
      onSubscribed: () => {
        subscribed = true;
      },
      onError: (error) => {
        errors.push(error);
      },
    });

    expect(subscribed).toBe(true);
    expect(errors).toEqual([{ type: 'subscription_error' }]);
    unsubscribe();
  });
});
