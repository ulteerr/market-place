<template>
  <section :class="styles.page" data-test="organizations-join-requests-page">
    <OrganizationsPageSkeleton
      v-if="pageState === 'loading'"
      :show-metrics="true"
      :cards="2"
      :list-items="5"
      data-test="organizations-join-requests-loading"
    />

    <template v-else>
      <PageHero
        :eyebrow="t('app.organizations.joinRequests.eyebrow')"
        :title="t('app.organizations.joinRequests.title')"
        :description="t('app.organizations.joinRequests.description')"
      />

      <PrivateStateMessage
        v-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.organizations.joinRequests.emptyTitle')"
        :description="t('app.organizations.joinRequests.emptyDescription')"
        data-test="organizations-join-requests-empty"
      />

      <PrivateStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.organizations.joinRequests.errorTitle')"
        :description="t('app.organizations.joinRequests.errorDescription')"
        data-test="organizations-join-requests-error"
      />

      <template v-else>
        <div :class="styles.filters" data-test="organizations-join-requests-filters">
          <UiCard
            v-for="filter in filters"
            :key="filter.label"
            padding="md"
            data-test="organizations-join-requests-filter-card"
          >
            <p :class="styles.filterLabel">{{ filter.label }}</p>
            <p :class="styles.filterValue">{{ filter.value }}</p>
            <p :class="styles.filterCaption">{{ filter.caption }}</p>
          </UiCard>
        </div>

        <div :class="styles.grid">
          <UiCard padding="lg" data-test="organizations-join-requests-inbox">
            <template #header>
              <div :class="styles.sectionHeader">
                <div>
                  <p :class="styles.eyebrow">
                    {{ t('app.organizations.joinRequests.sections.inboxEyebrow') }}
                  </p>
                  <h2 :class="styles.sectionTitle">
                    {{ t('app.organizations.joinRequests.sections.inboxTitle') }}
                  </h2>
                </div>
                <span :class="styles.sectionMeta">
                  {{
                    canReviewJoinRequests
                      ? t('app.organizations.joinRequests.sections.reviewEnabled')
                      : t('app.organizations.joinRequests.sections.reviewReadonly')
                  }}
                </span>
              </div>
            </template>

            <ul :class="styles.stack">
              <li v-for="request in inboxRequests" :key="request.title" :class="styles.stackItem">
                <div>
                  <p :class="styles.itemTitle">{{ request.title }}</p>
                  <p :class="styles.itemText">{{ request.description }}</p>
                  <p :class="styles.note">{{ request.note }}</p>
                </div>
                <div :class="styles.metaColumn">
                  <span :class="styles.badge">{{ request.status }}</span>
                  <span :class="styles.itemMeta">{{ request.subjectType }}</span>
                  <span :class="styles.itemMeta">{{ request.meta }}</span>
                </div>
              </li>
            </ul>
          </UiCard>

          <UiCard padding="lg" data-test="organizations-join-requests-review">
            <template #header>
              <div :class="styles.sectionHeader">
                <div>
                  <p :class="styles.eyebrow">
                    {{ t('app.organizations.joinRequests.sections.reviewEyebrow') }}
                  </p>
                  <h2 :class="styles.sectionTitle">
                    {{ t('app.organizations.joinRequests.sections.reviewTitle') }}
                  </h2>
                </div>
              </div>
            </template>

            <ul :class="styles.todoList">
              <li v-for="step in reviewChecklist" :key="step.title" :class="styles.todoItem">
                <p :class="styles.itemTitle">{{ step.title }}</p>
                <p :class="styles.itemText">{{ step.description }}</p>
              </li>
            </ul>
          </UiCard>
        </div>

        <UiCard padding="lg" data-test="organizations-join-requests-history">
          <template #header>
            <div :class="styles.sectionHeader">
              <div>
                <p :class="styles.eyebrow">
                  {{ t('app.organizations.joinRequests.sections.historyEyebrow') }}
                </p>
                <h2 :class="styles.sectionTitle">
                  {{ t('app.organizations.joinRequests.sections.historyTitle') }}
                </h2>
              </div>
              <span :class="styles.sectionMeta">{{
                t('app.organizations.joinRequests.sections.historyMeta')
              }}</span>
            </div>
          </template>

          <ul :class="styles.stack">
            <li v-for="request in reviewedRequests" :key="request.title" :class="styles.stackItem">
              <div>
                <p :class="styles.itemTitle">{{ request.title }}</p>
                <p :class="styles.itemText">{{ request.description }}</p>
                <p :class="styles.note">{{ request.note }}</p>
              </div>
              <div :class="styles.metaColumn">
                <span :class="styles.badge">{{ request.status }}</span>
                <span :class="styles.itemMeta">{{ request.subjectType }}</span>
                <span :class="styles.itemMeta">{{ request.meta }}</span>
              </div>
            </li>
          </ul>
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
import styles from './join-requests.module.scss';

