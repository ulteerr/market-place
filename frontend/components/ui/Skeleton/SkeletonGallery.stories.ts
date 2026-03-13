import type { Meta, StoryObj } from '@storybook/vue3';
import UiHeroSkeleton from './UiHeroSkeleton.vue';
import UiCardSkeleton from './UiCardSkeleton.vue';
import UiListSkeleton from './UiListSkeleton.vue';
import UiFilterBarSkeleton from './UiFilterBarSkeleton.vue';
import UiTableSkeleton from './UiTableSkeleton.vue';
import PublicCardGridSkeleton from '~/components/public/PublicCardGridSkeleton/PublicCardGridSkeleton.vue';
import AccountPageSkeleton from '~/components/account/AccountPageSkeleton/AccountPageSkeleton.vue';
import OrganizationsPageSkeleton from '~/components/organizations/OrganizationsPageSkeleton/OrganizationsPageSkeleton.vue';

const meta = {
  title: 'UI/Skeletons/Gallery',
  tags: ['autodocs'],
} satisfies Meta;

export default meta;
type Story = StoryObj<typeof meta>;

export const AllSkeletons: Story = {
  render: () => ({
    components: {
      UiHeroSkeleton,
      UiCardSkeleton,
      UiListSkeleton,
      UiFilterBarSkeleton,
      UiTableSkeleton,
      PublicCardGridSkeleton,
      AccountPageSkeleton,
      OrganizationsPageSkeleton,
    },
    template: `
      <div style="display: grid; gap: 24px;">
        <section style="display: grid; gap: 12px;">
          <h3 style="margin: 0; font-size: 1rem; font-weight: 700;">Base Skeletons</h3>
          <UiHeroSkeleton />
          <UiFilterBarSkeleton :chips="5" />
          <UiCardSkeleton />
          <UiListSkeleton :items="4" />
          <UiTableSkeleton :columns="4" :rows="4" />
        </section>

        <section style="display: grid; gap: 12px;">
          <h3 style="margin: 0; font-size: 1rem; font-weight: 700;">Public</h3>
          <PublicCardGridSkeleton :items="3" />
        </section>

        <section style="display: grid; gap: 12px;">
          <h3 style="margin: 0; font-size: 1rem; font-weight: 700;">Account</h3>
          <AccountPageSkeleton :show-metrics="true" :cards="2" :list-items="3" />
        </section>

        <section style="display: grid; gap: 12px;">
          <h3 style="margin: 0; font-size: 1rem; font-weight: 700;">Organizations</h3>
          <OrganizationsPageSkeleton :show-metrics="true" :cards="2" :list-items="4" />
        </section>
      </div>
    `,
  }),
};

export const AllSkeletonsDark: Story = {
  ...AllSkeletons,
  globals: {
    theme: 'dark',
  },
};
