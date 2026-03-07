// @vitest-environment jsdom
import { mount } from '@vue/test-utils';
import { computed } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import MetricDonutCard from '~/components/admin/Metrics/MetricDonutCard/MetricDonutCard.vue';

describe('MetricDonutCard', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.stubGlobal('computed', computed);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('renders title, total and legend by input props', () => {
    const wrapper = mount(MetricDonutCard, {
      props: {
        title: 'Подписчики',
        totalLabel: 'Total',
        totalValue: 19999,
        segments: [
          { label: 'Web', value: 10000, color: '#1f90ea' },
          { label: 'Apple', value: 9999, color: '#11d498' },
        ],
      },
    });

    expect(wrapper.text()).toContain('Подписчики');
    expect(wrapper.get('[data-test="metric-donut-total"]').text()).toBe('19,999');

    const legendItems = wrapper.findAll('[data-test="metric-donut-legend-item"]');
    expect(legendItems).toHaveLength(2);
    expect(legendItems[0]?.text()).toContain('Web');
    expect(legendItems[1]?.text()).toContain('Apple');
  });

  it('calculates total value from segments when totalValue is omitted', () => {
    const wrapper = mount(MetricDonutCard, {
      props: {
        segments: [
          { label: 'A', value: 10, color: '#1f90ea' },
          { label: 'B', value: 15, color: '#11d498' },
          { label: 'C', value: 5, color: '#f59e0b' },
        ],
      },
    });

    expect(wrapper.get('[data-test="metric-donut-total"]').text()).toBe('30');
    expect(wrapper.html()).not.toContain('NaN');
  });

  it('handles empty or zero-value segments', () => {
    const wrapper = mount(MetricDonutCard, {
      props: {
        totalValue: 0,
        segments: [
          { label: 'A', value: 0, color: '#1f90ea' },
          { label: 'B', value: -10, color: '#11d498' },
        ],
      },
    });

    expect(wrapper.get('[data-test="metric-donut-total"]').text()).toBe('0');
    expect(wrapper.findAll('[data-test="metric-donut-legend-item"]')).toHaveLength(0);
    expect(wrapper.get('[data-test="metric-donut-legend-empty"]').text()).toContain('Нет данных');
  });

  it('shows tooltip and updates center values on hover', async () => {
    const wrapper = mount(MetricDonutCard, {
      props: {
        totalLabel: 'Total',
        totalValue: 19999,
        segments: [
          { label: 'Web', value: 10000, color: '#1f90ea' },
          { label: 'Apple', value: 9999, color: '#11d498' },
        ],
      },
    });

    const firstLegendItem = wrapper.findAll('[data-test="metric-donut-legend-item"]')[0]!;
    await firstLegendItem.trigger('mouseenter');

    expect(wrapper.get('[data-test="metric-donut-tooltip"]').text()).toContain('Web:');
    expect(wrapper.get('[data-test="metric-donut-total"]').text()).toBe('10,000');

    await firstLegendItem.trigger('mouseleave');
    expect(wrapper.find('[data-test="metric-donut-tooltip"]').exists()).toBeFalsy();
    expect(wrapper.get('[data-test="metric-donut-total"]').text()).toBe('19,999');
  });
});
