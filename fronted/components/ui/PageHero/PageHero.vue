<template>
  <section :class="styles.hero">
    <div :class="styles.inner">
      <p :class="styles.eyebrow">{{ eyebrow }}</p>
      <h1 :class="styles.title">{{ title }}</h1>
      <p :class="styles.description">{{ description }}</p>
    </div>
  </section>
</template>

<script setup lang="ts">
import styles from './PageHero.module.scss';

withDefaults(
  defineProps<{
    eyebrow?: string;
    title: string;
    description: string;
  }>(),
  {
    eyebrow: 'Marketplace',
  }
);
</script>
