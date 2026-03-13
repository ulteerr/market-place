import type { Meta, StoryObj } from '@storybook/vue3';
import PageHero from './PageHero.vue';

const meta = {
  title: 'UI/NeoEditorial/PageHero',
  component: PageHero,
  tags: ['autodocs'],
  args: {
    eyebrow: 'Marketplace',
    title: 'Публичная страница в Neo-Editorial стиле',
    description:
      'Крупная типографика, чистая сетка и семантические токены без привязки к бизнес-логике.',
  },
} satisfies Meta<typeof PageHero>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Light: Story = {};

export const Dark: Story = {
  globals: {
    theme: 'dark',
  },
};
