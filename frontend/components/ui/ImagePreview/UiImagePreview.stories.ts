import type { Meta, StoryObj } from '@storybook/vue3';
import UiImagePreview from './UiImagePreview.vue';

const demoImage =
  'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1000&q=80';

const meta = {
  title: 'UI/Media/UiImagePreview',
  component: UiImagePreview,
  tags: ['autodocs'],
  args: {
    src: demoImage,
    alt: 'Device and keyboard',
    previewAlt: 'Large preview image',
    variant: 'table',
    fallbackText: '—',
    previewTitle: 'Предпросмотр изображения',
    openAriaLabel: 'Открыть изображение',
  },
} satisfies Meta<typeof UiImagePreview>;

export default meta;
type Story = StoryObj<typeof meta>;

export const TableVariant: Story = {};

export const CardVariant: Story = {
  args: {
    variant: 'card',
  },
};

export const Empty: Story = {
  args: {
    src: null,
    fallbackText: 'Нет изображения',
  },
};
