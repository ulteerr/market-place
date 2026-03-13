import type { Meta, StoryObj } from '@storybook/vue3';
import PublicSection from './PublicSection.vue';
import PublicCardGrid from '../PublicCardGrid/PublicCardGrid.vue';
import PublicActionLinks from '../PublicActionLinks/PublicActionLinks.vue';

const meta = {
  title: 'Public/NeoEditorial/PublicSection',
  component: PublicSection,
  tags: ['autodocs'],
  args: {
    title: 'Модульная секция для публичных страниц',
    description:
      'Собирает переиспользуемые блоки без копирования верстки между главной, каталогом и контентными страницами.',
  },
} satisfies Meta<typeof PublicSection>;

export default meta;
type Story = StoryObj<typeof meta>;

const cardItems = [
  {
    title: 'Каталог направлений',
    description: 'Переход в SEO-страницы каталога без изменения канонического UI-слоя.',
    to: '/catalog',
  },
  {
    title: 'Контентные страницы',
    description: 'Landing и editorial-страницы на тех же токенах и секциях.',
    to: '/content',
    variant: 'outline' as const,
  },
  {
    title: 'Кабинеты',
    description: 'Приватные маршруты используют тот же дизайн-канон, но без SEO/schema-слоя.',
    to: '/account',
    variant: 'elevated' as const,
  },
];

const links = [
  { label: 'Открыть каталог', to: '/catalog' },
  { label: 'Посмотреть контент', to: '/content' },
];

export const EditorialSection: Story = {
  render: (args) => ({
    components: { PublicSection, PublicCardGrid, PublicActionLinks },
    setup: () => ({ args, cardItems, links }),
    template: `
      <PublicSection v-bind="args">
        <PublicCardGrid :items="cardItems" />
        <div style="height: 16px;" />
        <PublicActionLinks :links="links" />
      </PublicSection>
    `,
  }),
};

export const EditorialSectionDark: Story = {
  ...EditorialSection,
  globals: {
    theme: 'dark',
  },
};
