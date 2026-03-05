import type { Meta, StoryObj } from '@storybook/vue3';
import { expect, within } from 'storybook/test';
import { createVModelRender } from '@/.storybook/vue-vmodel-render';
import UiImageDropzone from './UiImageDropzone.vue';

const meta = {
  title: 'UI/Media/UiImageDropzone',
  component: UiImageDropzone,
  tags: ['autodocs'],
  argTypes: {
    multiple: { control: 'boolean' },
    disabled: { control: 'boolean' },
    accept: { control: 'text' },
  },
  render: createVModelRender(UiImageDropzone, 'UiImageDropzone'),
  args: {
    modelValue: [],
    accept: 'image/*,.webp,.png,.jpg,.jpeg',
    multiple: true,
    title: 'Перетащите файлы сюда',
    description: 'Поддерживаются PNG, JPG и WEBP',
    browseButtonText: 'Выбрать изображения',
    disabled: false,
  },
  parameters: {
    localeArgs: {
      ru: {
        title: 'Перетащите файлы сюда',
        description: 'Поддерживаются PNG, JPG и WEBP',
        browseButtonText: 'Выбрать изображения',
      },
      en: {
        title: 'Drag files here',
        description: 'PNG, JPG and WEBP are supported',
        browseButtonText: 'Select images',
      },
    },
  },
} satisfies Meta<typeof UiImageDropzone>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const SingleFile: Story = {
  args: {
    multiple: false,
    title: 'Загрузите основной баннер',
  },
  parameters: {
    localeArgs: {
      ru: { title: 'Загрузите основной баннер' },
      en: { title: 'Upload main banner' },
    },
  },
};

export const Disabled: Story = {
  args: {
    disabled: true,
  },
};

export const InteractionBrowseButton: Story = {
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const browseBtn = canvas.getByRole('button', {
      name: /Выбрать изображения|Select images/i,
    });
    await expect(browseBtn).toBeInTheDocument();
    await expect(browseBtn).not.toBeDisabled();
  },
};
