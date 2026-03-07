import { useLastSeen } from '~/composables/useLastSeen';

type TranslateFn = (key: string, params?: Record<string, string | number>) => string;

export const buildUserPresenceStatusLabel = (params: {
  isOnline?: boolean | null;
  lastSeenAt?: string | null;
  t: TranslateFn;
  formatLastSeen: (value: Date | string | number | null | undefined, nowDate?: Date) => string;
  nowMs?: number;
}): string => {
  const nowMs = params.nowMs ?? Date.now();
  if (params.isOnline === true) {
    return params.t('admin.users.presence.online');
  }

  if (params.lastSeenAt) {
    return params.t('admin.users.presence.lastSeen', {
      value: params.formatLastSeen(params.lastSeenAt, new Date(nowMs)),
    });
  }

  return params.t('common.dash');
};

export const useUserPresenceStatus = () => {
  const { formatLastSeen } = useLastSeen();
  const { t } = useI18n();
  const nowTickMs = ref(Date.now());
  let nowTimerId: ReturnType<typeof setInterval> | null = null;

  if (import.meta.client) {
    onMounted(() => {
      if (nowTimerId !== null) {
        return;
      }

      nowTimerId = setInterval(() => {
        nowTickMs.value = Date.now();
      }, 30_000);
    });

    onBeforeUnmount(() => {
      if (nowTimerId === null) {
        return;
      }

      clearInterval(nowTimerId);
      nowTimerId = null;
    });
  }

  const formatPresenceStatus = (isOnline?: boolean | null, lastSeenAt?: string | null): string =>
    buildUserPresenceStatusLabel({
      isOnline,
      lastSeenAt,
      t,
      formatLastSeen,
      nowMs: nowTickMs.value,
    });

  return {
    formatPresenceStatus,
  };
};
