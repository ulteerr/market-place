<template>
  <div>
    <PublicSection data-test="public-activity-section">
      <UiCardSkeleton v-if="pageState === 'loading'" data-test="public-activity-loading" />
      <PublicStateMessage
        v-else-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.activity.emptyTitle')"
        :description="t('app.public.catalog.activity.emptyDescription')"
        data-test="public-activity-empty"
      />
      <PublicStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.activity.errorTitle')"
        :description="t('app.public.catalog.activity.errorDescription')"
        data-test="public-activity-error"
      />
      <div v-else class="activity-page__stack">
        <div class="activity-page__top" data-test="public-activity-top">
          <div class="activity-page__media-column">
            <UiSwiper
              v-if="mediaItems.length"
              :items="mediaItems"
              :prev-label="t('app.public.catalog.activity.gallery.prev')"
              :next-label="t('app.public.catalog.activity.gallery.next')"
              :thumb-label="t('app.public.catalog.activity.gallery.thumb')"
              data-test="public-activity-swiper"
            />
            <UiCard v-else variant="default" padding="lg" data-test="public-activity-media-empty">
              <p class="activity-page__media-empty">
                {{ t('app.public.catalog.activity.gallery.empty') }}
              </p>
            </UiCard>
          </div>

          <div class="activity-page__content-column">
            <UiCard variant="default" padding="lg" data-test="public-activity-summary">
              <div class="activity-page__summary">
                <p class="activity-page__eyebrow">{{ heroEyebrow }}</p>
                <h1 class="activity-page__title">{{ heroTitle }}</h1>
                <p class="activity-page__price">{{ priceLabel }}</p>
                <p class="activity-page__lead">{{ heroDescription }}</p>
                <p v-if="activity?.description" class="activity-page__description">
                  {{ activity.description }}
                </p>
              </div>
            </UiCard>

            <UiCard variant="default" padding="lg" data-test="public-activity-details">
              <h2 class="activity-page__heading">
                {{ t('app.public.catalog.activity.detailsTitle') }}
              </h2>
              <dl class="activity-page__details-grid">
                <div>
                  <dt>{{ t('app.public.catalog.activity.fields.organization') }}</dt>
                  <dd>{{ activity?.organization?.name || t('common.dash') }}</dd>
                </div>
                <div>
                  <dt>{{ t('app.public.catalog.activity.fields.category') }}</dt>
                  <dd>{{ categoryLabel }}</dd>
                </div>
                <div>
                  <dt>{{ t('app.public.catalog.activity.fields.location') }}</dt>
                  <dd>{{ locationLabel }}</dd>
                </div>
                <div>
                  <dt>{{ t('app.public.catalog.activity.fields.age') }}</dt>
                  <dd>{{ ageLabel }}</dd>
                </div>
                <div>
                  <dt>{{ t('app.public.catalog.activity.fields.price') }}</dt>
                  <dd>{{ priceLabel }}</dd>
                </div>
                <div>
                  <dt>{{ t('app.public.catalog.activity.fields.scheduleCount') }}</dt>
                  <dd>{{ scheduleCountLabel }}</dd>
                </div>
              </dl>
            </UiCard>
          </div>
        </div>

        <UiCard
          v-if="activity?.schedules?.length"
          variant="default"
          padding="lg"
          data-test="public-activity-schedules"
        >
          <h2 class="activity-page__heading">
            {{ t('app.public.catalog.activity.scheduleTitle') }}
          </h2>
          <ul class="activity-page__schedule-list">
            <li
              v-for="schedule in activity?.schedules || []"
              :key="schedule.id"
              class="activity-page__schedule-item"
            >
              <span>{{ resolveWeekdayLabel(schedule.day_of_week) }}</span>
              <strong>{{ formatTimeRange(schedule.start_time, schedule.end_time) }}</strong>
            </li>
          </ul>
        </UiCard>

        <UiCard variant="default" padding="lg" data-test="public-activity-lead-card">
          <div class="activity-page__lead-card-header">
            <div>
              <h2 class="activity-page__heading">
                {{ t('app.public.catalog.activity.lead.title') }}
              </h2>
              <p class="activity-page__lead-card-text">
                {{ t('app.public.catalog.activity.lead.description') }}
              </p>
            </div>
          </div>

          <div
            v-if="!isAuthenticated"
            class="activity-page__lead-guest"
            data-test="public-activity-lead-guest"
          >
            <p class="activity-page__lead-card-text">
              {{ t('app.public.catalog.activity.lead.guestDescription') }}
            </p>
            <NuxtLink to="/login" class="activity-page__lead-button">
              {{ t('app.public.catalog.activity.lead.actions.login') }}
            </NuxtLink>
          </div>

          <div v-else>
            <p
              v-if="leadSubmitState === 'success'"
              class="activity-page__lead-success"
              data-test="public-activity-lead-success"
            >
              {{ t('app.public.catalog.activity.lead.success') }}
            </p>
            <p
              v-else-if="leadError"
              class="activity-page__lead-error"
              data-test="public-activity-lead-error"
            >
              {{ leadError }}
            </p>

            <form class="activity-page__lead-form" @submit.prevent="submitLead">
              <UiSelect
                :model-value="leadForm.request_for_type"
                :label="t('app.public.catalog.activity.lead.fields.requestType')"
                :options="requestTypeOptions"
                :error="leadValidation.request_for_type"
                data-test="public-activity-lead-request-type"
                @update:model-value="onRequestTypeChange"
              />

              <UiSelect
                v-if="leadForm.request_for_type === 'child'"
                :model-value="leadForm.child_id"
                :label="t('app.public.catalog.activity.lead.fields.child')"
                :options="childOptions"
                :placeholder="t('app.public.catalog.activity.lead.fields.childPlaceholder')"
                :error="leadValidation.child_id"
                data-test="public-activity-lead-child"
                @update:model-value="leadForm.child_id = $event"
              />

              <div class="activity-page__lead-channels">
                <p class="activity-page__lead-section-title">
                  {{ t('app.public.catalog.activity.lead.fields.channels') }}
                </p>
                <div class="activity-page__lead-checkbox-grid">
                  <UiCheckbox
                    v-for="channel in contactChannelOptions"
                    :key="channel.value"
                    :model-value="leadForm.contact_channels.includes(channel.value)"
                    :label="channel.label"
                    :description="channel.description"
                    @update:model-value="toggleChannel(channel.value, $event)"
                  />
                </div>
                <p v-if="leadValidation.contact_channels" class="activity-page__lead-error">
                  {{ leadValidation.contact_channels }}
                </p>
              </div>

              <div v-if="showPhoneField" class="activity-page__lead-grid">
                <UiInput
                  v-model="leadForm.contact_payload.phone"
                  preset="phone"
                  :label="t('app.public.catalog.activity.lead.fields.phone')"
                  :placeholder="t('app.public.catalog.activity.lead.fields.phonePlaceholder')"
                />
              </div>

              <div class="activity-page__lead-grid">
                <UiInput
                  v-if="showTelegramField"
                  v-model="leadForm.contact_payload.telegram"
                  :label="t('app.public.catalog.activity.lead.fields.telegram')"
                  :placeholder="t('app.public.catalog.activity.lead.fields.telegramPlaceholder')"
                />
                <UiInput
                  v-if="showWhatsappField"
                  v-model="leadForm.contact_payload.whatsapp"
                  preset="phone"
                  :label="t('app.public.catalog.activity.lead.fields.whatsapp')"
                  :placeholder="t('app.public.catalog.activity.lead.fields.whatsappPlaceholder')"
                />
                <UiInput
                  v-if="showMaxField"
                  v-model="leadForm.contact_payload.max"
                  :label="t('app.public.catalog.activity.lead.fields.max')"
                  :placeholder="t('app.public.catalog.activity.lead.fields.maxPlaceholder')"
                />
              </div>

              <UiTextarea
                v-model="leadForm.message"
                :label="t('app.public.catalog.activity.lead.fields.message')"
                :placeholder="t('app.public.catalog.activity.lead.fields.messagePlaceholder')"
                :hint="t('app.public.catalog.activity.lead.fields.messageHint')"
                :rows="4"
              />

              <div class="activity-page__lead-actions">
                <button
                  type="submit"
                  class="activity-page__lead-button"
                  :disabled="leadSubmitting || leadLoadingChildren"
                  data-test="public-activity-lead-submit"
                >
                  {{
                    leadSubmitting
                      ? t('app.public.catalog.activity.lead.actions.submitting')
                      : t('app.public.catalog.activity.lead.actions.submit')
                  }}
                </button>
              </div>
            </form>
          </div>
        </UiCard>

        <PublicActionLinks :links="links" data-test="public-activity-actions" />
      </div>
    </PublicSection>
  </div>
