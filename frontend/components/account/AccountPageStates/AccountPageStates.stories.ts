import type { Meta, StoryObj } from '@storybook/vue3';
import AccountPageSkeleton from '~/components/account/AccountPageSkeleton/AccountPageSkeleton.vue';
import PrivateStateMessage from '~/components/private/PrivateStateMessage/PrivateStateMessage.vue';

const meta = {
  title: 'Private/Account/AccountPageStates',
  tags: ['autodocs'],
} satisfies Meta;

export default meta;
type Story = StoryObj<typeof meta>;

export const Loading: Story = {
  render: () => ({
    components: { AccountPageSkeleton },
    template: '<AccountPageSkeleton :show-metrics="true" :cards="2" :list-items="4" />',
  }),
};

export const Empty: Story = {
  render: () => ({
    components: { PrivateStateMessage },
    template: `
      <PrivateStateMessage
        eyebrow="Empty"
        title="Данные кабинета пока не подготовлены"
        description="После первой активности пользователя здесь появятся метрики, связанные организации и обзор ключевых сценариев."
      />
    `,
  }),
};

export const Error: Story = {
  render: () => ({
    components: { PrivateStateMessage },
    template: `
      <PrivateStateMessage
        eyebrow="Error"
        title="Не удалось собрать dashboard"
        description="Нужно повторить запрос данных кабинета или проверить источник пользовательского профиля."
      />
    `,
  }),
};

export const Dark: Story = {
  ...Loading,
  globals: {
    theme: 'dark',
  },
};
