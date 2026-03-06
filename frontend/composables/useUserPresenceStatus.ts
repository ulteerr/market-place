import { useLastSeen } from '~/composables/useLastSeen';

type TranslateFn = (key: string, params?: Record<string, string | number>) => string;

export const buildUserPresenceStatusLabel = (params: {
  isOnline?: boolean | null;
  lastSeenAt?: string | null;
  t: TranslateFn;
  formatLastSeen: (value: Date | string | number | null | undefined) => string;
}): string => {
  if (params.isOnline === true) {
    return params.t('admin.users.presence.online');
  }

  if (params.lastSeenAt) {
    return params.t('admin.users.presence.lastSeen', {
      value: params.formatLastSeen(params.lastSeenAt),
    });
  }

  return params.t('common.dash');
};

export const useUserPresenceStatus = () => {
  const { formatLastSeen } = useLastSeen();
  const { t } = useI18n();

  const formatPresenceStatus = (isOnline?: boolean | null, lastSeenAt?: string | null): string =>
    buildUserPresenceStatusLabel({
      isOnline,
      lastSeenAt,
      t,
      formatLastSeen,
    });

  return {
    formatPresenceStatus,
  };
};
