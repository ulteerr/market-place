import type { Meta, StoryObj } from '@storybook/vue3';
import MetricLineChartCard from './MetricLineChartCard.vue';

const meta = {
  title: 'Admin/Metrics/MetricLineChartCard',
  component: MetricLineChartCard,
  tags: ['autodocs'],
  parameters: {
    layout: 'padded',
    localeArgs: {
      ru: {
        title: 'Заказы',
        yLabel: 'Заказы',
      },
      en: {
        title: 'Orders',
        yLabel: 'Orders',
      },
    },
  },
  args: {
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
    gridSteps: 4,
  },
} satisfies Meta<typeof MetricLineChartCard>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default3Series: Story = {};

export const TwoSeries: Story = {
  args: {
    series: [
      {
        name: 'Выручка 1',
        color: '#1f90ea',
        points: [
          { x: '2026-03-07', y: 220 },
          { x: '2026-03-08', y: 240 },
          { x: '2026-03-09', y: 290 },
        ],
      },
      {
        name: 'Выручка 2',
        color: '#11d498',
        points: [
          { x: '2026-03-07', y: 120 },
          { x: '2026-03-08', y: 200 },
          { x: '2026-03-09', y: 240 },
        ],
      },
    ],
  },
};

export const HighValues: Story = {
  args: {
    series: [
      {
        name: 'Выручка 1',
        color: '#1f90ea',
        points: [
          { x: '2026-03-07', y: 1000 },
          { x: '2026-03-08', y: 4200 },
          { x: '2026-03-09', y: 8100 },
        ],
      },
      {
        name: 'Выручка 2',
        color: '#11d498',
        points: [
          { x: '2026-03-07', y: 800 },
          { x: '2026-03-08', y: 3900 },
          { x: '2026-03-09', y: 6200 },
        ],
      },
    ],
  },
};

export const Mobile: Story = {
  parameters: {
    viewport: {
      defaultViewport: 'mobile1',
    },
  },
};
