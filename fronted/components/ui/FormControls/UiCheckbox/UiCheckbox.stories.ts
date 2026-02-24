import type { Meta, StoryObj } from '@storybook/vue3';
import { expect, userEvent, within } from 'storybook/test';
import UiCheckbox from './UiCheckbox.vue';

const meta = {
  title: 'UI/Form Controls/UiCheckbox',
  component: UiCheckbox,
  tags: ['autodocs'],
  args: {
    label: 'Подтвердить действие',
    description: 'Я подтверждаю, что ознакомлен с условиями.',
    modelValue: false,
    hint: 'Без подтверждения действие недоступно',
    error: '',
    disabled: false,
  },
  parameters: {
    localeArgs: {
      ru: {
        label: 'Подтвердить действие',
        description: 'Я подтверждаю, что ознакомлен с условиями.',
        hint: 'Без подтверждения действие недоступно',
      },
      en: {
        label: 'Confirm action',
        description: 'I confirm that I have read the terms.',
        hint: 'Action is unavailable without confirmation',
      },
    },
  },
} satisfies Meta<typeof UiCheckbox>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Checked: Story = {
  args: {
    modelValue: true,
  },
};

export const WithError: Story = {
  args: {
    error: 'Необходимо подтвердить условие',
    hint: '',
  },
  parameters: {
    localeArgs: {
      ru: { error: 'Необходимо подтвердить условие' },
      en: { error: 'You need to confirm the condition' },
    },
  },
};

export const Disabled: Story = {
  args: {
    disabled: true,
  },
};

export const InteractionToggle: Story = {
  args: {
    modelValue: false,
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const checkbox = canvas.getByRole('checkbox', { name: /Подтвердить действие|Confirm action/i });
    await expect(checkbox).not.toBeChecked();
    await userEvent.click(checkbox);
    await expect(checkbox).toBeChecked();
  },
};
