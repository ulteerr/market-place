type RealtimeObservabilityEventName =
  | 'websocket_connect_ok'
  | 'websocket_connect_error'
  | 'websocket_subscribe_ok'
  | 'websocket_subscribe_error'
  | 'broadcast_dispatch_ok'
  | 'broadcast_dispatch_error';

export const useRealtimeObservability = () => {
  const api = useApi();

  const reportRealtimeEvent = async (
    event: RealtimeObservabilityEventName,
    status: 'ok' | 'error' = event.endsWith('_error') ? 'error' : 'ok',
    severity: 'info' | 'warning' | 'error' = status === 'ok' ? 'info' : 'warning',
    meta: Record<string, unknown> = {}
  ): Promise<void> => {
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
      // Observability reporting failures must never affect UI flow.
    }
  };

  return {
    reportRealtimeEvent,
  };
};
