import { describe, expect, it } from 'vitest';
import type { AdminUser } from '~/composables/useAdminUsers';
import {
  applyPresencePatchToUser,
  applyPresencePatchToUsers,
  normalizePresencePayload,
  subscribeToUsersPresence,
} from '~/composables/realtime-presence/runtime';

describe('realtime presence runtime', () => {
  it('normalizes valid payload and rejects invalid payload', () => {
    expect(
      normalizePresencePayload({
        user_id: 'u-1',
        is_online: true,
        last_seen_at: '2026-03-07T00:00:00Z',
      })
    ).toEqual({
      user_id: 'u-1',
      is_online: true,
      last_seen_at: '2026-03-07T00:00:00Z',
    });

    expect(normalizePresencePayload({})).toBeNull();
    expect(normalizePresencePayload(null)).toBeNull();
  });

  it('applies presence patch for list and single user', () => {
    const users: AdminUser[] = [
      { id: 'u-1', email: 'u1@test.dev', first_name: 'A', last_name: 'B', is_online: false },
      { id: 'u-2', email: 'u2@test.dev', first_name: 'C', last_name: 'D', is_online: false },
    ];

    const nextList = applyPresencePatchToUsers(users, {
      user_id: 'u-2',
      is_online: true,
      last_seen_at: '2026-03-07T00:01:00Z',
    });

    expect(nextList[0].is_online).toBe(false);
    expect(nextList[1].is_online).toBe(true);
    expect(nextList[1].last_seen_at).toBe('2026-03-07T00:01:00Z');

    const single = applyPresencePatchToUser(users[0], {
      user_id: 'u-1',
      is_online: true,
    });
    expect(single?.is_online).toBe(true);
  });

  it('subscribes to users presence channel and unsubscribes listeners', () => {
    const eventHandlers = new Map<string, (payload: unknown) => void>();
    const stopEvents: string[] = [];
    let leftChannel: string | null = null;
    const payloads: unknown[] = [];

    const echo = {
      private: () => ({
        listen: (event: string, callback: (payload: unknown) => void) => {
          eventHandlers.set(event, callback);
          return {
            listen: () => undefined as never,
          } as never;
        },
        stopListening: (event: string) => {
          stopEvents.push(event);
          return {
            stopListening: () => undefined as never,
          } as never;
        },
      }),
      leave: (channel: string) => {
        leftChannel = channel;
      },
    };

    const unsubscribe = subscribeToUsersPresence(echo, (payload) => {
      payloads.push(payload);
    });

    eventHandlers.get('.users.online')?.({ user_id: 'u-7', is_online: true });
    eventHandlers.get('.users.offline')?.({ user_id: 'u-7', is_online: false });

    expect(payloads).toHaveLength(2);

    unsubscribe();
    expect(stopEvents).toEqual(['.users.online', '.users.offline', '.users.last_seen_updated']);
    expect(leftChannel).toBe('users.presence');
  });

  it('handles channel subscribed and error callbacks', () => {
    let subscribed = false;
    const errors: unknown[] = [];
    const callbacks = new Map<string, (payload?: unknown) => void>();

    const channel = {
      listen: (event: string, callback: (payload: unknown) => void) => {
        callbacks.set(event, callback);
        return channel;
      },
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

    const unsubscribe = subscribeToUsersPresence(echo, () => undefined, {
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
