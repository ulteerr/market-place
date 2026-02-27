import type { Meta, StoryObj } from '@storybook/vue3';
import { expect, userEvent, within } from 'storybook/test';
import { ref, watch } from 'vue';
import UiColorPicker from './UiColorPicker.vue';

const meta = {
  title: 'UI/Form Controls/UiColorPicker',
  component: UiColorPicker,
  tags: ['autodocs'],
  argTypes: {
    modelValue: { control: 'text' },
  },
  args: {
    label: 'Цвет',
    modelValue: '#D6083B',
    placeholder: '#000000',
    hint: 'Выберите цвет на карте или введите HEX',
    required: false,
    disabled: false,
    error: '',
  },
  parameters: {
    localeArgs: {
      ru: {
        label: 'Цвет',
        hint: 'Выберите цвет на карте или введите HEX',
      },
      en: {
        label: 'Color',
        hint: 'Use color map or enter HEX value',
      },
    },
  },
} satisfies Meta<typeof UiColorPicker>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const WithError: Story = {
  args: {
    modelValue: 'red',
    error: 'Укажите цвет в формате HEX',
    hint: '',
  },
  parameters: {
    localeArgs: {
      ru: {
        error: 'Укажите цвет в формате HEX',
      },
      en: {
        error: 'Specify a HEX color',
      },
    },
  },
};

export const InteractionPaletteAndInput: Story = {
  args: {
    modelValue: '',
  },
  render: (args) => ({
    components: { UiColorPicker },
    setup() {
      const value = ref(String(args.modelValue ?? ''));
      watch(
        () => args.modelValue,
        (next) => {
          value.value = String(next ?? '');
        }
      );
      return { args, value };
    },
    template: '<UiColorPicker v-bind="args" v-model="value" />',
  }),
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const input = canvas.getByRole('textbox', { name: /Цвет|Color/i });
    await userEvent.type(input, '#008A49');
    await expect(input).toHaveValue('#008A49');

    await userEvent.click(canvas.getByRole('button', { name: '#1E88E5' }));
    await expect(input).toHaveValue('#1E88E5');
  },
};
