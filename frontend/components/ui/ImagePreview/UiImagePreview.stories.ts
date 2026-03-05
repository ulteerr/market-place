import type { Meta, StoryObj } from '@storybook/vue3';
import { expect, userEvent, within } from 'storybook/test';
import UiImagePreview from './UiImagePreview.vue';

const demoImage =
  'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1000&q=80';

const meta = {
  title: 'UI/Media/UiImagePreview',
  component: UiImagePreview,
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['table', 'card'],
    },
  },
  args: {
    src: demoImage,
    alt: 'Device and keyboard',
    previewAlt: 'Large preview image',
    variant: 'table',
    fallbackText: '—',
    previewTitle: 'Предпросмотр изображения',
    openAriaLabel: 'Открыть изображение',
  },
  parameters: {
    localeArgs: {
      ru: {
        previewTitle: 'Предпросмотр изображения',
        openAriaLabel: 'Открыть изображение',
        fallbackText: '—',
      },
      en: {
        previewTitle: 'Image preview',
        openAriaLabel: 'Open image',
        fallbackText: '—',
      },
    },
  },
} satisfies Meta<typeof UiImagePreview>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

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
  parameters: {
    localeArgs: {
      ru: { fallbackText: 'Нет изображения' },
      en: { fallbackText: 'No image' },
    },
  },
};

export const InteractionOpenPreview: Story = {
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const trigger = canvas.getByRole('button', {
      name: /Открыть изображение|Open image/i,
    });
    await userEvent.click(trigger);
    const dialog = canvas.getByRole('dialog');
    await expect(dialog).toBeInTheDocument();
    await expect(
      canvas.getByText(/Предпросмотр изображения|Image preview/i)
    ).toBeInTheDocument();
  },
};
