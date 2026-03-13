<template>
  <section :class="styles.page" data-test="organizations-members-page">
    <OrganizationsPageSkeleton
      v-if="pageState === 'loading'"
      :cards="2"
      :list-items="4"
      data-test="organizations-members-loading"
    />

    <template v-else>
      <PageHero
        :eyebrow="t('app.organizations.members.eyebrow')"
        :title="t('app.organizations.members.title')"
        :description="t('app.organizations.members.description')"
      />

      <PrivateStateMessage
        v-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.organizations.members.emptyTitle')"
        :description="t('app.organizations.members.emptyDescription')"
        data-test="organizations-members-empty"
      />

      <PrivateStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.organizations.members.errorTitle')"
        :description="t('app.organizations.members.errorDescription')"
        data-test="organizations-members-error"
      />

      <template v-else>
        <div :class="styles.grid">
          <OrganizationsRosterSection
            :eyebrow="t('app.organizations.members.sections.activeEyebrow')"
            :title="t('app.organizations.members.sections.activeTitle')"
            :summary="
              canManageMembers
                ? t('app.organizations.members.sections.activeEditable')
                : t('app.organizations.members.sections.activeReadonly')
            "
            :items="members"
            data-test="organizations-members-active"
          />

          <OrganizationsRosterSection
            :eyebrow="t('app.organizations.members.sections.invitesEyebrow')"
            :title="t('app.organizations.members.sections.invitesTitle')"
            :summary="t('app.organizations.members.sections.invitesSummary')"
            :items="invites"
            data-test="organizations-members-invites"
          />
        </div>

        <OrganizationsRosterSection
          :eyebrow="t('app.organizations.members.sections.clientsEyebrow')"
          :title="t('app.organizations.members.sections.clientsTitle')"
          :summary="t('app.organizations.members.sections.clientsSummary')"
          :items="clients"
          data-test="organizations-members-clients"
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
  permission: 'org.members.read',
});

const { t } = useI18n();
const pageState = usePrivatePreviewState();
const { canManageMembers } = useOrganizationAccess();

const members = computed(() => [
  {
    name: t('app.organizations.roster.ownerName'),
    description: t('app.organizations.roster.ownerDescription'),
    status: t('app.common.roles.owner'),
    meta: t('app.organizations.roster.ownerMeta'),
  },
  {
    name: t('app.organizations.roster.managerName'),
    description: t('app.organizations.roster.managerDescription'),
    status: t('app.common.roles.manager'),
    meta: t('app.organizations.roster.managerMeta'),
  },
  {
    name: t('app.organizations.roster.memberName'),
    description: t('app.organizations.roster.memberDescription'),
    status: t('app.common.roles.member'),
    meta: t('app.organizations.roster.memberMeta'),
  },
]);

const invites = computed(() => [
  {
    name: t('app.organizations.roster.coachInviteName'),
    description: t('app.organizations.roster.coachInviteDescription'),
    status: t('app.common.statuses.pending'),
    meta: t('app.organizations.roster.coachInviteMeta'),
  },
  {
    name: t('app.organizations.roster.coordinatorInviteName'),
    description: t('app.organizations.roster.coordinatorInviteDescription'),
    status: t('app.common.statuses.review'),
    meta: t('app.organizations.roster.coordinatorInviteMeta'),
  },
  {
    name: t('app.organizations.roster.operatorInviteName'),
    description: t('app.organizations.roster.operatorInviteDescription'),
    status: t('app.common.statuses.pending'),
    meta: t('app.organizations.roster.operatorInviteMeta'),
  },
]);

const clients = computed(() => [
  {
    name: t('app.organizations.roster.clientAlexeyName'),
    description: t('app.organizations.roster.clientAlexeyDescription'),
    status: t('app.common.roles.user'),
    meta: t('app.organizations.roster.clientAlexeyMeta'),
  },
  {
    name: t('app.organizations.roster.clientEgorName'),
    description: t('app.organizations.roster.clientEgorDescription'),
    status: t('app.common.roles.child'),
    meta: t('app.organizations.roster.clientEgorMeta'),
  },
  {
    name: t('app.organizations.roster.clientDariaName'),
    description: t('app.organizations.roster.clientDariaDescription'),
    status: t('app.common.statuses.pending'),
    meta: t('app.organizations.roster.clientDariaMeta'),
  },
]);
</script>
