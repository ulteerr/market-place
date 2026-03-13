import type { Meta, StoryObj } from '@storybook/vue3';
import OrganizationsPageSkeleton from '~/components/organizations/OrganizationsPageSkeleton/OrganizationsPageSkeleton.vue';
import PrivateStateMessage from '~/components/private/PrivateStateMessage/PrivateStateMessage.vue';

const meta = {
  title: 'Private/Organizations/OrganizationsPageStates',
  tags: ['autodocs'],
} satisfies Meta;

export default meta;
type Story = StoryObj<typeof meta>;

export const Loading: Story = {
  render: () => ({
    components: { OrganizationsPageSkeleton },
    template: '<OrganizationsPageSkeleton :show-metrics="true" :cards="2" :list-items="4" />',
  }),
};

export const Empty: Story = {
  render: () => ({
    components: { PrivateStateMessage },
    template: `
      <PrivateStateMessage
        eyebrow="Empty"
        title="Организации пока не подключены"
        description="После первого membership или создания собственной организации здесь появятся overview, участники и join requests."
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
        title="Не удалось загрузить organization screen"
        description="Нужно повторить запрос данных организации или проверить permission-контур пользователя."
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
