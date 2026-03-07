// @vitest-environment jsdom
import { mount } from '@vue/test-utils';
import { computed } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import MetricKpiGrid from '~/components/admin/Metrics/MetricKpiGrid/MetricKpiGrid.vue';

describe('MetricKpiGrid', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('useId', () => 'test-kpi-grid');
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('renders correct amount of cards', () => {
    const wrapper = mount(MetricKpiGrid, {
      props: {
        items: [
          {
            title: 'Revenue',
            value: '$192.10k',
            deltaText: '32k increase ↗',
            trendType: 'increase',
            accentColor: '#11d498',
            icon: '↗',
            trend: [
              { x: '1', y: 10 },
              { x: '2', y: 14 },
              { x: '3', y: 19 },
            ],
          },
          {
            title: 'Expenses',
            value: '$45.5k',
            deltaText: '12k decrease ↘',
            trendType: 'decrease',
            accentColor: '#ef4444',
            icon: '↘',
            trend: [
              { x: '1', y: 30 },
              { x: '2', y: 25 },
              { x: '3', y: 20 },
            ],
          },
          {
            title: 'Posts',
            value: 200,
            deltaText: '2 increase ↗',
            trendType: 'increase',
            accentColor: '#1f90ea',
            icon: '•',
            trend: [
              { x: '1', y: 8 },
              { x: '2', y: 10 },
              { x: '3', y: 13 },
            ],
          },
        ],
      },
    });

    expect(wrapper.findAll('[data-test="metric-kpi-card"]')).toHaveLength(3);
  });

  it('keeps responsive grid utility classes', () => {
    const wrapper = mount(MetricKpiGrid, {
      props: {
        items: [],
      },
    });

    const classList = wrapper.get('[data-test="metric-kpi-grid"]').classes();
    expect(classList).toContain('grid-cols-1');
    expect(classList).toContain('md:grid-cols-2');
    expect(classList).toContain('xl:grid-cols-3');
  });
});
