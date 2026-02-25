import type { Meta, StoryObj } from '@storybook/vue3';
import UiTextarea from './UiTextarea.vue';

const meta = {
  title: 'UI/Form Controls/UiTextarea',
  component: UiTextarea,
  tags: ['autodocs'],
  args: {
    label: 'Комментарий',
    modelValue: 'Текст комментария для модерации.',
    placeholder: 'Введите комментарий',
    hint: 'До 500 символов',
    rows: 4,
    required: false,
    disabled: false,
    error: '',
  },
  parameters: {
    localeArgs: {
      ru: {
        label: 'Комментарий',
        modelValue: 'Текст комментария для модерации.',
        placeholder: 'Введите комментарий',
        hint: 'До 500 символов',
      },
      en: {
        label: 'Comment',
        modelValue: 'Moderation comment text.',
        placeholder: 'Enter comment',
        hint: 'Up to 500 characters',
      },
    },
  },
} satisfies Meta<typeof UiTextarea>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const WithError: Story = {
  args: {
    error: 'Поле обязательно для заполнения',
    hint: '',
    modelValue: '',
  },
  parameters: {
    localeArgs: {
      ru: { error: 'Поле обязательно для заполнения' },
      en: { error: 'This field is required' },
    },
  },
};

export const Disabled: Story = {
  args: {
    disabled: true,
  },
};
