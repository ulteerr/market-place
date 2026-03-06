import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createPresenceStatusRefreshController } from '~/composables/presence-status-refresh/runtime';

describe('presence status refresh runtime', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.clearAllTimers();
    vi.useRealTimers();
  });

  it('refreshes on interval only when page is visible', async () => {
    let isVisible = true;
    const refresh = vi.fn(async () => undefined);

    const controller = createPresenceStatusRefreshController({
      intervalMs: 30_000,
      refresh,
      isPageVisible: () => isVisible,
    });

    controller.start();

    await vi.advanceTimersByTimeAsync(30_000);
    expect(refresh).toHaveBeenCalledTimes(1);

    isVisible = false;
    await vi.advanceTimersByTimeAsync(30_000);
    expect(refresh).toHaveBeenCalledTimes(1);

    controller.stop();
  });

  it('refreshes immediately on focus and visible state change', async () => {
    const refresh = vi.fn(async () => undefined);

    const controller = createPresenceStatusRefreshController({
      intervalMs: 30_000,
      refresh,
      isPageVisible: () => true,
    });

    await controller.handleWindowFocus();
    await controller.handleVisibilityChange();

    expect(refresh).toHaveBeenCalledTimes(2);
  });
});
