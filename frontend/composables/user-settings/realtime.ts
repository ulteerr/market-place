import type { UserSettings } from '~/composables/user-settings/types';

export interface MeSettingsRealtimePayload {
  user_id: string;
  settings: Partial<UserSettings>;
  updated_at?: string | null;
  version?: number | string;
}

interface EchoPrivateChannelLike {
  listen: (
    event: string,
    callback: (payload: MeSettingsRealtimePayload) => void
  ) => EchoPrivateChannelLike;
  stopListening?: (event: string) => EchoPrivateChannelLike;
  subscribed?: (callback: () => void) => EchoPrivateChannelLike;
  error?: (callback: (error: unknown) => void) => EchoPrivateChannelLike;
}

interface EchoLike {
  private: (channel: string) => EchoPrivateChannelLike;
  leave?: (channel: string) => void;
}

const EVENT_NAME = '.me.settings.updated';

export const normalizeMeSettingsRealtimePayload = (
  value: unknown
): MeSettingsRealtimePayload | null => {
  if (!value || typeof value !== 'object') {
    return null;
  }

  const candidate = value as Partial<MeSettingsRealtimePayload>;
  if (!candidate.user_id || typeof candidate.user_id !== 'string') {
    return null;
  }

  if (
    !candidate.settings ||
    typeof candidate.settings !== 'object' ||
    Array.isArray(candidate.settings)
  ) {
    return null;
  }

  const payload: MeSettingsRealtimePayload = {
    user_id: candidate.user_id,
    settings: candidate.settings as Partial<UserSettings>,
  };

  if (candidate.updated_at === null || typeof candidate.updated_at === 'string') {
    payload.updated_at = candidate.updated_at;
  }

  if (typeof candidate.version === 'number' || typeof candidate.version === 'string') {
    payload.version = candidate.version;
  }

  return payload;
};

export const subscribeToMeSettingsRealtime = (
  echo: EchoLike,
  userId: string,
  onPayload: (payload: MeSettingsRealtimePayload) => void,
  options?: {
    onSubscribed?: () => void;
    onError?: (error: unknown) => void;
  }
): (() => void) => {
  const channelName = `me-settings.${userId}`;
  const channel = echo.private(channelName);
  const listener = (raw: MeSettingsRealtimePayload) => {
    const payload = normalizeMeSettingsRealtimePayload(raw);
    if (!payload) {
      return;
    }

    onPayload(payload);
  };

  channel.listen(EVENT_NAME, listener);
  channel.subscribed?.(() => options?.onSubscribed?.());
  channel.error?.((error: unknown) => options?.onError?.(error));

  return () => {
    channel.stopListening?.(EVENT_NAME);
    echo.leave?.(channelName);
  };
};
