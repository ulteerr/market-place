import { computed } from 'vue';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { usePrivatePreviewState } from '~/composables/layout/usePrivatePreviewState';

describe('usePrivatePreviewState', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  const resolveState = (state: unknown) => {
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('useRoute', () => ({
      query: {
        state,
      },
    }));

    return usePrivatePreviewState().value;
  };

  it('returns ready by default', () => {
    expect(resolveState(undefined)).toBe('ready');
  });

  it('accepts supported preview states', () => {
    expect(resolveState('loading')).toBe('loading');
    expect(resolveState('empty')).toBe('empty');
    expect(resolveState('error')).toBe('error');
  });

  it('normalizes unsupported values to ready', () => {
    expect(resolveState('broken')).toBe('ready');
    expect(resolveState(123)).toBe('ready');
  });
});