</template>

<script setup lang="ts">
import type { UiSwiperItem } from '~/components/ui/Swiper/UiSwiper.vue';
import { usePublicPreviewState } from '~/composables/layout/usePublicPreviewState';
import { getApiErrorMessage } from '~/composables/useAdminCrudCommon';
import { buildPublicActivityPath, usePublicActivities } from '~/composables/usePublicActivities';
import type { ActivityLeadChild, ActivityLeadContactChannel } from '~/composables/useActivityLeads';
import { useActivityLeads } from '~/composables/useActivityLeads';
import { usePublicPageSeo } from '~/composables/seo/usePublicPageSeo';
import { buildPublicActivitySchemaNodes } from '~/composables/schema/public-activity-schema';
import { usePublicSchemaNode } from '~/composables/schema/usePublicSchemaRegistry';
import PublicActionLinks from '~/components/public/PublicActionLinks/PublicActionLinks.vue';
import PublicSection from '~/components/public/PublicSection/PublicSection.vue';
import PublicStateMessage from '~/components/public/PublicStateMessage/PublicStateMessage.vue';
import UiCard from '~/components/ui/Card/UiCard.vue';
import UiCheckbox from '~/components/ui/FormControls/UiCheckbox/UiCheckbox.vue';
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import UiTextarea from '~/components/ui/FormControls/UiTextarea/UiTextarea.vue';
import UiCardSkeleton from '~/components/ui/Skeleton/UiCardSkeleton.vue';
import UiSwiper from '~/components/ui/Swiper/UiSwiper.vue';

