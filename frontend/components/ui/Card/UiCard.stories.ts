import type { Meta, StoryObj } from '@storybook/vue3';
import UiCard from './UiCard.vue';

const meta = {
  title: 'UI/Layout/UiCard',
  component: UiCard,
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['default', 'elevated', 'outline'],
    },
    padding: {
      control: 'select',
      options: ['sm', 'md', 'lg'],
    },
  },
  args: {
    variant: 'default',
    padding: 'md',
    interactive: false,
  },
} satisfies Meta<typeof UiCard>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  render: (args) => ({
    components: { UiCard },
    setup: () => ({ args }),
    template: `
      <UiCard v-bind="args">
        <template #header>
          <h3 style="font-size: 1rem; font-weight: 700; margin: 0;">Заголовок карточки</h3>
        </template>
        <p style="margin: 0;">Базовый вариант канонической карточки.</p>
        <template #footer>
          <a href="#" style="font-weight: 600; color: var(--accent); text-decoration: none;">Действие</a>
        </template>
      </UiCard>
    `,
  }),
};

export const ElevatedInteractive: Story = {
  args: {
    variant: 'elevated',
    interactive: true,
  },
  render: (args) => ({
    components: { UiCard },
    setup: () => ({ args }),
    template: `
      <UiCard v-bind="args">
        <template #header>
          <h3 style="font-size: 1rem; font-weight: 700; margin: 0;">Elevated + Interactive</h3>
        </template>
        <p style="margin: 0;">Используется для кликабельных preview и KPI-блоков.</p>
      </UiCard>
    `,
  }),
};

export const OutlineCompact: Story = {
  args: {
    variant: 'outline',
    padding: 'sm',
  },
  render: (args) => ({
    components: { UiCard },
    setup: () => ({ args }),
    template: `
      <UiCard v-bind="args">
        <p style="margin: 0;">Outline-стиль для вторичных и сервисных карточек.</p>
      </UiCard>
    `,
  }),
};
