import type { Meta, StoryObj } from '@storybook/vue3';
import MetricKpiGrid from './MetricKpiGrid.vue';

const items = [
  {
    title: 'Revenue',
    value: '$192.10k',
    deltaText: '32k increase ↗',
    trend: [
      { x: '2026-03-07', y: 10 },
      { x: '2026-03-08', y: 14 },
      { x: '2026-03-09', y: 19 },
    ],
    trendType: 'increase' as const,
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
    trendType: 'decrease' as const,
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
    trendType: 'increase' as const,
    accentColor: '#1f90ea',
    icon: '↗',
  },
];

const meta = {
  title: 'Admin/Metrics/MetricKpiGrid',
  component: MetricKpiGrid,
  tags: ['autodocs'],
  args: {
    items,
  },
  parameters: {
    layout: 'padded',
  },
} satisfies Meta<typeof MetricKpiGrid>;

export default meta;
type Story = StoryObj<typeof meta>;

export const ThreeCardsDesktop: Story = {};

export const TabletTwoColumns: Story = {
  parameters: {
    viewport: {
      defaultViewport: 'tablet',
    },
  },
};

export const MobileSingleColumn: Story = {
  parameters: {
    viewport: {
      defaultViewport: 'mobile1',
    },
  },
};