definePageMeta({
  layout: 'default',
});

const { t } = useI18n();
const route = useRoute();
const config = useRuntimeConfig();
const previewState = usePublicPreviewState();
const publicActivitiesApi = usePublicActivities();
const activityLeadsApi = useActivityLeads();
const { isAuthenticated, user } = useAuth();

const publicKey = computed(() => String(route.params.activity || ''));
const activity = ref<Awaited<ReturnType<typeof publicActivitiesApi.show>> | null>(null);
const activityError = ref<unknown>(null);
const activityPending = ref(true);
const leadChildren = ref<ActivityLeadChild[]>([]);
const leadLoadingChildren = ref(false);
const leadSubmitting = ref(false);
const leadError = ref('');
const leadSubmitState = ref<'idle' | 'success'>('idle');
const leadValidation = reactive<Record<string, string>>({
  request_for_type: '',
  child_id: '',
  contact_channels: '',
});
const leadForm = reactive<{
  request_for_type: 'self' | 'child';
  child_id: string;
  contact_channels: ActivityLeadContactChannel[];
  contact_payload: {
    phone: string;
    telegram: string;
    whatsapp: string;
    max: string;
  };
  message: string;
}>({
  request_for_type: 'self',
  child_id: '',
  contact_channels: ['phone'],
  contact_payload: {
    phone: '',
    telegram: '',
    whatsapp: '',
    max: '',
  },
  message: '',
});
const canonicalPath = computed(() => {
  if (!activity.value) {
    return route.path;
  }

  return buildPublicActivityPath({
    public_key: publicKey.value,
    primary_category: activity.value.primary_category,
  });
});

const pageState = computed<'loading' | 'ready' | 'empty' | 'error'>(() => {
  if (previewState.value === 'loading') {
    return 'loading';
  }

  if (previewState.value === 'error') {
    return 'error';
  }

  if (previewState.value === 'empty') {
    return 'empty';
  }

  if (activityPending.value) {
    return 'loading';
  }

  if (activityError.value) {
    return 'error';
  }

  if (!activity.value) {
    return 'empty';
  }

  return 'ready';
});

const heroEyebrow = computed(() => {
  const rootName = activity.value?.primary_category?.parent?.name;
  const leafName = activity.value?.primary_category?.name;

  if (rootName && leafName) {
    return `${rootName} / ${leafName}`;
  }

  return t('app.public.catalog.eyebrow');
});
const heroTitle = computed(
  () => activity.value?.name || t('app.public.catalog.activity.fallbackTitle')
);
const heroDescription = computed(
  () => activity.value?.short_description || t('app.public.catalog.activity.fallbackDescription')
);

