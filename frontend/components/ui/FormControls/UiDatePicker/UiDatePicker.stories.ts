import type { Meta, StoryObj } from '@storybook/vue3';
import { expect, userEvent, within } from 'storybook/test';
import { createVModelRender } from '@/.storybook/vue-vmodel-render';
import UiDatePicker from './UiDatePicker.vue';

const meta = {
  title: 'UI/Form Controls/UiDatePicker',
  component: UiDatePicker,
  tags: ['autodocs'],
  argTypes: {
    mode: {
      control: 'select',
      options: ['single', 'range'],
    },
  },
  args: {
    label: 'Дата создания',
    mode: 'single',
    modelValue: '2026-02-21',
    placeholder: 'dd.mm.yyyy',
    placeholderStart: 'От',
    placeholderEnd: 'До',
    hint: 'Выберите дату в календаре',
    error: '',
    required: false,
    disabled: false,
    min: null,
    max: null,
  },
  parameters: {
    localeArgs: {
      ru: {
        label: 'Дата создания',
        placeholderStart: 'От',
        placeholderEnd: 'До',
        hint: 'Выберите дату в календаре',
      },
      en: {
        label: 'Creation date',
        placeholderStart: 'From',
        placeholderEnd: 'To',
        hint: 'Pick a date in the calendar',
      },
    },
  },
  render: createVModelRender(UiDatePicker, 'UiDatePicker'),
} satisfies Meta<typeof UiDatePicker>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Range: Story = {
  args: {
    mode: 'range',
    modelValue: ['2026-02-01', '2026-02-21'],
    label: 'Период',
  },
  parameters: {
    localeArgs: {
      ru: { label: 'Период' },
      en: { label: 'Period' },
    },
  },
};

export const WithLimits: Story = {
  args: {
    modelValue: '2026-02-15',
    min: '2026-02-10',
    max: '2026-02-20',
    hint: 'Разрешен диапазон только в пределах кампании',
  },
  parameters: {
    localeArgs: {
      ru: { hint: 'Разрешен диапазон только в пределах кампании' },
      en: { hint: 'Only campaign range is allowed' },
    },
  },
};

export const WithError: Story = {
  args: {
    modelValue: null,
    error: 'Дата обязательна',
    hint: '',
  },
  parameters: {
    localeArgs: {
      ru: { error: 'Дата обязательна' },
      en: { error: 'Date is required' },
    },
  },
};

export const InteractionOpenPicker: Story = {
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const trigger = canvas.getByRole('button', { name: /Дата создания|Creation date/i });
    await userEvent.click(trigger);
    const nextMonthBtn = await canvas.findByRole('button', { name: '›' });
    await expect(nextMonthBtn).toBeInTheDocument();
  },
};
