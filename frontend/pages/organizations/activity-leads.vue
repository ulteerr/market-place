<template>
  <section class="organizations-page" data-test="organizations-activity-leads-page">
    <OrganizationsPageSkeleton
      v-if="loading"
      :show-metrics="true"
      :cards="2"
      :list-items="4"
      data-test="organizations-activity-leads-loading"
    />

    <template v-else>
      <PageHero
        :eyebrow="t('app.organizations.activityLeads.eyebrow')"
        :title="t('app.organizations.activityLeads.title')"
        :description="t('app.organizations.activityLeads.description')"
      />

      <PrivateStateMessage
        v-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.organizations.activityLeads.emptyTitle')"
        :description="t('app.organizations.activityLeads.emptyDescription')"
        data-test="organizations-activity-leads-empty"
      />

      <PrivateStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.organizations.activityLeads.errorTitle')"
        :description="errorMessage || t('app.organizations.activityLeads.errorDescription')"
        data-test="organizations-activity-leads-error"
      />

      <template v-else>
        <div class="metrics" data-test="organizations-activity-leads-metrics">
          <UiCard v-for="metric in metrics" :key="metric.label" padding="md">
            <p class="metric-label">{{ metric.label }}</p>
            <p class="metric-value">{{ metric.value }}</p>
            <p class="metric-caption">{{ metric.caption }}</p>
          </UiCard>
        </div>

        <div class="grid">
          <UiCard padding="lg" data-test="organizations-activity-leads-inbox">
            <template #header>
              <div class="section-header">
                <div>
                  <p class="eyebrow">
                    {{ t('app.organizations.activityLeads.sections.inboxEyebrow') }}
                  </p>
                  <h2 class="section-title">
                    {{ t('app.organizations.activityLeads.sections.inboxTitle') }}
                  </h2>
                </div>
                <span class="section-meta">
                  {{
                    canReviewActivityLeads
                      ? t('app.organizations.activityLeads.sections.reviewEnabled')
                      : t('app.organizations.activityLeads.sections.reviewReadonly')
                  }}
                </span>
              </div>
            </template>

            <ul class="stack">
              <li v-for="lead in inboxLeads" :key="lead.id" class="stack-item">
                <div>
                  <p class="item-title">{{ resolveSubjectLabel(lead) }}</p>
                  <p class="item-text">
                    {{ lead.activity?.name || t('common.dash') }} · {{ resolveChannelsLabel(lead) }}
                  </p>
                  <p class="note">{{ lead.message || t('common.dash') }}</p>
                </div>
                <div class="meta-column">
                  <span class="badge">{{ resolveStatusLabel(lead.status) }}</span>
                  <span class="item-meta">{{
                    resolveRequestTypeLabel(lead.request_for_type)
                  }}</span>
                  <span class="item-meta">{{ formatDate(lead.created_at) }}</span>
                </div>
                <div v-if="canReviewActivityLeads" class="action-row">
                  <div :data-test="`organizations-activity-lead-status-${lead.id}`">
                    <UiSelect
                      :model-value="statusDrafts[lead.id] ?? lead.status"
                      :options="statusOptions"
                      :disabled="updatingLeadId === lead.id"
                      @update:model-value="onDraftStatusChange(lead.id, $event)"
                    />
                  </div>
                  <button
                    type="button"
                    class="action-button"
                    :data-test="`organizations-activity-lead-save-${lead.id}`"
                    :disabled="updatingLeadId === lead.id"
                    @click="saveStatus(lead)"
                  >
                    {{
                      updatingLeadId === lead.id
                        ? t('common.loading')
                        : t('app.organizations.activityLeads.actions.saveStatus')
                    }}
                  </button>
                </div>
              </li>
            </ul>
          </UiCard>

          <UiCard padding="lg" data-test="organizations-activity-leads-history">
            <template #header>
              <div class="section-header">
                <div>
                  <p class="eyebrow">
                    {{ t('app.organizations.activityLeads.sections.historyEyebrow') }}
                  </p>
                  <h2 class="section-title">
                    {{ t('app.organizations.activityLeads.sections.historyTitle') }}
                  </h2>
                </div>
                <span class="section-meta">{{
                  t('app.organizations.activityLeads.sections.historyMeta')
                }}</span>
              </div>
            </template>

            <ul class="stack">
              <li v-for="lead in historyLeads" :key="lead.id" class="stack-item">
                <div>
                  <p class="item-title">{{ resolveSubjectLabel(lead) }}</p>
                  <p class="item-text">{{ lead.activity?.name || t('common.dash') }}</p>
                  <p class="note">{{ resolveChannelsLabel(lead) }}</p>
                </div>
                <div class="meta-column">
                  <span class="badge">{{ resolveStatusLabel(lead.status) }}</span>
                  <span class="item-meta">{{
                    resolveRequestTypeLabel(lead.request_for_type)
                  }}</span>
                  <span class="item-meta">{{
                    formatDate(lead.updated_at || lead.created_at)
                  }}</span>
                </div>
              </li>
            </ul>
          </UiCard>
        </div>
      </template>
    </template>
  </section>
