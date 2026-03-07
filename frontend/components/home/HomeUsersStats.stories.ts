import type { Meta, StoryObj } from '@storybook/vue3';
import HomeUsersStats from './HomeUsersStats.vue';

const meta = {
  title: 'Home/HomeUsersStats',
  component: HomeUsersStats,
  tags: ['autodocs'],
  parameters: {
    layout: 'padded',
  },
  args: {
    totalUsers: 10245,
    onlineUsers: 734,
    totalLabel: 'Всего пользователей',
    onlineLabel: 'Сейчас онлайн',
    onlineHint: 'Обновляется в реальном времени',
    loading: false,
    error: '',
    loadingText: 'Загрузка...',
    errorText: 'Не удалось загрузить',
  },
} satisfies Meta<typeof HomeUsersStats>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const HighOnline: Story = {
  args: {
    totalUsers: 21000,
    onlineUsers: 4890,
  },
};

export const Loading: Story = {
  args: {
    loading: true,
  },
};

export const Mobile: Story = {
  parameters: {
    viewport: {
      defaultViewport: 'mobile1',
    },
  },
};
