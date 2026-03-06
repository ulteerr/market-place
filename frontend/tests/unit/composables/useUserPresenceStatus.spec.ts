import { describe, expect, it, vi } from 'vitest';
import { buildUserPresenceStatusLabel } from '~/composables/useUserPresenceStatus';

describe('buildUserPresenceStatusLabel', () => {
  it('returns online label when user is online', () => {
    const t = vi.fn((key: string) => (key === 'admin.users.presence.online' ? 'Онлайн' : key));
    const formatLastSeen = vi.fn(() => '5 минут назад');

    const result = buildUserPresenceStatusLabel({
      isOnline: true,
      lastSeenAt: '2026-03-07T12:00:00.000Z',
      t,
      formatLastSeen,
    });

    expect(result).toBe('Онлайн');
    expect(formatLastSeen).not.toHaveBeenCalled();
  });

  it('returns last seen label when user is offline and has timestamp', () => {
    const t = vi.fn((key: string, params?: Record<string, string | number>) => {
      if (key === 'admin.users.presence.lastSeen') {
        return `Был в сети ${String(params?.value ?? '')}`;
      }

      return key;
    });
    const formatLastSeen = vi.fn(() => '5 минут назад');

    const result = buildUserPresenceStatusLabel({
      isOnline: false,
      lastSeenAt: '2026-03-07T12:00:00.000Z',
      t,
      formatLastSeen,
    });

    expect(result).toBe('Был в сети 5 минут назад');
    expect(formatLastSeen).toHaveBeenCalledTimes(1);
  });
});
