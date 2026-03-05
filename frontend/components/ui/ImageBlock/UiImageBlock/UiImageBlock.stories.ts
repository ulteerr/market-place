import type { Meta, StoryObj } from '@storybook/vue3';
import { expect, within } from 'storybook/test';
import UiImageBlock from './UiImageBlock.vue';

const demoImages = [
  {
    id: 1,
    src: 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=700&q=80',
    alt: 'Code on laptop',
    caption: 'Hero desktop',
  },
  {
    id: 2,
    src: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=700&q=80',
    alt: 'Keyboard closeup',
    caption: 'List card',
  },
  {
    id: 3,
    src: 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=700&q=80',
    alt: 'Developer workstation',
    caption: 'Details page',
  },
];

const meta = {
  title: 'UI/Media/UiImageBlock',
  component: UiImageBlock,
  tags: ['autodocs'],
  argTypes: {
    removable: { control: 'boolean' },
    showAddButton: { control: 'boolean' },
  },
  args: {
    title: 'Галерея',
    description: 'Изображения для карточки сущности',
    images: demoImages,
    removable: true,
    showAddButton: true,
    addButtonText: 'Добавить изображение',
    removeButtonText: 'Удалить',
    emptyText: 'Изображений пока нет',
    captionPrefix: 'Фото',
  },
  parameters: {
    localeArgs: {
      ru: {
        title: 'Галерея',
        description: 'Изображения для карточки сущности',
        addButtonText: 'Добавить изображение',
        removeButtonText: 'Удалить',
        emptyText: 'Изображений пока нет',
        captionPrefix: 'Фото',
      },
      en: {
        title: 'Gallery',
        description: 'Images for the entity card',
        addButtonText: 'Add image',
        removeButtonText: 'Remove',
        emptyText: 'No images yet',
        captionPrefix: 'Photo',
      },
    },
  },
} satisfies Meta<typeof UiImageBlock>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Empty: Story = {
  args: {
    images: [],
  },
};

export const Readonly: Story = {
  args: {
    removable: false,
    showAddButton: false,
  },
};

export const InteractionButtonsVisible: Story = {
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    await expect(
      canvas.getByRole('button', { name: /Добавить изображение|Add image/i })
    ).toBeInTheDocument();
    const removeButtons = canvas.getAllByRole('button', { name: /Удалить|Remove/i });
    await expect(removeButtons.length).toBeGreaterThan(0);
  },
};
