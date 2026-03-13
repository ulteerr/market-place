<template>
  <section :class="styles.page" data-test="account-dashboard-page">
    <AccountPageSkeleton
      v-if="pageState === 'loading'"
      :show-metrics="true"
      :cards="2"
      :list-items="4"
      data-test="account-dashboard-loading"
    />

    <template v-else>
      <PageHero
        :eyebrow="t('app.account.dashboard.eyebrow')"
        :title="t('app.account.dashboard.title')"
        :description="t('app.account.dashboard.description')"
      />

      <PrivateStateMessage
        v-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.account.dashboard.emptyTitle')"
        :description="t('app.account.dashboard.emptyDescription')"
        data-test="account-dashboard-empty"
      />

      <PrivateStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.account.dashboard.errorTitle')"
        :description="t('app.account.dashboard.errorDescription')"
        data-test="account-dashboard-error"
      />

      <template v-else>
        <div :class="styles.metrics" data-test="account-dashboard-metrics">
          <AccountDashboardMetric
            v-for="metric in metrics"
            :key="metric.label"
            :label="metric.label"
            :value="metric.value"
            :caption="metric.caption"
            data-test="account-dashboard-metric-card"
          />
        </div>

        <div :class="styles.grid">
          <AccountDashboardSection
            :eyebrow="t('app.account.dashboard.sections.overview')"
            :title="t('app.account.dashboard.sections.prioritiesTitle')"
            :action-label="t('app.account.dashboard.sections.profileAction')"
            action-to="/account/profile"
            data-test="account-dashboard-priorities"
          >
            <ul :class="styles.list">
              <li v-for="item in priorities" :key="item.title" :class="styles.listItem">
                <div>
                  <p :class="styles.itemTitle">{{ item.title }}</p>
                  <p :class="styles.itemText">{{ item.description }}</p>
                </div>
                <span :class="styles.itemBadge">{{ item.badge }}</span>
              </li>
            </ul>
          </AccountDashboardSection>

          <AccountDashboardSection
            :eyebrow="t('app.account.dashboard.sections.organizationsEyebrow')"
            :title="t('app.account.dashboard.sections.organizationsTitle')"
            :action-label="t('app.account.dashboard.sections.organizationsAction')"
            action-to="/organizations"
            data-test="account-dashboard-organizations"
          >
            <ul :class="styles.stack">
              <li
                v-for="organization in organizations"
                :key="organization.name"
                :class="styles.stackItem"
              >
                <div>
                  <p :class="styles.itemTitle">{{ organization.name }}</p>
                  <p :class="styles.itemText">{{ organization.role }}</p>
                </div>
                <span :class="styles.itemMeta">{{ organization.scope }}</span>
              </li>
            </ul>
          </AccountDashboardSection>
        </div>

        <AccountDashboardSection
          :eyebrow="t('app.account.dashboard.sections.activityEyebrow')"
          :title="t('app.account.dashboard.sections.activityTitle')"
          :action-label="t('app.account.dashboard.sections.activityAction')"
          action-to="/account/activity"
          data-test="account-dashboard-activity"
        >
          <ul :class="styles.stack">
            <li v-for="entry in activity" :key="entry.title" :class="styles.stackItem">
              <div>
                <p :class="styles.itemTitle">{{ entry.title }}</p>
                <p :class="styles.itemText">{{ entry.description }}</p>
              </div>
              <span :class="styles.itemMeta">{{ entry.meta }}</span>
            </li>
          </ul>
        </AccountDashboardSection>
      </template>
    </template>
  </section>
</template>

<script setup lang="ts">
import { usePrivatePreviewState } from '~/composables/layout/usePrivatePreviewState';
import AccountDashboardMetric from '~/components/account/AccountDashboardMetric/AccountDashboardMetric.vue';
import AccountPageSkeleton from '~/components/account/AccountPageSkeleton/AccountPageSkeleton.vue';
import AccountDashboardSection from '~/components/account/AccountDashboardSection/AccountDashboardSection.vue';
import PrivateStateMessage from '~/components/private/PrivateStateMessage/PrivateStateMessage.vue';
import PageHero from '~/components/ui/PageHero/PageHero.vue';
import styles from './index.module.scss';

definePageMeta({
  layout: 'account',
});

const { t } = useI18n();
const pageState = usePrivatePreviewState();

const metrics = computed(() => [
  {
    label: t('app.account.dashboard.metrics.profileLabel'),
    value: '84%',
    caption: t('app.account.dashboard.metrics.profileCaption'),
  },
  {
    label: t('app.account.dashboard.metrics.organizationsLabel'),
    value: '6',
    caption: t('app.account.dashboard.metrics.organizationsCaption'),
  },
  {
    label: t('app.account.dashboard.metrics.requestsLabel'),
    value: '3',
    caption: t('app.account.dashboard.metrics.requestsCaption'),
  },
]);

const priorities = computed(() => [
  {
    title: t('app.account.dashboard.priorities.contactsTitle'),
    description: t('app.account.dashboard.priorities.contactsDescription'),
    badge: t('app.common.badges.p0'),
  },
  {
    title: t('app.account.dashboard.priorities.invitesTitle'),
    description: t('app.account.dashboard.priorities.invitesDescription'),
    badge: t('app.common.badges.newTwo'),
  },
  {
    title: t('app.account.dashboard.priorities.themeTitle'),
    description: t('app.account.dashboard.priorities.themeDescription'),
    badge: t('app.common.badges.ui'),
  },
]);

const organizations = computed(() => [
  {
    name: t('app.common.names.floorballCenter'),
    role: t('app.common.roles.owner'),
    scope: t('app.account.dashboard.organizations.primaryScope'),
  },
  {
    name: t('app.common.names.northHub'),
    role: t('app.common.roles.manager'),
    scope: t('app.account.dashboard.organizations.workScope'),
  },
  {
    name: t('app.common.names.kidsLab'),
    role: t('app.common.roles.member'),
    scope: t('app.account.dashboard.organizations.participantScope'),
  },
]);

const activity = computed(() => [
  {
    title: t('app.account.dashboard.activity.reportTitle'),
    description: t('app.account.dashboard.activity.reportDescription'),
    meta: t('app.common.meta.today1420'),
  },
  {
    title: t('app.account.dashboard.activity.inviteTitle'),
    description: t('app.account.dashboard.activity.inviteDescription'),
    meta: t('app.common.meta.yesterday1805'),
  },
  {
    title: t('app.account.dashboard.activity.settingsTitle'),
    description: t('app.account.dashboard.activity.settingsDescription'),
    meta: t('app.common.meta.march11'),
  },
]);
</script>
