<template>
  <UiCard padding="lg" :data-test="dataTest">
    <template #header>
      <div :class="styles.header">
        <div>
          <p :class="styles.eyebrow">{{ resolvedEyebrow }}</p>
          <h2 :class="styles.title">{{ title }}</h2>
        </div>

        <span v-if="summary" :class="styles.summary">{{ summary }}</span>
      </div>
    </template>

    <ul :class="styles.list">
      <li v-for="item in items" :key="item.name" :class="styles.item">
        <div>
          <p :class="styles.itemTitle">{{ item.name }}</p>
          <p :class="styles.itemText">{{ item.description }}</p>
        </div>
        <div :class="styles.metaColumn">
          <span :class="styles.badge">{{ item.status }}</span>
          <span :class="styles.meta">{{ item.meta }}</span>
        </div>
      </li>
    </ul>
  </UiCard>
</template>

<script setup lang="ts">
import UiCard from '~/components/ui/Card/UiCard.vue';
import styles from './OrganizationsRosterSection.module.scss';

type RosterItem = {
  name: string;
  description: string;
  status: string;
  meta: string;
};

const { t } = useI18n();
const props = withDefaults(
  defineProps<{
    eyebrow?: string;
    title: string;
    summary?: string;
    items: RosterItem[];
    dataTest?: string;
  }>(),
  {
    eyebrow: '',
    summary: '',
    dataTest: 'organizations-roster-section',
  }
);

const resolvedEyebrow = computed(() => props.eyebrow || t('app.defaults.organizationsEyebrow'));
</script>
