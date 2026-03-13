<template>
  <section :class="styles.page" data-test="account-activity-page">
    <AccountPageSkeleton
      v-if="pageState === 'loading'"
      :cards="2"
      :list-items="5"
      data-test="account-activity-loading"
    />

    <template v-else>
      <PageHero
        :eyebrow="t('app.account.activity.eyebrow')"
        :title="t('app.account.activity.title')"
        :description="t('app.account.activity.description')"
      />

      <PrivateStateMessage
        v-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.account.activity.emptyTitle')"
        :description="t('app.account.activity.emptyDescription')"
        data-test="account-activity-empty"
      />

      <PrivateStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.account.activity.errorTitle')"
        :description="t('app.account.activity.errorDescription')"
        data-test="account-activity-error"
      />

      <template v-else>
        <div :class="styles.grid">
          <AccountDashboardSection
            :eyebrow="t('app.account.activity.sections.requestsEyebrow')"
            :title="t('app.account.activity.sections.requestsTitle')"
            data-test="account-activity-requests"
          >
            <ul :class="styles.stack">
              <li v-for="request in requests" :key="request.title" :class="styles.stackItem">
                <div>
                  <p :class="styles.itemTitle">{{ request.title }}</p>
                  <p :class="styles.itemText">{{ request.description }}</p>
                </div>
                <div :class="styles.metaColumn">
                  <span :class="styles.statusBadge">{{ request.status }}</span>
                  <span :class="styles.itemMeta">{{ request.meta }}</span>
                </div>
              </li>
            </ul>
          </AccountDashboardSection>

          <AccountDashboardSection
            :eyebrow="t('app.account.activity.sections.nextEyebrow')"
            :title="t('app.account.activity.sections.nextTitle')"
            data-test="account-activity-next-steps"
          >
            <ul :class="styles.todoList">
              <li v-for="step in nextSteps" :key="step.title" :class="styles.todoItem">
                <p :class="styles.itemTitle">{{ step.title }}</p>
                <p :class="styles.itemText">{{ step.description }}</p>
              </li>
            </ul>
          </AccountDashboardSection>
        </div>

        <AccountDashboardSection
          :eyebrow="t('app.account.activity.sections.timelineEyebrow')"
          :title="t('app.account.activity.sections.timelineTitle')"
          :action-label="t('app.account.activity.sections.timelineAction')"
          action-to="/account"
          data-test="account-activity-timeline"
        >
          <ul :class="styles.stack">
            <li v-for="entry in timeline" :key="entry.title" :class="styles.stackItem">
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
import AccountPageSkeleton from '~/components/account/AccountPageSkeleton/AccountPageSkeleton.vue';
import AccountDashboardSection from '~/components/account/AccountDashboardSection/AccountDashboardSection.vue';
import PrivateStateMessage from '~/components/private/PrivateStateMessage/PrivateStateMessage.vue';
import PageHero from '~/components/ui/PageHero/PageHero.vue';
import styles from './activity.module.scss';

definePageMeta({
  layout: 'account',
});

const { t } = useI18n();
const pageState = usePrivatePreviewState();
const requests = computed(() => [
  {
    title: t('app.account.activity.requests.joinTitle'),
    description: t('app.account.activity.requests.joinDescription'),
    status: t('app.common.statuses.pending'),
    meta: t('app.common.meta.updatedToday'),
  },
  {
    title: t('app.account.activity.requests.uiTitle'),
    description: t('app.account.activity.requests.uiDescription'),
    status: t('app.common.statuses.sent'),
    meta: t('app.common.meta.report1042'),
  },
  {
    title: t('app.account.activity.requests.roleTitle'),
    description: t('app.account.activity.requests.roleDescription'),
    status: t('app.common.statuses.review'),
    meta: t('app.common.meta.waitingDecision'),
  },
]);

const nextSteps = computed(() => [
  {
    title: t('app.account.activity.nextSteps.profileTitle'),
    description: t('app.account.activity.nextSteps.profileDescription'),
  },
  {
    title: t('app.account.activity.nextSteps.inviteTitle'),
    description: t('app.account.activity.nextSteps.inviteDescription'),
  },
  {
    title: t('app.account.activity.nextSteps.reportTitle'),
    description: t('app.account.activity.nextSteps.reportDescription'),
  },
]);

const timeline = computed(() => [
  {
    title: t('app.account.activity.timeline.themeTitle'),
    description: t('app.account.activity.timeline.themeDescription'),
    meta: t('app.common.meta.today1012'),
  },
  {
    title: t('app.account.activity.timeline.reportTitle'),
    description: t('app.account.activity.timeline.reportDescription'),
    meta: t('app.common.meta.yesterday1420'),
  },
  {
    title: t('app.account.activity.timeline.inviteTitle'),
    description: t('app.account.activity.timeline.inviteDescription'),
    meta: t('app.common.meta.march10'),
  },
  {
    title: t('app.account.activity.timeline.settingsTitle'),
    description: t('app.account.activity.timeline.settingsDescription'),
    meta: t('app.common.meta.march9'),
  },
]);
</script>
