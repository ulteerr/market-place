export interface PresenceStatusRefreshController {
  start: () => void;
  stop: () => void;
  handleVisibilityChange: () => Promise<void>;
  handleWindowFocus: () => Promise<void>;
}

interface PresenceStatusRefreshOptions {
  intervalMs: number;
  refresh: () => Promise<void> | void;
  isPageVisible: () => boolean;
  setIntervalFn?: (handler: () => void, timeout: number) => ReturnType<typeof setInterval>;
  clearIntervalFn?: (id: ReturnType<typeof setInterval>) => void;
}

export const createPresenceStatusRefreshController = (
  options: PresenceStatusRefreshOptions
): PresenceStatusRefreshController => {
  const setIntervalFn = options.setIntervalFn ?? setInterval;
  const clearIntervalFn = options.clearIntervalFn ?? clearInterval;
  let intervalId: ReturnType<typeof setInterval> | null = null;

  const refreshIfVisible = async () => {
    if (!options.isPageVisible()) {
      return;
    }

    await options.refresh();
  };

  const start = () => {
    if (intervalId !== null) {
      return;
    }

    intervalId = setIntervalFn(
      () => {
        void refreshIfVisible();
      },
      Math.max(1_000, options.intervalMs)
    );
  };

  const stop = () => {
    if (intervalId === null) {
      return;
    }

    clearIntervalFn(intervalId);
    intervalId = null;
  };

  const handleVisibilityChange = async () => {
    if (options.isPageVisible()) {
      await options.refresh();
    }
  };

  const handleWindowFocus = async () => {
    await refreshIfVisible();
  };

  return {
    start,
    stop,
    handleVisibilityChange,
    handleWindowFocus,
  };
};
