<template>
  <div :class="styles.actions" :data-test="dataTest">
    <NuxtLink v-for="link in links" :key="link.to" :to="link.to" :class="styles.action">
      {{ link.label }}
    </NuxtLink>
  </div>
</template>

<script setup lang="ts">
import styles from './PublicActionLinks.module.scss';

type PublicActionLink = {
  label: string;
  to: string;
};

withDefaults(
  defineProps<{
    links: PublicActionLink[];
    dataTest?: string;
  }>(),
  {
    dataTest: 'public-action-links',
  }
);
</script>
