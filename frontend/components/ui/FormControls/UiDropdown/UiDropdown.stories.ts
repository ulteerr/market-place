import type { Meta, StoryObj } from '@storybook/vue3';
import { expect, userEvent, within } from 'storybook/test';
import { createVModelRender } from '@/.storybook/vue-vmodel-render';
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
  argTypes: {
    options: { control: 'object', description: 'List of { label, value }' },
  },
  render: createVModelRender(UiDropdown, 'UiDropdown'),
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

export const InteractionSelectOption: Story = {
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const trigger = canvas.getByRole('button', { name: /Активен|Active/i });
    await userEvent.click(trigger);
    const listbox = canvas.getByRole('listbox');
    const option = within(listbox).getByRole('button', { name: /Черновик|Draft/i });
    await userEvent.click(option);
    await expect(canvas.getByRole('button', { name: /Черновик|Draft/i })).toBeInTheDocument();
  },
};