usePublicPageSeo({
  h1: heroTitle,
  title: computed(() =>
    activity.value?.name
      ? t('app.public.catalog.activity.seoTitle', { title: activity.value.name })
      : t('app.public.catalog.seoTitle')
  ),
  description: computed(
    () =>
      activity.value?.description ||
      activity.value?.short_description ||
      t('app.public.catalog.activity.fallbackDescription')
  ),
  image: computed(
    () => activity.value?.cover?.url || activity.value?.gallery?.[0]?.url || undefined
  ),
  canonicalPath,
});

const mediaItems = computed<UiSwiperItem[]>(() => {
  const items: UiSwiperItem[] = [];

  if (activity.value?.cover?.url) {
    items.push({
      id: activity.value.cover.id,
      src: activity.value.cover.url,
      alt: activity.value.name,
    });
  }

  for (const file of activity.value?.gallery || []) {
    if (items.some((item) => item.id === file.id)) {
      continue;
    }

    items.push({
      id: file.id,
      src: file.url,
      alt: activity.value?.name,
    });
  }

  return items;
});

const categoryLabel = computed(() => {
  const rootName = activity.value?.primary_category?.parent?.name;
  const leafName = activity.value?.primary_category?.name;

  if (rootName && leafName) {
    return `${rootName} / ${leafName}`;
  }

  return leafName || t('common.dash');
});

const locationLabel = computed(() => {
  const cityName = activity.value?.location?.city?.name;
  const address = activity.value?.location?.address;

  if (cityName && address) {
    return `${cityName}, ${address}`;
  }

  return cityName || address || t('common.dash');
});

const ageLabel = computed(() => {
  const minAge = activity.value?.min_age;
  const maxAge = activity.value?.max_age;

  if (minAge != null && maxAge != null) {
    return t('app.public.catalog.activity.ageRange', { min: minAge, max: maxAge });
  }

  if (minAge != null) {
    return t('app.public.catalog.activity.ageFrom', { min: minAge });
  }

  if (maxAge != null) {
    return t('app.public.catalog.activity.ageTo', { max: maxAge });
  }

  return t('common.dash');
});

const priceLabel = computed(() => {
  const priceFrom = activity.value?.price_from;
  const priceTo = activity.value?.price_to;
  const currency = activity.value?.currency;

  if (priceFrom != null && priceTo != null) {
    return t('app.public.catalog.activity.priceRange', {
      from: priceFrom,
      to: priceTo,
      currency: currency || '',
    }).trim();
  }

  if (priceFrom != null) {
    return t('app.public.catalog.activity.priceFrom', {
      from: priceFrom,
      currency: currency || '',
    }).trim();
  }

  if (priceTo != null) {
    return t('app.public.catalog.activity.priceTo', {
      to: priceTo,
      currency: currency || '',
    }).trim();
  }

  return t('app.public.catalog.activity.priceUnavailable');
});

const scheduleCountLabel = computed(() => {
  const count = activity.value?.schedules?.length || 0;

  return t('app.public.catalog.activity.scheduleCountValue', { count });
});

const requestTypeOptions = computed(() => [
  { value: 'self', label: t('app.public.catalog.activity.lead.requestType.self') },
  { value: 'child', label: t('app.public.catalog.activity.lead.requestType.child') },
]);

const contactChannelOptions = computed(() => [
  {
    value: 'chat' as const,
    label: t('app.public.catalog.activity.lead.channels.chat'),
    description: t('app.public.catalog.activity.lead.channels.chatDescription'),
  },
  {
    value: 'phone' as const,
    label: t('app.public.catalog.activity.lead.channels.phone'),
    description: t('app.public.catalog.activity.lead.channels.phoneDescription'),
  },
  {
    value: 'telegram' as const,
    label: t('app.public.catalog.activity.lead.channels.telegram'),
    description: t('app.public.catalog.activity.lead.channels.telegramDescription'),
  },
  {
    value: 'whatsapp' as const,
    label: t('app.public.catalog.activity.lead.channels.whatsapp'),
    description: t('app.public.catalog.activity.lead.channels.whatsappDescription'),
  },
  {
    value: 'max' as const,
    label: t('app.public.catalog.activity.lead.channels.max'),
    description: t('app.public.catalog.activity.lead.channels.maxDescription'),
  },
]);

const childOptions = computed(() =>
  leadChildren.value.map((child) => ({
    value: child.id,
    label: [child.last_name, child.first_name, child.middle_name]
      .filter((value): value is string => typeof value === 'string' && value.length > 0)
      .join(' '),
  }))
);

