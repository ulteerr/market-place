// @vitest-environment jsdom
import { mount } from '@vue/test-utils';
import { computed } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import MetricLineChartCard from '~/components/admin/Metrics/MetricLineChartCard/MetricLineChartCard.vue';

describe('MetricLineChartCard', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.stubGlobal('computed', computed);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('renders N series lines and legend items', () => {
    const wrapper = mount(MetricLineChartCard, {
      props: {
        title: 'Заказы',
        yLabel: 'Заказы',
        xLabels: ['2026-03-07', '2026-03-08', '2026-03-09'],
        series: [
          {
            name: 'Выручка 1',
            color: '#1f90ea',
            points: [
              { x: '2026-03-07', y: 100 },
              { x: '2026-03-08', y: 200 },
              { x: '2026-03-09', y: 500 },
            ],
          },
          {
            name: 'Выручка 2',
            color: '#11d498',
            points: [
              { x: '2026-03-07', y: 300 },
              { x: '2026-03-08', y: 400 },
              { x: '2026-03-09', y: 300 },
            ],
          },
        ],
      },
    });

    expect(wrapper.text()).toContain('Заказы');
    expect(wrapper.findAll('[data-test="metric-line-path"]')).toHaveLength(2);
    expect(wrapper.findAll('.metric-line-card__legend-item')).toHaveLength(2);
  });

  it('matches legend and path colors', () => {
    const wrapper = mount(MetricLineChartCard, {
      props: {
        xLabels: ['A', 'B', 'C'],
        series: [
          {
            name: 'Series A',
            color: '#123456',
            points: [
              { x: 'A', y: 1 },
              { x: 'B', y: 2 },
              { x: 'C', y: 3 },
            ],
          },
        ],
      },
    });

    const path = wrapper.get('[data-test="metric-line-path"]');
    expect(path.attributes('stroke')).toBe('#123456');

    const legendColor = wrapper.get('.metric-line-card__legend-color');
    expect(legendColor.attributes('style')).toContain('rgb(18, 52, 86)');
  });

  it('renders x-axis labels and fallback on empty series', async () => {
    const wrapper = mount(MetricLineChartCard, {
      props: {
        xLabels: ['2026-03-07', '2026-03-08', '2026-03-09'],
        series: [],
      },
    });

    expect(wrapper.get('[data-test="metric-line-empty"]').text()).toContain('Нет данных');
    expect(wrapper.findAll('[data-test="metric-line-path"]')).toHaveLength(0);

    await wrapper.setProps({
      series: [
        {
          name: 'Выручка 1',
          color: '#1f90ea',
          points: [
            { x: '2026-03-07', y: 100 },
            { x: '2026-03-08', y: 200 },
            { x: '2026-03-09', y: 500 },
          ],
        },
      ],
    });

    const labels = wrapper.get('[data-test="metric-line-x-labels"]').text();
    expect(labels).toContain('2026-03-07');
    expect(labels).toContain('2026-03-08');
    expect(labels).toContain('2026-03-09');
  });

  it('shows tooltip and hover markers when x label is hovered', async () => {
    const wrapper = mount(MetricLineChartCard, {
      props: {
        xLabels: ['2026-03-07', '2026-03-08', '2026-03-09'],
        series: [
          {
            name: 'Выручка 1',
            color: '#1f90ea',
            points: [
              { x: '2026-03-07', y: 100 },
              { x: '2026-03-08', y: 200 },
              { x: '2026-03-09', y: 500 },
            ],
          },
          {
            name: 'Выручка 2',
            color: '#11d498',
            points: [
              { x: '2026-03-07', y: 300 },
              { x: '2026-03-08', y: 400 },
              { x: '2026-03-09', y: 300 },
            ],
          },
        ],
      },
    });

    expect(wrapper.find('[data-test="metric-line-tooltip"]').exists()).toBeFalsy();

    const firstLabel = wrapper.findAll('[data-test="metric-line-x-labels"] li')[0]!;
    await firstLabel.trigger('mouseenter');

    expect(wrapper.get('[data-test="metric-line-tooltip"]').text()).toContain('2026-03-07');
    expect(wrapper.get('[data-test="metric-line-tooltip"]').text()).toContain('Выручка 1:');
    expect(wrapper.findAll('[data-test="metric-line-marker"]')).toHaveLength(2);
    expect(wrapper.find('[data-test="metric-line-crosshair"]').exists()).toBeTruthy();
  });
});
