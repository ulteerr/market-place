import { describe, expect, it } from 'vitest';
import { createPresenceHeartbeatController } from '~/composables/presence-heartbeat/runtime';

interface ScheduledTask {
  id: number;
  runAt: number;
  callback: () => void;
}

const createFakeScheduler = () => {
  let now = 0;
  let nextId = 1;
  const tasks = new Map<number, ScheduledTask>();

  const setTimeoutFn = (callback: () => void, delayMs: number): number => {
    const id = nextId++;
    tasks.set(id, {
      id,
      runAt: now + delayMs,
      callback,
    });

    return id;
  };

  const clearTimeoutFn = (id: number): void => {
    tasks.delete(id);
  };

  const advanceBy = (ms: number): void => {
    now += ms;

    while (true) {
      const dueTask = [...tasks.values()]
        .filter((task) => task.runAt <= now)
        .sort((left, right) => left.runAt - right.runAt)[0];

      if (!dueTask) {
        break;
      }

      tasks.delete(dueTask.id);
      dueTask.callback();
    }
  };

  const pendingCount = (): number => tasks.size;

  return {
    setTimeoutFn,
    clearTimeoutFn,
    advanceBy,
    pendingCount,
  };
};

const flushMicrotasks = async (): Promise<void> => {
  await Promise.resolve();
  await Promise.resolve();
};

describe('presence heartbeat runtime', () => {
  it('pauses on hidden tab and resumes on visible tab', async () => {
    const scheduler = createFakeScheduler();
    const calls: string[] = [];
    let visible = true;
    let onVisibilityChange: (() => void) | null = null;

    const controller = createPresenceHeartbeatController({
      baseIntervalMs: 1000,
      maxBackoffMs: 8000,
      pauseWhenHidden: true,
      isAuthenticated: () => true,
      isDocumentVisible: () => visible,
      sendHeartbeat: async () => {
        calls.push('beat');
      },
      subscribeVisibilityChange: (handler) => {
        onVisibilityChange = handler;
        return () => {
          onVisibilityChange = null;
        };
      },
      setTimeoutFn: scheduler.setTimeoutFn as unknown as (
        callback: () => void,
        delayMs: number
      ) => ReturnType<typeof setTimeout>,
      clearTimeoutFn: scheduler.clearTimeoutFn as unknown as (
        handle: ReturnType<typeof setTimeout>
      ) => void,
    });

    controller.start();
    await flushMicrotasks();
    expect(calls).toHaveLength(1);

    scheduler.advanceBy(1000);
    await flushMicrotasks();
    expect(calls).toHaveLength(2);

    visible = false;
    onVisibilityChange?.();
    expect(scheduler.pendingCount()).toBe(0);

    scheduler.advanceBy(5000);
    await flushMicrotasks();
    expect(calls).toHaveLength(2);

    visible = true;
    onVisibilityChange?.();
    await flushMicrotasks();
    expect(calls).toHaveLength(3);

    scheduler.advanceBy(1000);
    await flushMicrotasks();
    expect(calls).toHaveLength(4);

    controller.destroy();
  });

  it('retries with backoff on network failures and resets on success', async () => {
    const scheduler = createFakeScheduler();
    const calls: string[] = [];
    const errors: number[] = [];
    let attempt = 0;

    const controller = createPresenceHeartbeatController({
      baseIntervalMs: 1000,
      maxBackoffMs: 8000,
      pauseWhenHidden: true,
      isAuthenticated: () => true,
      isDocumentVisible: () => true,
      sendHeartbeat: async () => {
        attempt += 1;
        calls.push(`attempt-${attempt}`);

        if (attempt <= 2) {
          throw new Error('network');
        }
      },
      subscribeVisibilityChange: () => () => {},
      onError: (_error, failureCount) => {
        errors.push(failureCount);
      },
      setTimeoutFn: scheduler.setTimeoutFn as unknown as (
        callback: () => void,
        delayMs: number
      ) => ReturnType<typeof setTimeout>,
      clearTimeoutFn: scheduler.clearTimeoutFn as unknown as (
        handle: ReturnType<typeof setTimeout>
      ) => void,
    });

    controller.start();
    await flushMicrotasks();
    expect(calls).toEqual(['attempt-1']);
    expect(errors).toEqual([1]);

    scheduler.advanceBy(1000);
    await flushMicrotasks();
    expect(calls).toEqual(['attempt-1']);

    scheduler.advanceBy(1000);
    await flushMicrotasks();
    expect(calls).toEqual(['attempt-1', 'attempt-2']);
    expect(errors).toEqual([1, 2]);

    scheduler.advanceBy(3000);
    await flushMicrotasks();
    expect(calls).toEqual(['attempt-1', 'attempt-2']);

    scheduler.advanceBy(1000);
    await flushMicrotasks();
    expect(calls).toEqual(['attempt-1', 'attempt-2', 'attempt-3']);

    scheduler.advanceBy(1000);
    await flushMicrotasks();
    expect(calls).toEqual(['attempt-1', 'attempt-2', 'attempt-3', 'attempt-4']);

    controller.destroy();
  });
});
