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
};

export default meta;

export const Default = {};

export const Empty = {
  args: {
    images: [],
  },
};

export const Readonly = {
  args: {
    removable: false,
    showAddButton: false,
  },
};