const showPhoneField = computed(() => leadForm.contact_channels.includes('phone'));
const showTelegramField = computed(() => leadForm.contact_channels.includes('telegram'));
const showWhatsappField = computed(() => leadForm.contact_channels.includes('whatsapp'));
const showMaxField = computed(() => leadForm.contact_channels.includes('max'));

const links = computed(() => [
  { label: t('app.public.catalog.activity.backToCatalog'), to: '/catalog' },
]);

const weekdayLabels = computed<Record<number, string>>(() => ({
  1: t('admin.activities.schedule.weekdays.monday'),
  2: t('admin.activities.schedule.weekdays.tuesday'),
  3: t('admin.activities.schedule.weekdays.wednesday'),
  4: t('admin.activities.schedule.weekdays.thursday'),
  5: t('admin.activities.schedule.weekdays.friday'),
  6: t('admin.activities.schedule.weekdays.saturday'),
  7: t('admin.activities.schedule.weekdays.sunday'),
}));

const resolveWeekdayLabel = (dayOfWeek: number): string =>
  weekdayLabels.value[dayOfWeek] || String(dayOfWeek);
const normalizeTime = (value: string): string => value.slice(0, 5);
const formatTimeRange = (startTime: string, endTime: string): string =>
  `${normalizeTime(startTime)} - ${normalizeTime(endTime)}`;

const resetLeadValidation = () => {
  leadValidation.request_for_type = '';
  leadValidation.child_id = '';
  leadValidation.contact_channels = '';
};

const clearLeadFeedback = () => {
  leadError.value = '';
  leadSubmitState.value = 'idle';
};

const onRequestTypeChange = (value: string) => {
  leadForm.request_for_type = value === 'child' ? 'child' : 'self';

  if (leadForm.request_for_type !== 'child') {
    leadForm.child_id = '';
  }

  clearLeadFeedback();
};

const toggleChannel = (channel: ActivityLeadContactChannel, checked: boolean) => {
  if (checked) {
    if (!leadForm.contact_channels.includes(channel)) {
      leadForm.contact_channels = [...leadForm.contact_channels, channel];
    }
  } else {
    leadForm.contact_channels = leadForm.contact_channels.filter((value) => value !== channel);
  }

  clearLeadFeedback();
};

const normalizeContactPayload = () => {
  const payload: Partial<Record<'phone' | 'telegram' | 'whatsapp' | 'max', string>> = {};

  for (const key of ['phone', 'telegram', 'whatsapp', 'max'] as const) {
    const value = leadForm.contact_payload[key].trim();

    if (value !== '') {
      payload[key] = value;
    }
  }

  return Object.keys(payload).length > 0 ? payload : null;
};

const validateLeadForm = (): boolean => {
  resetLeadValidation();

  if (leadForm.request_for_type === 'child' && !leadForm.child_id) {
    leadValidation.child_id = t('app.public.catalog.activity.lead.validation.childRequired');
  }

  if (leadForm.contact_channels.length === 0) {
    leadValidation.contact_channels = t(
      'app.public.catalog.activity.lead.validation.channelRequired'
    );
  }

  return !leadValidation.child_id && !leadValidation.contact_channels;
};

const loadLeadChildren = async () => {
  if (!isAuthenticated.value) {
    leadChildren.value = [];
    return;
  }

  leadLoadingChildren.value = true;

  try {
    leadChildren.value = await activityLeadsApi.listChildren();
  } catch {
    leadChildren.value = [];
  } finally {
    leadLoadingChildren.value = false;
  }
};

const submitLead = async () => {
  clearLeadFeedback();

  if (!activity.value || !validateLeadForm()) {
    return;
  }

  leadSubmitting.value = true;

  try {
    await activityLeadsApi.submit(activity.value.id, {
      request_for_type: leadForm.request_for_type,
      child_id: leadForm.request_for_type === 'child' ? leadForm.child_id : null,
      contact_channels: leadForm.contact_channels,
      contact_payload: normalizeContactPayload(),
      message: leadForm.message.trim() || null,
    });

    leadSubmitState.value = 'success';
    leadForm.message = '';
  } catch (error) {
    leadError.value = getApiErrorMessage(error, t('app.public.catalog.activity.lead.error'));
  } finally {
    leadSubmitting.value = false;
  }
};

