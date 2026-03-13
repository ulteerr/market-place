<template>
  <section :class="styles.page" data-test="organizations-clients-page">
    <OrganizationsPageSkeleton
      v-if="pageState === 'loading'"
      :cards="2"
      :list-items="4"
      data-test="organizations-clients-loading"
    />

    <template v-else>
      <PageHero
        :eyebrow="t('app.organizations.clients.eyebrow')"
        :title="t('app.organizations.clients.title')"
        :description="t('app.organizations.clients.description')"
      />

      <PrivateStateMessage
        v-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.organizations.clients.emptyTitle')"
        :description="t('app.organizations.clients.emptyDescription')"
        data-test="organizations-clients-empty"
      />

      <PrivateStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.organizations.clients.errorTitle')"
        :description="t('app.organizations.clients.errorDescription')"
        data-test="organizations-clients-error"
      />

      <template v-else>
        <div :class="styles.grid">
          <OrganizationsRosterSection
            :eyebrow="t('app.organizations.clients.sections.activeEyebrow')"
            :title="t('app.organizations.clients.sections.activeTitle')"
            :summary="
              canManageClients
                ? t('app.organizations.clients.sections.activeEditable')
                : t('app.organizations.clients.sections.activeReadonly')
            "
            :items="activeClients"
            data-test="organizations-clients-active"
          />

          <OrganizationsRosterSection
            :eyebrow="t('app.organizations.clients.sections.pendingEyebrow')"
            :title="t('app.organizations.clients.sections.pendingTitle')"
            :summary="t('app.organizations.clients.sections.pendingSummary')"
            :items="pendingClients"
            data-test="organizations-clients-pending"
          />
        </div>

        <OrganizationsRosterSection
          :eyebrow="t('app.organizations.clients.sections.staffEyebrow')"
          :title="t('app.organizations.clients.sections.staffTitle')"
          :summary="t('app.organizations.clients.sections.staffSummary')"
          :items="staff"
          data-test="organizations-clients-staff"
        />
      </template>
    </template>
  </section>
</template>

<script setup lang="ts">
import { usePrivatePreviewState } from '~/composables/layout/usePrivatePreviewState';
import { useOrganizationAccess } from '~/composables/useOrganizationAccess';
import OrganizationsPageSkeleton from '~/components/organizations/OrganizationsPageSkeleton/OrganizationsPageSkeleton.vue';
import OrganizationsRosterSection from '~/components/organizations/OrganizationsRosterSection/OrganizationsRosterSection.vue';
import PrivateStateMessage from '~/components/private/PrivateStateMessage/PrivateStateMessage.vue';
import PageHero from '~/components/ui/PageHero/PageHero.vue';
import styles from './members.module.scss';

definePageMeta({
  layout: 'organizations',
  middleware: 'organizations-permission',
  permission: 'org.children.read',
});

const { t } = useI18n();
const pageState = usePrivatePreviewState();
const { canManageClients } = useOrganizationAccess();

const activeClients = computed(() => [
  {
    name: t('app.organizations.roster.familyName'),
    description: t('app.organizations.roster.familyDescription'),
    status: t('app.common.statuses.active'),
    meta: t('app.organizations.roster.familyMeta'),
  },
  {
    name: t('app.organizations.roster.markName'),
    description: t('app.organizations.roster.markDescription'),
    status: t('app.common.roles.child'),
    meta: t('app.organizations.roster.markMeta'),
  },
  {
    name: t('app.organizations.roster.arinaName'),
    description: t('app.organizations.roster.arinaDescription'),
    status: t('app.common.roles.user'),
    meta: t('app.organizations.roster.arinaMeta'),
  },
]);

const pendingClients = computed(() => [
  {
    name: t('app.organizations.roster.newFamilyName'),
    description: t('app.organizations.roster.newFamilyDescription'),
    status: t('app.common.statuses.pending'),
    meta: t('app.organizations.roster.newFamilyMeta'),
  },
  {
    name: t('app.organizations.roster.draftChildName'),
    description: t('app.organizations.roster.draftChildDescription'),
    status: t('app.common.statuses.draft'),
    meta: t('app.organizations.roster.draftChildMeta'),
  },
  {
    name: t('app.organizations.roster.leadName'),
    description: t('app.organizations.roster.leadDescription'),
    status: t('app.common.statuses.review'),
    meta: t('app.organizations.roster.leadMeta'),
  },
]);

const staff = computed(() => [
  {
    name: t('app.organizations.roster.managerName'),
    description: t('app.organizations.roster.managerDescription'),
    status: t('app.common.roles.manager'),
    meta: t('app.organizations.roster.staffIlyaMeta'),
  },
  {
    name: t('app.organizations.roster.staffOlgaName'),
    description: t('app.organizations.roster.staffOlgaDescription'),
    status: t('app.common.roles.staff'),
    meta: t('app.organizations.roster.staffOlgaMeta'),
  },
  {
    name: t('app.organizations.roster.ownerName'),
    description: t('app.organizations.roster.ownerDescription'),
    status: t('app.common.roles.owner'),
    meta: t('app.organizations.roster.staffAnnaMeta'),
  },
]);
</script>
