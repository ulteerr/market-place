<template>
  <UiCard variant="outline" padding="lg" :data-test="dataTest">
    <p :class="styles.eyebrow">{{ resolvedEyebrow }}</p>
    <h3 :class="styles.title">{{ title }}</h3>
    <p :class="styles.description">{{ description }}</p>
  </UiCard>
</template>

<script setup lang="ts">
import UiCard from '~/components/ui/Card/UiCard.vue';
import styles from './PrivateStateMessage.module.scss';

const { t } = useI18n();
const props = withDefaults(
  defineProps<{
    eyebrow?: string;
    title: string;
    description: string;
    dataTest?: string;
  }>(),
  {
    eyebrow: '',
    dataTest: 'private-state-message',
  }
);

const resolvedEyebrow = computed(() => props.eyebrow || t('app.defaults.stateEyebrow'));
</script>
