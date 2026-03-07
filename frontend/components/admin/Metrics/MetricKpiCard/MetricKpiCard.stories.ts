import type { Meta, StoryObj } from '@storybook/vue3';
import MetricKpiCard from './MetricKpiCard.vue';

const baseTrend = [
  { x: '2026-03-07', y: 10 },
  { x: '2026-03-08', y: 14 },
  { x: '2026-03-09', y: 19 },
];

const meta = {
  title: 'Admin/Metrics/MetricKpiCard',
  component: MetricKpiCard,
  tags: ['autodocs'],
  parameters: {
    layout: 'padded',
  },
  args: {
    title: 'Revenue',
    value: '$192.10k',
    deltaText: '32k increase ↗',
    trend: baseTrend,
    trendType: 'increase',
    accentColor: '#11d498',
    icon: '↗',
  },
} satisfies Meta<typeof MetricKpiCard>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Increase: Story = {};

export const Decrease: Story = {
  args: {
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
};

export const Neutral: Story = {
  args: {
    title: 'Posts',
    value: 200,
    deltaText: '0 stable',
    trend: [
      { x: '2026-03-07', y: 20 },
      { x: '2026-03-08', y: 20 },
      { x: '2026-03-09', y: 20 },
    ],
    trendType: 'neutral',
    accentColor: '#1f90ea',
    icon: '•',
  },
};
