<template>
  <section :class="styles.section" :data-test="dataTest">
    <div v-if="title || description" :class="styles.heading">
      <h2 v-if="title" :class="styles.title">{{ title }}</h2>
      <p v-if="description" :class="styles.description">{{ description }}</p>
    </div>

    <slot />
  </section>
</template>

<script setup lang="ts">
import styles from './PublicSection.module.scss';

withDefaults(
  defineProps<{
    title?: string;
    description?: string;
    dataTest?: string;
  }>(),
  {
    title: '',
    description: '',
    dataTest: 'public-section',
  }
);
</script>
