import type { Meta, StoryObj } from '@storybook/vue3';
import MetricDashboardSection from './MetricDashboardSection.vue';

const meta = {
  title: 'Admin/Metrics/MetricDashboardSection',
  component: MetricDashboardSection,
  tags: ['autodocs'],
  parameters: {
    layout: 'padded',
  },
  args: {
    donutData: {
      title: 'Подписчики',
      totalLabel: 'Total',
      totalValue: 19999,
      height: 420,
      segments: [
        { label: 'Web', value: 10000, color: '#1f90ea' },
        { label: 'Apple', value: 9999, color: '#11d498' },
      ],
    },
    lineData: {
      title: 'Заказы',
      yLabel: 'Заказы',
      xLabels: ['2026-03-07', '2026-03-08', '2026-03-09'],
      gridSteps: 4,
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
        {
          name: 'Выручка 3',
          color: '#f59e0b',
          points: [
            { x: '2026-03-07', y: 400 },
            { x: '2026-03-08', y: 500 },
            { x: '2026-03-09', y: 300 },
          ],
        },
      ],
    },
    kpiItems: [
      {
        title: 'Revenue',
        value: '$192.10k',
        deltaText: '32k increase ↗',
        trend: [
          { x: '2026-03-07', y: 10 },
          { x: '2026-03-08', y: 14 },
          { x: '2026-03-09', y: 19 },
        ],
        trendType: 'increase',
        accentColor: '#11d498',
        icon: '↗',
      },
      {
        title: 'Expenses',
        value: '$45.5k',
        deltaText: '12k decrease ↘',
        trend: [
          { x: '2026-03-07', y: 40 },
          { x: '2026-03-08', y: 34 },
          { x: '2026-03-09', y: 22 },
        ],
        trendType: 'decrease',
        accentColor: '#ef4444',
        icon: '↘',
      },
      {
        title: 'Posts',
        value: 200,
        deltaText: '2 increase ↗',
        trend: [
          { x: '2026-03-07', y: 8 },
          { x: '2026-03-08', y: 10 },
          { x: '2026-03-09', y: 13 },
        ],
        trendType: 'increase',
        accentColor: '#1f90ea',
        icon: '↗',
      },
    ],
  },
} satisfies Meta<typeof MetricDashboardSection>;

export default meta;
type Story = StoryObj<typeof meta>;

export const DefaultDashboard: Story = {};

export const CompactMobile: Story = {
  parameters: {
    viewport: {
      defaultViewport: 'mobile1',
    },
  },
};
