import type { Meta, StoryObj } from '@storybook/vue3';
import { within, userEvent, expect } from 'storybook/test';
import AppHeader from './AppHeader.vue';

const meta = {
  title: 'Layout/AppHeader',
  component: AppHeader,
  tags: ['autodocs'],
  parameters: {
    layout: 'fullscreen',
  },
} satisfies Meta<typeof AppHeader>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const CatalogMenuOpen: Story = {
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);

    await userEvent.click(canvas.getByTestId('public-header-catalog-toggle'));
    await expect(canvas.getByTestId('public-header-catalog-menu')).toBeVisible();
  },
};

export const MobileMenuOpen: Story = {
  parameters: {
    viewport: {
      defaultViewport: 'mobile1',
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);

    await userEvent.click(canvas.getByTestId('public-header-mobile-menu-toggle'));
    await expect(canvas.getByTestId('public-header-mobile-menu')).toBeVisible();
  },
};
