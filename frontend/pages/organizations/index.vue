<template>
  <section :class="styles.page" data-test="organizations-overview-page">
    <OrganizationsPageSkeleton
      v-if="pageState === 'loading'"
      :show-metrics="true"
      :cards="2"
      :list-items="4"
      data-test="organizations-overview-loading"
    />

    <template v-else>
      <PageHero
        :eyebrow="t('app.organizations.overview.eyebrow')"
        :title="t('app.organizations.overview.title')"
        :description="t('app.organizations.overview.description')"
      />

      <PrivateStateMessage
        v-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.organizations.overview.emptyTitle')"
        :description="t('app.organizations.overview.emptyDescription')"
        data-test="organizations-overview-empty"
      />

      <PrivateStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.organizations.overview.errorTitle')"
        :description="t('app.organizations.overview.errorDescription')"
        data-test="organizations-overview-error"
      />

      <template v-else>
        <div :class="styles.metrics" data-test="organizations-overview-metrics">
          <UiCard
            v-for="metric in metrics"
            :key="metric.label"
            padding="md"
            data-test="organizations-overview-metric-card"
          >
            <p :class="styles.metricLabel">{{ metric.label }}</p>
            <p :class="styles.metricValue">{{ metric.value }}</p>
            <p :class="styles.metricCaption">{{ metric.caption }}</p>
          </UiCard>
        </div>

        <div :class="styles.grid">
          <UiCard padding="lg" data-test="organizations-owned-section">
            <template #header>
              <div :class="styles.sectionHeader">
                <div>
                  <p :class="styles.eyebrow">
                    {{ t('app.organizations.overview.sections.ownerEyebrow') }}
                  </p>
                  <h2 :class="styles.sectionTitle">
                    {{ t('app.organizations.overview.sections.ownerTitle') }}
                  </h2>
                </div>
                <NuxtLink v-if="canReadMembers" to="/organizations/members" :class="styles.link">{{
                  t('app.organizations.overview.sections.ownerAction')
                }}</NuxtLink>
              </div>
            </template>

            <ul :class="styles.stack">
              <li
                v-for="organization in ownedOrganizations"
                :key="organization.name"
                :class="styles.stackItem"
              >
                <div>
                  <p :class="styles.itemTitle">{{ organization.name }}</p>
                  <p :class="styles.itemText">{{ organization.description }}</p>
                </div>
                <div :class="styles.metaColumn">
                  <span :class="styles.metaBadge">{{ organization.status }}</span>
                  <span :class="styles.itemMeta">{{ organization.meta }}</span>
                </div>
              </li>
            </ul>
          </UiCard>

          <UiCard padding="lg" data-test="organizations-memberships-section">
            <template #header>
              <div :class="styles.sectionHeader">
                <div>
                  <p :class="styles.eyebrow">
                    {{ t('app.organizations.overview.sections.memberEyebrow') }}
                  </p>
                  <h2 :class="styles.sectionTitle">
                    {{ t('app.organizations.overview.sections.memberTitle') }}
                  </h2>
                </div>
                <NuxtLink v-if="canReadClients" to="/organizations/clients" :class="styles.link">{{
                  t('app.organizations.overview.sections.memberAction')
                }}</NuxtLink>
              </div>
            </template>

            <ul :class="styles.stack">
              <li
                v-for="membership in memberships"
                :key="membership.name"
                :class="styles.stackItem"
              >
                <div>
                  <p :class="styles.itemTitle">{{ membership.name }}</p>
                  <p :class="styles.itemText">{{ membership.role }}</p>
                </div>
                <span :class="styles.itemMeta">{{ membership.scope }}</span>
              </li>
            </ul>
          </UiCard>
        </div>

        <UiCard padding="lg" data-test="organizations-join-requests-section">
          <template #header>
            <div :class="styles.sectionHeader">
              <div>
                <p :class="styles.eyebrow">
                  {{ t('app.organizations.overview.sections.requestsEyebrow') }}
                </p>
                <h2 :class="styles.sectionTitle">
                  {{ t('app.organizations.overview.sections.requestsTitle') }}
                </h2>
              </div>
              <div :class="styles.headerMeta">
                <span :class="styles.accessChip">{{ accessLabel }}</span>
                <NuxtLink
                  v-if="canViewJoinRequests"
                  to="/organizations/join-requests"
                  :class="styles.link"
                  >{{ t('app.organizations.overview.sections.requestsAction') }}</NuxtLink
                >
              </div>
            </div>
          </template>

          <div :class="styles.gridBottom">
            <ul :class="styles.stack">
              <li v-for="request in joinRequests" :key="request.title" :class="styles.stackItem">
                <div>
                  <p :class="styles.itemTitle">{{ request.title }}</p>
                  <p :class="styles.itemText">{{ request.description }}</p>
                </div>
                <div :class="styles.metaColumn">
                  <span :class="styles.metaBadge">{{ request.status }}</span>
                  <span :class="styles.itemMeta">{{ request.meta }}</span>
                </div>
              </li>
            </ul>

            <ul :class="styles.todoList">
              <li v-for="step in nextSteps" :key="step.title" :class="styles.todoItem">
                <p :class="styles.itemTitle">{{ step.title }}</p>
                <p :class="styles.itemText">{{ step.description }}</p>
              </li>
            </ul>
          </div>
        </UiCard>
      </template>
    </template>
  </section>
