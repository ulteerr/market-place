type RealtimeObservabilityEventName =
  | 'websocket_connect_ok'
  | 'websocket_connect_error'
  | 'websocket_subscribe_ok'
  | 'websocket_subscribe_error'
  | 'settings_realtime_fallback_enabled'
  | 'settings_realtime_fallback_disabled'
  | 'broadcast_dispatch_ok'
  | 'broadcast_dispatch_error';

const REALTIME_OBSERVABILITY_DEDUPE_WINDOW_MS = 10_000;
const realtimeObservabilitySentAt = new Map<string, number>();

export const useRealtimeObservability = () => {
  const api = useApi();
  const route = useRoute();

  const isObservabilityRoute = computed(
    () =>
      route.path.startsWith('/admin') ||
      route.path.startsWith('/account') ||
      route.path.startsWith('/organizations')
  );

  const reportRealtimeEvent = async (
    event: RealtimeObservabilityEventName,
    status: 'ok' | 'error' = event.endsWith('_error') ? 'error' : 'ok',
    severity: 'info' | 'warning' | 'error' = status === 'ok' ? 'info' : 'warning',
    meta: Record<string, unknown> = {}
  ): Promise<void> => {
    if (!isObservabilityRoute.value) {
      return;
    }

    const dedupeKey = JSON.stringify({
      event,
      status,
      severity,
      meta,
    });
    const now = Date.now();
    const previousSentAt = realtimeObservabilitySentAt.get(dedupeKey) ?? 0;

    if (now - previousSentAt < REALTIME_OBSERVABILITY_DEDUPE_WINDOW_MS) {
      return;
    }

    realtimeObservabilitySentAt.set(dedupeKey, now);

    try {
      await api('/api/admin/observability/realtime-event', {
        method: 'POST',
        body: {
          event,
          status,
          severity,
          meta,
        },
      });
    } catch {
      realtimeObservabilitySentAt.delete(dedupeKey);
      // Observability reporting failures must never affect UI flow.
    }
  };

  return {
    reportRealtimeEvent,
  };
};
