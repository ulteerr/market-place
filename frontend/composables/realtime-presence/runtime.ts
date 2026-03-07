import type { AdminUser } from '~/composables/useAdminUsers';

export interface PresenceEventPayload {
  user_id: string;
  is_online?: boolean;
  last_seen_at?: string | null;
}

interface EchoPrivateChannelLike {
  listen: (
    event: string,
    callback: (payload: PresenceEventPayload) => void
  ) => EchoPrivateChannelLike;
  stopListening?: (event: string) => EchoPrivateChannelLike;
  subscribed?: (callback: () => void) => EchoPrivateChannelLike;
  error?: (callback: (error: unknown) => void) => EchoPrivateChannelLike;
}

interface EchoLike {
  private: (channel: string) => EchoPrivateChannelLike;
  leave?: (channel: string) => void;
}

const EVENT_NAMES = ['.users.online', '.users.offline', '.users.last_seen_updated'] as const;
const CHANNEL_NAME = 'users.presence';

export const normalizePresencePayload = (value: unknown): PresenceEventPayload | null => {
  if (!value || typeof value !== 'object') {
    return null;
  }

  const candidate = value as Partial<PresenceEventPayload>;
  if (!candidate.user_id || typeof candidate.user_id !== 'string') {
    return null;
  }

  const payload: PresenceEventPayload = {
    user_id: candidate.user_id,
  };

  if (typeof candidate.is_online === 'boolean') {
    payload.is_online = candidate.is_online;
  }

  if (candidate.last_seen_at === null || typeof candidate.last_seen_at === 'string') {
    payload.last_seen_at = candidate.last_seen_at;
  }

  return payload;
};

export const applyPresencePatchToUser = (
  user: AdminUser | null,
  payload: PresenceEventPayload
): AdminUser | null => {
  if (!user || user.id !== payload.user_id) {
    return user;
  }

  return {
    ...user,
    is_online: payload.is_online ?? user.is_online,
    last_seen_at:
      payload.last_seen_at !== undefined ? payload.last_seen_at : (user.last_seen_at ?? null),
  };
};

export const applyPresencePatchToUsers = (
  users: AdminUser[],
  payload: PresenceEventPayload
): AdminUser[] => {
  let changed = false;
  const nextUsers = users.map((user) => {
    const nextUser = applyPresencePatchToUser(user, payload);
    if (nextUser !== user) {
      changed = true;
    }

    return nextUser ?? user;
  });

  return changed ? nextUsers : users;
};

export const subscribeToUsersPresence = (
  echo: EchoLike,
  onPayload: (payload: PresenceEventPayload) => void,
  options?: {
    onSubscribed?: () => void;
    onError?: (error: unknown) => void;
  }
): (() => void) => {
  const channel = echo.private(CHANNEL_NAME);
  const listener = (raw: PresenceEventPayload) => {
    const payload = normalizePresencePayload(raw);
    if (!payload) {
      return;
    }

    onPayload(payload);
  };

  EVENT_NAMES.forEach((eventName) => {
    channel.listen(eventName, listener);
  });
  channel.subscribed?.(() => options?.onSubscribed?.());
  channel.error?.((error: unknown) => options?.onError?.(error));

  return () => {
    EVENT_NAMES.forEach((eventName) => {
      channel.stopListening?.(eventName);
    });
    echo.leave?.(CHANNEL_NAME);
  };
};