definePageMeta({
  layout: 'organizations',
  middleware: 'organizations-permission',
  permission: 'org.members.read',
});

const { t } = useI18n();
const pageState = usePrivatePreviewState();
const { canReviewJoinRequests } = useOrganizationAccess();

const filters = computed(() => [
  {
    label: t('app.organizations.joinRequests.filters.totalLabel'),
    value: '14',
    caption: t('app.organizations.joinRequests.filters.totalCaption'),
  },
  {
    label: t('app.organizations.joinRequests.filters.pendingLabel'),
    value: '5',
    caption: t('app.organizations.joinRequests.filters.pendingCaption'),
  },
  {
    label: t('app.organizations.joinRequests.filters.subjectLabel'),
    value: t('app.organizations.joinRequests.filters.subjectValue'),
    caption: t('app.organizations.joinRequests.filters.subjectCaption'),
  },
]);

const inboxRequests = computed(() => [
  {
    title: t('app.organizations.joinRequestItems.inboxOneTitle'),
    description: t('app.organizations.joinRequestItems.inboxOneDescription'),
    note: t('app.organizations.joinRequestItems.inboxOneNote'),
    status: t('app.common.statuses.pending'),
    subjectType: t('app.common.roles.user'),
    meta: t('app.organizations.joinRequestItems.inboxOneMeta'),
  },
  {
    title: t('app.organizations.joinRequestItems.inboxTwoTitle'),
    description: t('app.organizations.joinRequestItems.inboxTwoDescription'),
    note: t('app.organizations.joinRequestItems.inboxTwoNote'),
    status: t('app.common.statuses.new'),
    subjectType: t('app.common.roles.child'),
    meta: t('app.organizations.joinRequestItems.inboxTwoMeta'),
  },
  {
    title: t('app.organizations.joinRequestItems.inboxThreeTitle'),
    description: t('app.organizations.joinRequestItems.inboxThreeDescription'),
    note: t('app.organizations.joinRequestItems.inboxThreeNote'),
    status: t('app.common.statuses.review'),
    subjectType: t('app.common.roles.user'),
    meta: t('app.organizations.joinRequestItems.inboxThreeMeta'),
  },
]);

const reviewChecklist = computed(() => [
  {
    title: t('app.organizations.joinRequestItems.checklistTypeTitle'),
    description: t('app.organizations.joinRequestItems.checklistTypeDescription'),
  },
  {
    title: t('app.organizations.joinRequestItems.checklistRequesterTitle'),
    description: t('app.organizations.joinRequestItems.checklistRequesterDescription'),
  },
  {
    title: t('app.organizations.joinRequestItems.checklistNoteTitle'),
    description: t('app.organizations.joinRequestItems.checklistNoteDescription'),
  },
  {
    title: t('app.organizations.joinRequestItems.checklistReviewerTitle'),
    description: t('app.organizations.joinRequestItems.checklistReviewerDescription'),
  },
]);

const reviewedRequests = computed(() => [
  {
    title: t('app.organizations.joinRequestItems.reviewedOneTitle'),
    description: t('app.organizations.joinRequestItems.reviewedOneDescription'),
    note: t('app.organizations.joinRequestItems.reviewedOneNote'),
    status: t('app.common.statuses.approved'),
    subjectType: t('app.common.roles.user'),
    meta: t('app.organizations.joinRequestItems.reviewedOneMeta'),
  },
  {
    title: t('app.organizations.joinRequestItems.reviewedTwoTitle'),
    description: t('app.organizations.joinRequestItems.reviewedTwoDescription'),
    note: t('app.organizations.joinRequestItems.reviewedTwoNote'),
    status: t('app.common.statuses.rejected'),
    subjectType: t('app.common.roles.child'),
    meta: t('app.organizations.joinRequestItems.reviewedTwoMeta'),
  },
  {
    title: t('app.organizations.joinRequestItems.reviewedThreeTitle'),
    description: t('app.organizations.joinRequestItems.reviewedThreeDescription'),
    note: t('app.organizations.joinRequestItems.reviewedThreeNote'),
    status: t('app.common.statuses.approved'),
    subjectType: t('app.common.roles.user'),
    meta: t('app.organizations.joinRequestItems.reviewedThreeMeta'),
  },
]);
</script>
