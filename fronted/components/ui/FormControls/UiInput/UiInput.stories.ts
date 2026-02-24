import type { Meta, StoryObj } from '@storybook/vue3';
import { expect, userEvent, within } from 'storybook/test';
import UiInput from './UiInput.vue';

const meta = {
  title: 'UI/Form Controls/UiInput',
  component: UiInput,
  tags: ['autodocs'],
  argTypes: {
    modelValue: { control: 'text' },
    preset: {
      control: 'select',
      options: ['text', 'email', 'password', 'number', 'phone', 'url', 'search'],
    },
  },
  args: {
    label: 'Email',
    modelValue: 'admin@example.com',
    preset: 'email',
    hint: 'Используйте корпоративный email',
    placeholder: 'name@company.com',
    required: false,
    disabled: false,
    error: '',
  },
  parameters: {
    localeArgs: {
      ru: {
        label: 'Email',
        hint: 'Используйте корпоративный email',
        placeholder: 'name@company.com',
      },
      en: {
        label: 'Email',
        hint: 'Use your corporate email',
        placeholder: 'name@company.com',
      },
    },
  },
} satisfies Meta<typeof UiInput>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const WithError: Story = {
  args: {
    modelValue: 'invalid',
    error: 'Неверный формат email',
    hint: '',
  },
  parameters: {
    localeArgs: {
      ru: {
        error: 'Неверный формат email',
      },
      en: {
        error: 'Invalid email format',
      },
    },
  },
};

export const PasswordWithToggle: Story = {
  args: {
    label: 'Пароль',
    preset: 'password',
    modelValue: 'secret123',
    passwordToggle: true,
    hint: 'Минимум 8 символов',
  },
  parameters: {
    localeArgs: {
      ru: {
        label: 'Пароль',
        hint: 'Минимум 8 символов',
      },
      en: {
        label: 'Password',
        hint: 'At least 8 characters',
      },
    },
  },
};

export const InteractionTyping: Story = {
  args: {
    label: 'Логин',
    preset: 'text',
    modelValue: '',
    placeholder: 'Введите логин',
  },
  parameters: {
    localeArgs: {
      ru: {
        label: 'Логин',
        placeholder: 'Введите логин',
      },
      en: {
        label: 'Login',
        placeholder: 'Enter login',
      },
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const input = canvas.getByRole('textbox', { name: /Логин|Login/i });
    await userEvent.clear(input);
    await userEvent.type(input, 'superadmin@example.com');
    await expect(input).toHaveValue('superadmin@example.com');
  },
};
