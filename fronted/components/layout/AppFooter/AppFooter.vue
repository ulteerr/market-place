<template>
  <footer :class="styles.footer">
    <div :class="styles.container">
      <p>Marketplace Fronted</p>
      <p>Backend API: {{ apiBase }}</p>
    </div>
  </footer>
</template>

<script setup lang="ts">
import styles from './AppFooter.module.scss'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase
</script>
