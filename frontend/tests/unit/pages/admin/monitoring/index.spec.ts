// @vitest-environment jsdom
import { mount } from '@vue/test-utils';
import { computed, defineComponent, onMounted, ref, watch } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import MonitoringPage from '~/pages/admin/monitoring/index.vue';

describe('admin monitoring page', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.stubGlobal('ref', ref);
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('watch', watch);
    vi.stubGlobal('onMounted', onMounted);
  });

  it('renders aggregated metrics and incidents from observability payload', async () => {
    const getDashboard = vi.fn().mockResolvedValue({
      summary: {
        updated_at: '2026-03-06T12:00:00.000Z',
        domains: {
          presence: {
            events_total: 2,
            errors_total: 1,
            duration_total_ms: 80,
            duration_count: 2,
            events: { heartbeat: { ok: 1, error: 1 } },
            last_event_at: '2026-03-06T12:00:00.000Z',
          },
          auth: {
            events_total: 1,
            errors_total: 0,
            duration_total_ms: 20,
            duration_count: 1,
            events: { login: { ok: 1 } },
            last_event_at: '2026-03-06T11:59:00.000Z',
          },
        },
      },
      incidents: [
        {
          timestamp: '2026-03-06T12:00:00.000Z',
          domain: 'presence',
          component: 'presence.service',
          event: 'redis_operation',
          severity: 'warning',
          status: 'error',
          duration_ms: null,
          request_id: null,
          meta: {},
        },
      ],
      alerts: [],
    });

    vi.stubGlobal('definePageMeta', vi.fn());
    vi.stubGlobal('useI18n', () => ({
      t: (key: string) => key,
      locale: ref('en'),
    }));
    vi.stubGlobal('useAdminObservability', () => ({
      getDashboard,
    }));

    const wrapper = mount(MonitoringPage, {
      global: {
        stubs: {
          UiSelect: defineComponent({
            props: ['modelValue', 'options'],
            emits: ['update:model-value'],
            template: '<div data-test="ui-select"></div>',
          }),
          UiInput: defineComponent({
            props: ['modelValue'],
            emits: ['update:model-value'],
            template: '<div data-test="ui-input"></div>',
          }),
        },
      },
    });

    await Promise.resolve();
    await Promise.resolve();

    expect(getDashboard).toHaveBeenCalledTimes(1);
    expect(wrapper.text()).toContain('3');
    expect(wrapper.text()).toContain('1');
    expect(wrapper.text()).toContain('33');
    expect(wrapper.text()).toContain('presence.service');
    expect(wrapper.text()).toContain('redis_operation');
  });
});