</template>

<script setup lang="ts">
import { usePrivatePreviewState } from '~/composables/layout/usePrivatePreviewState';
import { useOrganizationAccess } from '~/composables/useOrganizationAccess';
import OrganizationsPageSkeleton from '~/components/organizations/OrganizationsPageSkeleton/OrganizationsPageSkeleton.vue';
import PrivateStateMessage from '~/components/private/PrivateStateMessage/PrivateStateMessage.vue';
import UiCard from '~/components/ui/Card/UiCard.vue';
import PageHero from '~/components/ui/PageHero/PageHero.vue';
import styles from './index.module.scss';

definePageMeta({
  layout: 'organizations',
  middleware: 'organizations-permission',
  permission: 'org.company.profile.read',
});

const { t } = useI18n();
const pageState = usePrivatePreviewState();
const { accessLabel, canReadClients, canReadMembers, canViewJoinRequests } =
  useOrganizationAccess();

const metrics = computed(() => [
  {
    label: t('app.organizations.overview.metrics.totalLabel'),
    value: '6',
    caption: t('app.organizations.overview.metrics.totalCaption'),
  },
  {
    label: t('app.organizations.overview.metrics.ownedLabel'),
    value: '2',
    caption: t('app.organizations.overview.metrics.ownedCaption'),
  },
  {
    label: t('app.organizations.overview.metrics.requestsLabel'),
    value: '4',
    caption: t('app.organizations.overview.metrics.requestsCaption'),
  },
]);

const ownedOrganizations = computed(() => [
  {
    name: t('app.common.names.floorballCenter'),
    description: t('app.organizations.overview.owned.primaryDescription'),
    status: t('app.common.statuses.active'),
    meta: t('app.organizations.overview.owned.primaryMeta'),
  },
  {
    name: t('app.common.names.juniorArena'),
    description: t('app.organizations.overview.owned.draftDescription'),
    status: t('app.common.statuses.draft'),
    meta: t('app.organizations.overview.owned.draftMeta'),
  },
]);

const memberships = computed(() => [
  {
    name: t('app.common.names.northHub'),
    role: t('app.organizations.overview.memberships.northRole'),
    scope: t('app.organizations.overview.memberships.northScope'),
  },
  {
    name: t('app.common.names.kidsLab'),
    role: t('app.organizations.overview.memberships.kidsRole'),
    scope: t('app.organizations.overview.memberships.kidsScope'),
  },
  {
    name: t('app.common.names.volleyCampus'),
    role: t('app.organizations.overview.memberships.volleyRole'),
    scope: t('app.organizations.overview.memberships.volleyScope'),
  },
]);

const joinRequests = computed(() => [
  {
    title: t('app.organizations.overview.requests.firstTitle'),
    description: t('app.organizations.overview.requests.firstDescription'),
    status: t('app.common.statuses.new'),
    meta: t('app.organizations.overview.requests.firstMeta'),
  },
  {
    title: t('app.organizations.overview.requests.secondTitle'),
    description: t('app.organizations.overview.requests.secondDescription'),
    status: t('app.common.statuses.review'),
    meta: t('app.organizations.overview.requests.secondMeta'),
  },
  {
    title: t('app.organizations.overview.requests.thirdTitle'),
    description: t('app.organizations.overview.requests.thirdDescription'),
    status: t('app.common.statuses.pending'),
    meta: t('app.organizations.overview.requests.thirdMeta'),
  },
]);

const nextSteps = computed(() => [
  {
    title: t('app.organizations.overview.nextSteps.reviewTitle'),
    description: t('app.organizations.overview.nextSteps.reviewDescription'),
  },
  {
    title: t('app.organizations.overview.nextSteps.rolesTitle'),
    description: t('app.organizations.overview.nextSteps.rolesDescription'),
  },
  {
    title: t('app.organizations.overview.nextSteps.profileTitle'),
    description: t('app.organizations.overview.nextSteps.profileDescription'),
  },
]);
</script>