</template>

<script setup lang="ts">
import OrganizationsPageSkeleton from '~/components/organizations/OrganizationsPageSkeleton/OrganizationsPageSkeleton.vue';
import PrivateStateMessage from '~/components/private/PrivateStateMessage/PrivateStateMessage.vue';
import UiCard from '~/components/ui/Card/UiCard.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import PageHero from '~/components/ui/PageHero/PageHero.vue';
import type {
  ActivityLead,
  ActivityLeadRequestType,
  ActivityLeadStatus,
} from '~/composables/useActivityLeads';
import {
  resolveActivityLeadChannelsLabel,
  resolveActivityLeadSubjectLabel,
  useActivityLeads,
} from '~/composables/useActivityLeads';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';
import { useOrganizationAccess } from '~/composables/useOrganizationAccess';

definePageMeta({
  layout: 'organizations',
  middleware: 'organizations-permission',
  permission: 'org.activity-leads.read',
});

const { t, locale } = useI18n();
const route = useRoute();
const activityLeadsApi = useActivityLeads();
const { canReviewActivityLeads } = useOrganizationAccess();

const leads = ref<ActivityLead[]>([]);
const loading = ref(true);
const errorMessage = ref('');
const updatingLeadId = ref<string | null>(null);
const statusDrafts = reactive<Record<string, ActivityLeadStatus>>({});

const organizationId = computed(() => {
  const raw = route.query.organization_id;
  return Array.isArray(raw) ? raw[0] || '' : String(raw || '');
});

const pageState = computed<'ready' | 'empty' | 'error'>(() => {
  if (errorMessage.value) {
    return 'error';
  }

  if (!leads.value.length) {
    return 'empty';
  }

  return 'ready';
});

const inboxLeads = computed(() =>
  leads.value.filter((lead) => !['registered', 'cancelled'].includes(lead.status))
);
const historyLeads = computed(() =>
  leads.value.filter((lead) => ['registered', 'cancelled'].includes(lead.status))
);

const metrics = computed(() => [
  {
    label: t('app.organizations.activityLeads.filters.totalLabel'),
    value: String(leads.value.length),
    caption: t('app.organizations.activityLeads.filters.totalCaption'),
  },
  {
    label: t('app.organizations.activityLeads.filters.newLabel'),
    value: String(leads.value.filter((lead) => lead.status === 'new').length),
    caption: t('app.organizations.activityLeads.filters.newCaption'),
  },
  {
    label: t('app.organizations.activityLeads.filters.activeLabel'),
    value: String(inboxLeads.value.length),
    caption: t('app.organizations.activityLeads.filters.activeCaption'),
  },
]);

const statusOptions = computed(() => [
  { value: 'new', label: t('app.organizations.activityLeads.status.new') },
  { value: 'in_progress', label: t('app.organizations.activityLeads.status.inProgress') },
  { value: 'contacted', label: t('app.organizations.activityLeads.status.contacted') },
  { value: 'registered', label: t('app.organizations.activityLeads.status.registered') },
  { value: 'cancelled', label: t('app.organizations.activityLeads.status.cancelled') },
]);

