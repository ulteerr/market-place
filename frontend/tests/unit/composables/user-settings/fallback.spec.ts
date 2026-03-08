import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createSettingsFallbackPollingController } from '~/composables/user-settings/fallback';

describe('user settings fallback polling runtime', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.clearAllTimers();
    vi.useRealTimers();
  });

  it('polls only when visible and refreshes on focus/visibility', async () => {
    let isVisible = true;
    const refresh = vi.fn(async () => undefined);

    const controller = createSettingsFallbackPollingController({
      baseIntervalMs: 10_000,
      maxBackoffMs: 60_000,
      isPageVisible: () => isVisible,
      refresh,
    });

    controller.start();
    expect(refresh).toHaveBeenCalledTimes(1);

    await vi.advanceTimersByTimeAsync(10_000);
    expect(refresh).toHaveBeenCalledTimes(2);

    isVisible = false;
    await vi.advanceTimersByTimeAsync(10_000);
    expect(refresh).toHaveBeenCalledTimes(2);

    isVisible = true;
    await controller.handleWindowFocus();
    await controller.handleVisibilityChange();
    expect(refresh).toHaveBeenCalledTimes(4);

    controller.stop();
  });

  it('applies backoff on failures and resets interval after success', async () => {
    const refresh = vi
      .fn<() => Promise<void>>()
      .mockRejectedValueOnce(new Error('network-1'))
      .mockRejectedValueOnce(new Error('network-2'))
      .mockResolvedValue(undefined);

    const controller = createSettingsFallbackPollingController({
      baseIntervalMs: 1_000,
      maxBackoffMs: 4_000,
      isPageVisible: () => true,
      refresh,
    });

    controller.start();
    expect(refresh).toHaveBeenCalledTimes(1);

    await vi.advanceTimersByTimeAsync(1_000);
    expect(refresh).toHaveBeenCalledTimes(1);

    await vi.advanceTimersByTimeAsync(1_000);
    expect(refresh).toHaveBeenCalledTimes(2);

    await vi.advanceTimersByTimeAsync(4_000);
    expect(refresh).toHaveBeenCalledTimes(3);

    await vi.advanceTimersByTimeAsync(1_000);
    expect(refresh).toHaveBeenCalledTimes(4);

    controller.stop();
  });
});
