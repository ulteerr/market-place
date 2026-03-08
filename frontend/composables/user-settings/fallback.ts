type TimerHandle = ReturnType<typeof setTimeout>;

export interface SettingsFallbackPollingController {
  start: () => void;
  stop: () => void;
  handleVisibilityChange: () => Promise<void>;
  handleWindowFocus: () => Promise<void>;
}

interface SettingsFallbackPollingOptions {
  baseIntervalMs: number;
  maxBackoffMs: number;
  isPageVisible: () => boolean;
  refresh: () => Promise<void>;
  setTimeoutFn?: (callback: () => void, delayMs: number) => TimerHandle;
  clearTimeoutFn?: (handle: TimerHandle) => void;
}

export const createSettingsFallbackPollingController = (
  options: SettingsFallbackPollingOptions
): SettingsFallbackPollingController => {
  const setTimeoutFn = options.setTimeoutFn ?? setTimeout;
  const clearTimeoutFn = options.clearTimeoutFn ?? clearTimeout;
  const baseIntervalMs = Math.max(1_000, options.baseIntervalMs);
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

    if (!options.isPageVisible()) {
      scheduleNext();
      return;
    }

    try {
      await options.refresh();
      failureCount = 0;
    } catch {
      failureCount += 1;
    }

    if (!started) {
      return;
    }

    scheduleNext();
  };

  const handleVisibilityChange = async (): Promise<void> => {
    if (!started || !options.isPageVisible()) {
      return;
    }

    clearTimer();
    await tick();
  };

  const handleWindowFocus = async (): Promise<void> => {
    if (!started || !options.isPageVisible()) {
      return;
    }

    clearTimer();
    await tick();
  };

  return {
    start(): void {
      if (started) {
        return;
      }

      started = true;
      void tick();
    },

    stop(): void {
      started = false;
      failureCount = 0;
      clearTimer();
    },

    handleVisibilityChange,
    handleWindowFocus,
  };
};
