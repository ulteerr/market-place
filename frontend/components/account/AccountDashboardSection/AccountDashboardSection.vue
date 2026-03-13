<template>
  <UiCard :variant="variant" :padding="padding" :data-test="dataTest">
    <template #header>
      <div :class="styles.header">
        <div>
          <p :class="styles.eyebrow">{{ resolvedEyebrow }}</p>
          <h2 :class="styles.title">{{ title }}</h2>
        </div>

        <NuxtLink v-if="actionLabel && actionTo" :to="actionTo" :class="styles.link">
          {{ actionLabel }}
        </NuxtLink>
      </div>
    </template>

    <slot />
  </UiCard>
</template>

<script setup lang="ts">
import UiCard from '~/components/ui/Card/UiCard.vue';
import styles from './AccountDashboardSection.module.scss';

const { t } = useI18n();
const props = withDefaults(
  defineProps<{
    eyebrow?: string;
    title: string;
    actionLabel?: string;
    actionTo?: string;
    variant?: 'default' | 'elevated' | 'outline';
    padding?: 'sm' | 'md' | 'lg';
    dataTest?: string;
  }>(),
  {
    eyebrow: '',
    variant: 'default',
    padding: 'lg',
    dataTest: 'account-dashboard-section',
  }
);

const resolvedEyebrow = computed(() => props.eyebrow || t('app.defaults.accountEyebrow'));
</script>
