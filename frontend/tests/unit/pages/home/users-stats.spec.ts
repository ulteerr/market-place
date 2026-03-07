// @vitest-environment jsdom
import { mount } from '@vue/test-utils';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AdminHomePage from '~/pages/admin/index.vue';

vi.mock('~/composables/realtime-echo/useRealtimeEchoState', () => ({
  useRealtimeEchoState: () => null,
}));

vi.mock('~/composables/realtime-echo/useRealtimeEcho', () => ({
  useRealtimeEcho: () => null,
}));

vi.mock('~/composables/realtime-presence/runtime', () => ({
  subscribeToUsersPresence: () => () => undefined,
}));

describe('admin home users stats', () => {
  beforeEach(() => {
    vi.unstubAllGlobals();
    vi.resetAllMocks();
    vi.stubGlobal('ref', ref);
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('watch', watch);
    vi.stubGlobal('onMounted', onMounted);
    vi.stubGlobal('onBeforeUnmount', onBeforeUnmount);
    vi.stubGlobal('definePageMeta', vi.fn());
    vi.stubGlobal('useI18n', () => ({
      t: (key: string) => key,
    }));
    vi.stubGlobal('useAuth', () => ({
      isAuthenticated: ref(false),
      token: ref(null),
    }));
  });

  it('renders total users and online users values', async () => {
    const refresh = vi.fn().mockResolvedValue(undefined);

    vi.stubGlobal('useMainUsersStats', () => ({
      state: ref({ totalUsers: 1200, onlineUsers: 87, updatedAt: null }),
      loading: ref(false),
      error: ref(''),
      refresh,
    }));

    const wrapper = mount(AdminHomePage);
    await Promise.resolve();

    expect(refresh).toHaveBeenCalledTimes(1);
    expect(wrapper.get('[data-test="home-users-total"]').text()).toContain('1,200');
    expect(wrapper.get('[data-test="home-users-online"]').text()).toContain('87');
  });

  it('shows loading state and fallback error text', async () => {
    const loadingStats = {
      state: ref({ totalUsers: 0, onlineUsers: 0, updatedAt: null }),
      loading: ref(true),
      error: ref(''),
      refresh: vi.fn().mockResolvedValue(undefined),
    };

    vi.stubGlobal('useMainUsersStats', () => loadingStats);
    const wrapperLoading = mount(AdminHomePage);
    await wrapperLoading.vm.$nextTick();
    expect(wrapperLoading.text()).toContain('common.loading');

    const errorStats = {
      state: ref({ totalUsers: 0, onlineUsers: 0, updatedAt: null }),
      loading: ref(false),
      error: ref('load_error'),
      refresh: vi.fn().mockResolvedValue(undefined),
    };

    vi.stubGlobal('useMainUsersStats', () => errorStats);
    const wrapperError = mount(AdminHomePage);
    await wrapperError.vm.$nextTick();

    expect(wrapperError.text()).toContain('admin.dashboard.stats.loadError');
  });
});
