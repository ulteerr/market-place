import type { Meta, StoryObj } from '@storybook/vue3';
import { ref } from 'vue';
import UiImageDropzone from './UiImageDropzone.vue';

const meta = {
  title: 'UI/Media/UiImageDropzone',
  component: UiImageDropzone,
  tags: ['autodocs'],
  args: {
    modelValue: [],
    accept: 'image/*,.webp,.png,.jpg,.jpeg',
    multiple: true,
    title: 'Перетащите файлы сюда',
    description: 'Поддерживаются PNG, JPG и WEBP',
    browseButtonText: 'Выбрать изображения',
    disabled: false,
  },
  render: (args) => ({
    components: { UiImageDropzone },
    setup() {
      const model = ref<File[]>([]);
      return { args, model };
    },
    template: '<UiImageDropzone v-bind="args" v-model="model" />',
  }),
} satisfies Meta<typeof UiImageDropzone>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const SingleFile: Story = {
  args: {
    multiple: false,
    title: 'Загрузите основной баннер',
  },
};

export const Disabled: Story = {
  args: {
    disabled: true,
  },
};
