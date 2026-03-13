import type { Meta, StoryObj } from '@storybook/vue3';
import OrganizationsRosterSection from './OrganizationsRosterSection.vue';

const meta = {
  title: 'Private/Organizations/OrganizationsRosterSection',
  component: OrganizationsRosterSection,
  tags: ['autodocs'],
  args: {
    eyebrow: 'Organizations',
    title: 'Участники и рабочие связи',
    summary: '12 записей',
    items: [
      {
        name: 'Центр развития Север',
        description: 'Основная организация пользователя. Доступ к участникам и заявкам.',
        status: 'owner',
        meta: 'Полный доступ',
      },
      {
        name: 'Академия Флорбола',
        description: 'Рабочее участие через staff-профиль и операционные задачи.',
        status: 'staff',
        meta: 'Только рабочий контур',
      },
      {
        name: 'Творческая студия Линия',
        description: 'Участие через shared membership без прав на модерацию.',
        status: 'member',
        meta: 'Read only',
      },
    ],
  },
} satisfies Meta<typeof OrganizationsRosterSection>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Dark: Story = {
  globals: {
    theme: 'dark',
  },
};
