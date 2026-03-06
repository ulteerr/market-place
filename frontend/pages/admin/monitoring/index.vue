<template>
  <section class="space-y-6">
    <header class="space-y-2">
      <h1 class="text-2xl font-semibold">{{ t('admin.monitoring.title') }}</h1>
      <p class="admin-muted text-sm">{{ t('admin.monitoring.subtitle') }}</p>
    </header>

    <div class="grid gap-3 md:grid-cols-4">
      <div class="rounded-xl border border-[var(--border)] bg-[var(--surface)] p-4">
        <p class="admin-muted text-xs">{{ t('admin.monitoring.cards.eventsTotal') }}</p>
        <p class="mt-2 text-2xl font-semibold">{{ totalEvents }}</p>
      </div>
      <div class="rounded-xl border border-[var(--border)] bg-[var(--surface)] p-4">
        <p class="admin-muted text-xs">{{ t('admin.monitoring.cards.errorsTotal') }}</p>
        <p class="mt-2 text-2xl font-semibold">{{ totalErrors }}</p>
      </div>
      <div class="rounded-xl border border-[var(--border)] bg-[var(--surface)] p-4">
        <p class="admin-muted text-xs">{{ t('admin.monitoring.cards.avgDuration') }}</p>
        <p class="mt-2 text-2xl font-semibold">{{ averageDurationMs }}</p>
      </div>
      <div class="rounded-xl border border-[var(--border)] bg-[var(--surface)] p-4">
        <p class="admin-muted text-xs">{{ t('admin.monitoring.cards.lastEventAt') }}</p>
        <p class="mt-2 text-sm font-medium">{{ lastEventAtLabel }}</p>
      </div>
    </div>

    <div class="rounded-xl border border-[var(--border)] bg-[var(--surface)] p-4">
      <div class="grid gap-3 md:grid-cols-[1fr_180px_auto]">
        <UiSelect
          :model-value="selectedDomain"
          :label="t('admin.monitoring.filters.domain')"
          :options="domainOptions"
          :searchable="false"
          :placeholder="t('admin.monitoring.filters.allDomains')"
          @update:model-value="onDomainChange"
        />

        <UiInput
          :model-value="String(incidentsLimit)"
          preset="number"
          :label="t('admin.monitoring.filters.incidentsLimit')"
          @update:model-value="onLimitChange"
        />

        <div class="flex items-end">
          <button
            type="button"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="loading"
            @click="loadDashboard"
          >
            {{ t('admin.monitoring.filters.refresh') }}
          </button>
        </div>
      </div>
      <p v-if="loadError" class="mt-3 text-sm text-red-600">{{ loadError }}</p>
    </div>

    <div class="rounded-xl border border-[var(--border)] bg-[var(--surface)] p-4">
      <h2 class="text-lg font-semibold">{{ t('admin.monitoring.incidents.title') }}</h2>

      <div class="mt-3 overflow-x-auto rounded-xl border border-[var(--border)]">
        <table class="admin-table min-w-[880px]">
          <thead>
            <tr>
              <th>{{ t('admin.monitoring.incidents.headers.time') }}</th>
              <th>{{ t('admin.monitoring.incidents.headers.domain') }}</th>
              <th>{{ t('admin.monitoring.incidents.headers.event') }}</th>
              <th>{{ t('admin.monitoring.incidents.headers.status') }}</th>
              <th>{{ t('admin.monitoring.incidents.headers.severity') }}</th>
              <th>{{ t('admin.monitoring.incidents.headers.component') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="admin-muted py-4 text-center text-sm">
                {{ t('common.loading') }}
              </td>
            </tr>
            <tr v-else-if="!incidents.length">
              <td colspan="6" class="admin-muted py-4 text-center text-sm">
                {{ t('admin.monitoring.incidents.empty') }}
              </td>
            </tr>
            <tr
              v-for="incident in incidents"
              :key="incident.timestamp + incident.event + incident.component"
            >
              <td class="text-xs">{{ formatTimestamp(incident.timestamp) }}</td>
              <td class="font-mono text-xs">{{ incident.domain }}</td>
              <td>{{ incident.event }}</td>
              <td>{{ incident.status }}</td>
              <td>{{ incident.severity }}</td>
              <td class="text-xs">{{ incident.component }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="rounded-xl border border-[var(--border)] bg-[var(--surface)] p-4">
      <h2 class="text-lg font-semibold">{{ t('admin.monitoring.alerts.title') }}</h2>
      <div class="mt-3 space-y-2">
        <p v-if="!alerts.length" class="admin-muted text-sm">
          {{ t('admin.monitoring.alerts.empty') }}
        </p>
        <div
          v-for="alert in alerts"
          :key="`${alert.code}:${alert.domain}`"
          class="rounded-lg border border-amber-400/40 bg-amber-500/10 p-3"
        >
          <p class="text-sm font-medium">{{ alert.message }}</p>
          <p class="admin-muted mt-1 text-xs">
            {{ alert.domain }} · {{ alert.code }} · {{ alert.errors_total }}/{{
              alert.events_total
            }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { ObservabilityDashboardPayload } from '~/composables/useAdminObservability';
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.monitoring.read',
});

const { t, locale } = useI18n();
const api = useAdminObservability();

const dashboard = ref<ObservabilityDashboardPayload | null>(null);
const loading = ref(false);
const loadError = ref('');
const selectedDomain = ref('');
const incidentsLimit = ref(50);

const availableDomains = computed(() => Object.keys(dashboard.value?.summary.domains ?? {}));
const domainOptions = computed(() =>
  availableDomains.value.map((domain) => ({
    value: domain,
    label: domain,
  }))
);
const incidents = computed(() => dashboard.value?.incidents ?? []);
const alerts = computed(() => dashboard.value?.alerts ?? []);

const totalEvents = computed(() => {
  const domains = dashboard.value?.summary.domains ?? {};

  return Object.values(domains).reduce((sum, domain) => sum + (domain.events_total || 0), 0);
});

const totalErrors = computed(() => {
  const domains = dashboard.value?.summary.domains ?? {};

  return Object.values(domains).reduce((sum, domain) => sum + (domain.errors_total || 0), 0);
});

const averageDurationMs = computed(() => {
  const domains = dashboard.value?.summary.domains ?? {};

  const totals = Object.values(domains).reduce(
    (acc, domain) => {
      acc.total += domain.duration_total_ms || 0;
      acc.count += domain.duration_count || 0;
      return acc;
    },
    { total: 0, count: 0 }
  );

  if (totals.count === 0) {
    return 0;
  }

  return Math.round(totals.total / totals.count);
});

const lastEventAtLabel = computed(() => {
  const domains = dashboard.value?.summary.domains ?? {};
  const timestamps = Object.values(domains)
    .map((domain) => domain.last_event_at)
    .filter((value): value is string => Boolean(value))
    .sort()
    .reverse();

  return timestamps[0] ? formatTimestamp(timestamps[0]) : t('common.dash');
});

const formatTimestamp = (value: string): string => {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return t('common.dash');
  }

  return new Intl.DateTimeFormat(locale.value, {
    dateStyle: 'short',
    timeStyle: 'medium',
  }).format(date);
};

const loadDashboard = async () => {
  loading.value = true;
  loadError.value = '';

  try {
    dashboard.value = await api.getDashboard({
      domain: selectedDomain.value || null,
      limit: incidentsLimit.value,
    });
  } catch {
    loadError.value = t('admin.monitoring.errors.load');
  } finally {
    loading.value = false;
  }
};

const onDomainChange = (value: string | number | (string | number)[]) => {
  const next = Array.isArray(value) ? value[0] : value;
  selectedDomain.value = typeof next === 'string' ? next : '';
};

const onLimitChange = (value: string | number | null) => {
  const numeric = Number(value);
  if (Number.isNaN(numeric)) {
    incidentsLimit.value = 50;
    return;
  }

  incidentsLimit.value = Math.max(1, Math.min(200, Math.trunc(numeric)));
};

onMounted(() => {
  void loadDashboard();
});

watch(selectedDomain, () => {
  void loadDashboard();
});
</script>
