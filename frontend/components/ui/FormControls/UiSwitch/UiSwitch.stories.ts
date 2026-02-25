import type { Meta, StoryObj } from '@storybook/vue3';
import UiSwitch from './UiSwitch.vue';

const meta = {
  title: 'UI/Form Controls/UiSwitch',
  component: UiSwitch,
  tags: ['autodocs'],
  args: {
    label: 'Показывать в админ-навигации',
    description: 'Переключает видимость раздела для текущего пользователя.',
    modelValue: false,
    hint: 'Изменение применяется сразу',
    error: '',
    required: false,
    disabled: false,
  },
  parameters: {
    localeArgs: {
      ru: {
        label: 'Показывать в админ-навигации',
        description: 'Переключает видимость раздела для текущего пользователя.',
        hint: 'Изменение применяется сразу',
      },
      en: {
        label: 'Show in admin navigation',
        description: 'Toggles section visibility for the current user.',
        hint: 'Changes are applied immediately',
      },
    },
  },
} satisfies Meta<typeof UiSwitch>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Enabled: Story = {
  args: {
    modelValue: true,
  },
};

export const WithError: Story = {
  args: {
    error: 'Сервис недоступен, повторите позже',
    hint: '',
  },
  parameters: {
    localeArgs: {
      ru: { error: 'Сервис недоступен, повторите позже' },
      en: { error: 'Service unavailable, try again later' },
    },
  },
};

export const Disabled: Story = {
  args: {
    disabled: true,
  },
};
