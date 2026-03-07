// @vitest-environment jsdom
import { mount } from '@vue/test-utils';
import { computed } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import MetricKpiCard from '~/components/admin/Metrics/MetricKpiCard/MetricKpiCard.vue';

describe('MetricKpiCard', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('useId', () => 'test-kpi');
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('renders title, value and delta', () => {
    const wrapper = mount(MetricKpiCard, {
      props: {
        title: 'Revenue',
        value: '$192.10k',
        deltaText: '32k increase ↗',
        trendType: 'increase',
        accentColor: '#11d498',
        trend: [
          { x: '1', y: 10 },
          { x: '2', y: 14 },
          { x: '3', y: 19 },
        ],
      },
    });

    expect(wrapper.text()).toContain('Revenue');
    expect(wrapper.get('[data-test="metric-kpi-value"]').text()).toBe('$192.10k');
    expect(wrapper.get('[data-test="metric-kpi-delta"]').text()).toContain('32k increase');
  });

  it('applies trendType class and renders sparkline', () => {
    const wrapper = mount(MetricKpiCard, {
      props: {
        title: 'Expenses',
        value: '$45.5k',
        deltaText: '12k decrease ↘',
        trendType: 'decrease',
        accentColor: '#ef4444',
        trend: [
          { x: '1', y: 30 },
          { x: '2', y: 25 },
          { x: '3', y: 20 },
        ],
      },
    });

    expect(wrapper.classes()).toContain('is-decrease');
    expect(wrapper.find('[data-test="metric-kpi-line"]').exists()).toBeTruthy();
    expect(wrapper.find('[data-test="metric-kpi-area"]').exists()).toBeTruthy();
  });

  it('shows empty state for insufficient trend points', () => {
    const wrapper = mount(MetricKpiCard, {
      props: {
        title: 'Posts',
        value: 200,
        deltaText: '0 stable',
        trendType: 'neutral',
        trend: [{ x: '1', y: 20 }],
      },
    });

    expect(wrapper.find('[data-test="metric-kpi-line"]').exists()).toBeFalsy();
    expect(wrapper.get('[data-test="metric-kpi-empty"]').text()).toContain('Нет данных');
  });
});
