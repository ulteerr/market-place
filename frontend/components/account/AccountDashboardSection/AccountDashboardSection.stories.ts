import type { Meta, StoryObj } from '@storybook/vue3';
import AccountDashboardSection from './AccountDashboardSection.vue';
import AccountDashboardMetric from '../AccountDashboardMetric/AccountDashboardMetric.vue';

const meta = {
  title: 'Private/Account/AccountDashboardSection',
  component: AccountDashboardSection,
  tags: ['autodocs'],
  args: {
    eyebrow: 'Account',
    title: 'Сводка по кабинету пользователя',
    actionLabel: 'Открыть профиль',
    actionTo: '/account/profile',
  },
} satisfies Meta<typeof AccountDashboardSection>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: (args) => ({
    components: { AccountDashboardSection, AccountDashboardMetric },
    setup: () => ({ args }),
    template: `
      <AccountDashboardSection v-bind="args">
        <div style="display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
          <AccountDashboardMetric
            label="Активные заявки"
            value="04"
            caption="Требуют ответа в течение 24 часов"
          />
          <AccountDashboardMetric
            label="Организации"
            value="07"
            caption="Собственные и доступные через участие"
            variant="outline"
          />
        </div>
      </AccountDashboardSection>
    `,
  }),
};

export const Dark: Story = {
  ...Default,
  globals: {
    theme: 'dark',
  },
};
