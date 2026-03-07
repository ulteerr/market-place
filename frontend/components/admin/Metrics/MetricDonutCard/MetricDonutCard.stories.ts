import type { Meta, StoryObj } from '@storybook/vue3';
import MetricDonutCard from './MetricDonutCard.vue';

const meta = {
  title: 'Admin/Metrics/MetricDonutCard',
  component: MetricDonutCard,
  tags: ['autodocs'],
  parameters: {
    layout: 'padded',
    localeArgs: {
      ru: {
        title: 'Подписчики',
        totalLabel: 'Итого',
      },
      en: {
        title: 'Subscribers',
        totalLabel: 'Total',
      },
    },
  },
  args: {
    title: 'Подписчики',
    totalLabel: 'Итого',
    totalValue: 19999,
    segments: [
      { label: 'Web', value: 10000, color: '#1f90ea' },
      { label: 'Apple', value: 9999, color: '#11d498' },
    ],
  },
} satisfies Meta<typeof MetricDonutCard>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const ManySegments: Story = {
  args: {
    totalValue: 33120,
    segments: [
      { label: 'Web', value: 11020, color: '#1f90ea' },
      { label: 'Apple', value: 10210, color: '#11d498' },
      { label: 'Android', value: 6850, color: '#f59e0b' },
      { label: 'Desktop', value: 5040, color: '#ef4444' },
    ],
  },
};

export const EmptyState: Story = {
  args: {
    totalValue: 0,
    segments: [],
  },
};

export const Mobile: Story = {
  args: {
    height: 380,
  },
  parameters: {
    viewport: {
      defaultViewport: 'mobile1',
    },
  },
};
