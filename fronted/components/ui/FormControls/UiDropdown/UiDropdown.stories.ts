import type { Meta, StoryObj } from '@storybook/vue3';
import UiDropdown from './UiDropdown.vue';

const optionsRu = [
  { label: 'Активен', value: 'active' },
  { label: 'Черновик', value: 'draft' },
  { label: 'Архив', value: 'archived' },
];
const optionsEn = [
  { label: 'Active', value: 'active' },
  { label: 'Draft', value: 'draft' },
  { label: 'Archived', value: 'archived' },
];

const meta = {
  title: 'UI/Form Controls/UiDropdown',
  component: UiDropdown,
  tags: ['autodocs'],
  args: {
    label: 'Статус',
    modelValue: 'active',
    options: optionsRu,
    placeholder: 'Выберите статус',
    hint: 'Используется в фильтрах',
    error: '',
    disabled: false,
  },
  parameters: {
    localeArgs: {
      ru: {
        label: 'Статус',
        options: optionsRu,
        placeholder: 'Выберите статус',
        hint: 'Используется в фильтрах',
      },
      en: {
        label: 'Status',
        options: optionsEn,
        placeholder: 'Select status',
        hint: 'Used in admin filters',
      },
    },
  },
} satisfies Meta<typeof UiDropdown>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const EmptyValue: Story = {
  args: {
    modelValue: null,
  },
};

export const WithError: Story = {
  args: {
    error: 'Выберите значение',
    hint: '',
  },
  parameters: {
    localeArgs: {
      ru: { error: 'Выберите значение' },
      en: { error: 'Please select a value' },
    },
  },
};

export const Disabled: Story = {
  args: {
    disabled: true,
  },
};
