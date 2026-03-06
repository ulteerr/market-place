type TimerHandle = ReturnType<typeof setTimeout>;

export interface PresenceHeartbeatControllerOptions {
  baseIntervalMs: number;
  maxBackoffMs: number;
  pauseWhenHidden: boolean;
  isAuthenticated: () => boolean;
  isDocumentVisible: () => boolean;
  sendHeartbeat: () => Promise<void>;
  subscribeVisibilityChange: (handler: () => void) => () => void;
  onError?: (error: unknown, failureCount: number) => void;
  setTimeoutFn?: (callback: () => void, delayMs: number) => TimerHandle;
  clearTimeoutFn?: (handle: TimerHandle) => void;
}

export interface PresenceHeartbeatController {
  start: () => void;
  stop: () => void;
  destroy: () => void;
}

export const createPresenceHeartbeatController = (
  options: PresenceHeartbeatControllerOptions
): PresenceHeartbeatController => {
  const setTimeoutFn = options.setTimeoutFn ?? setTimeout;
  const clearTimeoutFn = options.clearTimeoutFn ?? clearTimeout;
  const baseIntervalMs = Math.max(1000, options.baseIntervalMs);
  const maxBackoffMs = Math.max(baseIntervalMs, options.maxBackoffMs);

  let started = false;
  let timer: TimerHandle | null = null;
  let failureCount = 0;

  const clearTimer = (): void => {
    if (!timer) {
      return;
    }

    clearTimeoutFn(timer);
    timer = null;
  };

  const nextDelayMs = (): number => {
    if (failureCount <= 0) {
      return baseIntervalMs;
    }

    return Math.min(baseIntervalMs * 2 ** failureCount, maxBackoffMs);
  };

  const scheduleNext = (): void => {
    clearTimer();
    timer = setTimeoutFn(() => {
      void tick();
    }, nextDelayMs());
  };

  const tick = async (): Promise<void> => {
    if (!started) {
      return;
    }

    if (!options.isAuthenticated()) {
      return;
    }

    if (options.pauseWhenHidden && !options.isDocumentVisible()) {
      clearTimer();
      return;
    }

    try {
      await options.sendHeartbeat();
      failureCount = 0;
    } catch (error) {
      failureCount += 1;
      options.onError?.(error, failureCount);
    }

    if (!started || !options.isAuthenticated()) {
      return;
    }

    if (options.pauseWhenHidden && !options.isDocumentVisible()) {
      clearTimer();
      return;
    }

    scheduleNext();
  };

  const onVisibilityChange = (): void => {
    if (!started || !options.isAuthenticated()) {
      return;
    }

    if (options.pauseWhenHidden && !options.isDocumentVisible()) {
      clearTimer();
      return;
    }

    clearTimer();
    void tick();
  };

  const unsubscribeVisibilityChange = options.subscribeVisibilityChange(onVisibilityChange);

  return {
    start(): void {
      if (started) {
        return;
      }

      started = true;
      if (!options.isAuthenticated()) {
        return;
      }

      if (options.pauseWhenHidden && !options.isDocumentVisible()) {
        return;
      }

      void tick();
    },

    stop(): void {
      started = false;
      failureCount = 0;
      clearTimer();
    },

    destroy(): void {
      this.stop();
      unsubscribeVisibilityChange();
    },
  };
};