const loadActivity = async () => {
  activityPending.value = true;
  activityError.value = null;

  try {
    const response = await publicActivitiesApi.show(publicKey.value);

    activity.value = response;

    const nextCanonicalPath = buildPublicActivityPath({
      public_key: publicKey.value,
      primary_category: response.primary_category,
    });

    if (route.path !== nextCanonicalPath) {
      await navigateTo(nextCanonicalPath, { replace: true });
    }
  } catch (error) {
    activity.value = null;
    activityError.value = error;
  } finally {
    activityPending.value = false;
  }
};

const schemaNodes = computed(() => {
  if (!activity.value) {
    return {
      breadcrumbNode: null,
      activityNode: null,
    };
  }

  return buildPublicActivitySchemaNodes({
    siteUrl: config.public.siteUrl,
    activity: activity.value,
  });
});

usePublicSchemaNode(
  computed(() => `page:activity:breadcrumb:${publicKey.value}`),
  computed(() => schemaNodes.value.breadcrumbNode)
);
usePublicSchemaNode(
  computed(() => `page:activity:service:${publicKey.value}`),
  computed(() => schemaNodes.value.activityNode)
);

onMounted(() => {
  if (previewState.value !== 'ready') {
    activityPending.value = false;
    return;
  }

  if (user.value?.phone && !leadForm.contact_payload.phone) {
    leadForm.contact_payload.phone = user.value.phone;
  }

  void loadLeadChildren();
  void loadActivity();
});
</script>

<style scoped lang="scss">
.activity-page__stack {
  display: grid;
  gap: 1.35rem;
}

.activity-page__top {
  display: grid;
  gap: 1.35rem;
}

.activity-page__media-column,
.activity-page__content-column {
  min-width: 0;
}

.activity-page__summary {
  display: grid;
  gap: 0.85rem;
}

.activity-page__eyebrow {
  margin: 0;
  color: var(--muted);
  font-size: 0.92rem;
}

.activity-page__title {
  margin: 0;
  font-size: clamp(1.8rem, 3vw, 2.8rem);
  line-height: 1.05;
}

.activity-page__price {
  margin: 0;
  color: #ff4f9a;
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
}

.activity-page__lead,
.activity-page__description,
.activity-page__media-empty {
  margin: 0;
  color: var(--muted);
  line-height: 1.6;
}

.activity-page__heading {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 700;
}

.activity-page__details-grid {
  display: grid;
  gap: 1rem;
  margin: 1rem 0 0;
}

.activity-page__details-grid div {
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.activity-page__details-grid dt {
  margin: 0 0 0.35rem;
  color: var(--muted);
  font-size: 0.875rem;
}

.activity-page__details-grid dd {
  margin: 0;
  font-weight: 600;
}

.activity-page__schedule-list {
  display: grid;
  gap: 0.75rem;
  margin: 1rem 0 0;
  padding: 0;
  list-style: none;
}

.activity-page__schedule-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.activity-page__lead-card-header,
.activity-page__lead-form,
.activity-page__lead-guest {
  display: grid;
  gap: 1rem;
}

.activity-page__lead-card-text {
  margin: 0.45rem 0 0;
  color: var(--muted);
  line-height: 1.6;
}

.activity-page__lead-section-title {
  margin: 0;
  font-weight: 600;
}

.activity-page__lead-channels {
  display: grid;
  gap: 0.85rem;
}

.activity-page__lead-checkbox-grid {
  display: grid;
  gap: 0.85rem;
}

.activity-page__lead-grid {
  display: grid;
  gap: 1rem;
}

.activity-page__lead-actions {
  display: flex;
  justify-content: flex-start;
}

.activity-page__lead-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 2.9rem;
  padding: 0.8rem 1.2rem;
  border: 1px solid var(--border);
  border-radius: 999px;
  background: var(--accent);
  color: #fff;
  font-weight: 700;
  text-decoration: none;
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease;
}

.activity-page__lead-button:hover {
  transform: translateY(-1px);
}

.activity-page__lead-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none;
}

.activity-page__lead-error,
.activity-page__lead-success {
  margin: 0 0 1rem;
  font-weight: 600;
}

.activity-page__lead-error {
  color: #ff6f91;
}

.activity-page__lead-success {
  color: #48c78e;
}

@media (min-width: 1080px) {
  .activity-page__top {
    grid-template-columns: minmax(0, 1.24fr) minmax(23rem, 0.76fr);
    align-items: start;
  }

  .activity-page__details-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    column-gap: 1.5rem;
  }

  .activity-page__lead-checkbox-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .activity-page__lead-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
