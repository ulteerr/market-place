<template>
  <header :class="styles.header">
    <div :class="styles.container">
      <NuxtLink to="/" :class="styles.logo">Marketplace</NuxtLink>

      <nav :class="styles.nav">
        <NuxtLink to="/" :class="styles.link">Главная</NuxtLink>
        <NuxtLink to="/admin" :class="styles.link">Админка</NuxtLink>

        <button type="button" :class="styles.themeButton" @click="toggleTheme">
          {{ isDark ? 'Светлая' : 'Тёмная' }} тема
        </button>
      </nav>
    </div>
  </header>
</template>

<script setup lang="ts">
import styles from './AppHeader.module.scss'

const { isDark, toggleTheme } = useUserSettings()
</script>
