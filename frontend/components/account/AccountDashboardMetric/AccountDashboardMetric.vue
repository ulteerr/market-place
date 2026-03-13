<template>
  <UiCard :variant="variant" :padding="padding" :data-test="dataTest">
    <p :class="styles.label">{{ label }}</p>
    <p :class="styles.value">{{ value }}</p>
    <p :class="styles.caption">{{ caption }}</p>
  </UiCard>
</template>

<script setup lang="ts">
import UiCard from '~/components/ui/Card/UiCard.vue';
import styles from './AccountDashboardMetric.module.scss';

withDefaults(
  defineProps<{
    label: string;
    value: string;
    caption: string;
    variant?: 'default' | 'elevated' | 'outline';
    padding?: 'sm' | 'md' | 'lg';
    dataTest?: string;
  }>(),
  {
    variant: 'default',
    padding: 'md',
    dataTest: 'account-dashboard-metric',
  }
);
</script>