const loadLeads = async () => {
  loading.value = true;
  errorMessage.value = '';

  if (!organizationId.value) {
    leads.value = [];
    errorMessage.value = t('app.organizations.activityLeads.scopeError');
    loading.value = false;
    return;
  }

  try {
    const response = await activityLeadsApi.organizationList(organizationId.value, {
      per_page: 20,
      sort_by: 'created_at',
      sort_dir: 'desc',
    });

    leads.value = response.data;
    response.data.forEach((lead) => {
      statusDrafts[lead.id] = lead.status;
    });
  } catch (error) {
    errorMessage.value = getApiErrorMessage(
      error,
      t('app.organizations.activityLeads.errorDescription')
    );
  } finally {
    loading.value = false;
  }
};

const onDraftStatusChange = (leadId: string, value: string | number | (string | number)[]) => {
  const nextValue = Array.isArray(value) ? value[0] : value;
  if (
    nextValue === 'new' ||
    nextValue === 'in_progress' ||
    nextValue === 'contacted' ||
    nextValue === 'registered' ||
    nextValue === 'cancelled'
  ) {
    statusDrafts[leadId] = nextValue;
  }
};

const saveStatus = async (lead: ActivityLead) => {
  const nextStatus = statusDrafts[lead.id] ?? lead.status;
  if (!organizationId.value || nextStatus === lead.status) {
    return;
  }

  updatingLeadId.value = lead.id;
  try {
    await activityLeadsApi.organizationUpdateStatus(organizationId.value, lead.id, nextStatus);
    await loadLeads();
  } catch (error) {
    errorMessage.value = getApiErrorMessage(
      error,
      t('app.organizations.activityLeads.updateError')
    );
  } finally {
    updatingLeadId.value = null;
  }
};

const formatDate = (value: string | null | undefined): string => {
  if (!value) {
    return t('common.dash');
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(parsed);
};

const resolveSubjectLabel = (lead: ActivityLead): string => resolveActivityLeadSubjectLabel(lead);
const resolveChannelsLabel = (lead: ActivityLead): string => resolveActivityLeadChannelsLabel(lead);
const resolveStatusLabel = (status: ActivityLeadStatus): string =>
  t(`app.organizations.activityLeads.status.${status === 'in_progress' ? 'inProgress' : status}`);
const resolveRequestTypeLabel = (type: ActivityLeadRequestType): string =>
  t(`app.organizations.activityLeads.requestType.${type}`);

onMounted(() => {
  void loadLeads();
});
</script>

<style lang="scss" scoped>
.organizations-page {
  display: grid;
  gap: 1.5rem;
}

.metrics {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}

.metric-label,
.metric-caption,
.eyebrow,
.section-meta,
.item-text,
.note,
.item-meta {
  color: var(--muted);
}

.metric-value {
  font-size: 1.75rem;
  font-weight: 700;
}

.grid {
  display: grid;
  gap: 1rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  flex-wrap: wrap;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
}

.stack {
  display: grid;
  gap: 0.75rem;
}

.stack-item {
  display: grid;
  gap: 0.75rem;
  padding: 1rem;
  border: 1px solid var(--border);
  border-radius: 1rem;
}

.stack-item > * {
  min-width: 0;
}

.item-title {
  font-weight: 600;
}

.item-title,
.item-text,
.note,
.item-meta {
  overflow-wrap: anywhere;
}

.meta-column {
  display: grid;
  gap: 0.25rem;
}

.badge {
  display: inline-flex;
  width: fit-content;
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 0.2rem 0.55rem;
}

.action-row {
  display: grid;
  gap: 0.75rem;
  min-width: 0;
}

.action-button {
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 0.65rem 1rem;
  font-weight: 600;
  width: 100%;
}

@media (min-width: 960px) {
  .grid {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
