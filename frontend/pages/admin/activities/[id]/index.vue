<template>
  <section class="admin-show-page mx-auto w-full max-w-5xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.activities.show.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.activities.show.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <div v-if="loading" class="admin-page-skeleton space-y-3" aria-hidden="true">
        <div class="skeleton-line is-title" />
        <div class="grid gap-3 sm:grid-cols-2">
          <div v-for="index in 10" :key="index" class="space-y-2">
            <div class="skeleton-line is-label" />
            <div class="skeleton-line is-value" />
          </div>
        </div>
      </div>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="activity">
        <dl class="grid gap-3 sm:grid-cols-2">
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.name') }}</dt>
            <dd>{{ activity.name }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.slug') }}</dt>
            <dd class="font-mono text-sm">{{ activity.slug }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.organization') }}</dt>
            <dd>{{ activity.organization?.name || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.location') }}</dt>
            <dd>{{ resolveLocationLabel(activity) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.category') }}</dt>
            <dd>{{ resolveCategoryLabel(activity) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.status') }}</dt>
            <dd>{{ resolveStatusLabel(activity.status) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.minAge') }}</dt>
            <dd>{{ resolveNumber(activity.min_age) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.maxAge') }}</dt>
            <dd>{{ resolveNumber(activity.max_age) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.capacity') }}</dt>
            <dd>{{ resolveNumber(activity.capacity) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.isFeatured') }}</dt>
            <dd>
              {{
                activity.is_featured
                  ? t('admin.activities.labels.featuredYes')
                  : t('admin.activities.labels.featuredNo')
              }}
            </dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.priceFrom') }}</dt>
            <dd>{{ resolvePrice(activity.price_from, activity.currency) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.priceTo') }}</dt>
            <dd>{{ resolvePrice(activity.price_to, activity.currency) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.currency') }}</dt>
            <dd>{{ activity.currency || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.publishedAt') }}</dt>
            <dd>{{ resolvePublishedAt(activity.published_at) }}</dd>
          </div>
        </dl>

        <div class="mt-5 space-y-4">
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.shortDescription') }}</dt>
            <dd class="mt-1 whitespace-pre-line">{{ activity.short_description }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.activities.fields.description') }}</dt>
            <dd class="mt-1 whitespace-pre-line">{{ activity.description || t('common.dash') }}</dd>
          </div>
        </div>

        <div v-if="activity.schedules?.length" class="mt-5">
          <h3 class="text-sm font-semibold">{{ t('admin.activities.show.schedulesTitle') }}</h3>
          <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <article
              v-for="schedule in activity.schedules"
              :key="schedule.id"
              class="rounded-xl border border-[var(--admin-border)] p-3"
            >
              <p class="font-medium">{{ resolveWeekdayLabel(schedule.day_of_week) }}</p>
              <p class="admin-muted mt-1 text-xs">
                {{
                  t('admin.activities.show.scheduleTime', {
                    start: schedule.start_time,
                    end: schedule.end_time,
                  })
                }}
              </p>
            </article>
          </div>
        </div>

        <div v-if="activity.cover || activity.gallery?.length" class="mt-5 space-y-4">
          <div v-if="activity.cover">
            <h3 class="text-sm font-semibold">{{ t('admin.activities.show.coverTitle') }}</h3>
            <div class="mt-3 overflow-hidden rounded-xl border border-[var(--admin-border)]">
              <img
                :src="activity.cover.url"
                :alt="activity.cover.original_name || activity.name"
                class="h-56 w-full object-cover"
              />
            </div>
          </div>

          <div v-if="activity.gallery?.length">
            <h3 class="text-sm font-semibold">{{ t('admin.activities.show.galleryTitle') }}</h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <article
                v-for="file in activity.gallery"
                :key="file.id"
                class="overflow-hidden rounded-xl border border-[var(--admin-border)]"
              >
                <img
                  :src="file.url"
                  :alt="file.original_name || activity.name"
                  class="h-40 w-full object-cover"
                />
              </article>
            </div>
          </div>
        </div>

        <div class="mt-5 flex gap-2">
          <NuxtLink
            v-if="canWriteActivities"
            :to="`/admin/activities/${activity.id}/edit`"
            class="admin-button rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.edit') }}
          </NuxtLink>
          <NuxtLink
            to="/admin/activities"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.backToList') }}
          </NuxtLink>
        </div>
      </template>
    </article>

    <AdminChangeLogPanel
      v-if="canReadChangeLog"
      model="activity"
      :entity-id="String(route.params.id || '')"
      @rolled-back="fetchActivity"
    />

    <AdminActionLogPanel model="activity" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import AdminActionLogPanel from '~/components/admin/ActionLog/AdminActionLogPanel.vue';
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import type { AdminActivity } from '~/composables/useAdminActivities';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';

const { t, locale } = useI18n();
const route = useRoute();
const activitiesApi = useAdminActivities();
const { hasPermission } = usePermissions();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.activities.read',
});

const canWriteActivities = computed(() => hasPermission('admin.activities.update'));
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));

const activity = ref<AdminActivity | null>(null);
const loading = ref(false);
const loadError = ref('');

const weekdayLabels = computed<Record<number, string>>(() => ({
  1: locale.value === 'ru' ? 'Понедельник' : 'Monday',
  2: locale.value === 'ru' ? 'Вторник' : 'Tuesday',
  3: locale.value === 'ru' ? 'Среда' : 'Wednesday',
  4: locale.value === 'ru' ? 'Четверг' : 'Thursday',
  5: locale.value === 'ru' ? 'Пятница' : 'Friday',
  6: locale.value === 'ru' ? 'Суббота' : 'Saturday',
  7: locale.value === 'ru' ? 'Воскресенье' : 'Sunday',
}));

const resolveWeekdayLabel = (dayOfWeek: number): string => {
  return weekdayLabels.value[dayOfWeek] || String(dayOfWeek);
};

const resolveLocationLabel = (item: AdminActivity): string => {
  if (!item.location) {
    return t('common.dash');
  }

  const cityName = item.location.city?.name?.trim();
  const address = item.location.address?.trim();

  if (cityName && address) {
    return `${cityName}, ${address}`;
  }

  return cityName || address || t('common.dash');
};

const resolveCategoryLabel = (item: AdminActivity): string => {
  const category = item.primary_category;

  if (!category) {
    return t('common.dash');
  }

  return category.parent?.name ? `${category.parent.name} / ${category.name}` : category.name;
};

const resolveStatusLabel = (status: string): string => {
  const statusMap: Record<string, string> = {
    draft: t('admin.activities.status.draft'),
    pending_review: t('admin.activities.status.pendingReview'),
    published: t('admin.activities.status.published'),
    archived: t('admin.activities.status.archived'),
  };

  return statusMap[status] ?? status;
};

const resolvePublishedAt = (value?: string | null): string => {
  if (!value) {
    return t('common.dash');
  }

  return new Intl.DateTimeFormat(locale.value, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value));
};

const resolveNumber = (value?: number | null): string => {
  return typeof value === 'number' ? String(value) : t('common.dash');
};

const resolvePrice = (value?: number | null, currency?: string | null): string => {
  if (typeof value !== 'number') {
    return t('common.dash');
  }

  return currency ? `${value} ${currency}` : String(value);
};

const fetchActivity = async () => {
  const id = String(route.params.id || '');

  if (!id) {
    loadError.value = t('admin.activities.show.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    activity.value = await activitiesApi.show(id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.activities.show.errors.load'));
  } finally {
    loading.value = false;
  }
};

onMounted(fetchActivity);
</script>

<style lang="scss" scoped src="../../_shared/admin-show-page.scss"></style>
<style lang="scss" scoped>
.admin-page-skeleton {
  .skeleton-line {
    display: block;
    border-radius: 999px;
    background: linear-gradient(
      90deg,
      rgba(148, 163, 184, 0.12) 0%,
      rgba(148, 163, 184, 0.28) 50%,
      rgba(148, 163, 184, 0.12) 100%
    );
    background-size: 200% 100%;
    animation: admin-page-skeleton-shimmer 1.2s ease-in-out infinite;
  }

  .skeleton-line.is-title {
    width: 38%;
    height: 1.25rem;
  }

  .skeleton-line.is-label {
    width: 32%;
    height: 0.75rem;
  }

  .skeleton-line.is-value {
    width: 78%;
    height: 1rem;
  }
}

@keyframes admin-page-skeleton-shimmer {
  0% {
    background-position: 200% 0;
  }

  100% {
    background-position: -200% 0;
  }
}
</style>
