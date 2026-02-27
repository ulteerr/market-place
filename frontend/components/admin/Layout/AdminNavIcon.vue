<template>
  <svg
    class="admin-nav-svg-icon"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    aria-hidden="true"
  >
    <path
      v-for="(path, index) in iconPaths"
      :key="`${name}-${index}`"
      stroke-linecap="round"
      stroke-linejoin="round"
      :d="path"
    />
  </svg>
</template>

<script setup lang="ts">
import type { AdminNavIcon } from '~/config/admin-navigation';

const props = defineProps<{
  name: AdminNavIcon;
}>();

const iconPathMap: Record<AdminNavIcon, string[]> = {
  home: ['M3 10.5L12 3l9 7.5', 'M5.25 9.75V21h13.5V9.75', 'M9.75 21v-5.25h4.5V21'],
  system: [
    'M4.5 7.5a3 3 0 100-6 3 3 0 000 6z',
    'M19.5 10.5a3 3 0 100-6 3 3 0 000 6z',
    'M12 21a4.5 4.5 0 100-9 4.5 4.5 0 000 9z',
    'M8.25 5.25h7.5',
    'M7.5 16.5H4.5a3 3 0 01-3-3V12',
  ],
  settings: [
    'M10.5 6h9',
    'M4.5 6h2.25',
    'M10.5 18h9',
    'M4.5 18h2.25',
    'M15 12h7.5',
    'M4.5 12h6',
    'M7.875 4.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z',
    'M13.125 10.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z',
    'M7.875 16.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z',
  ],
  users: [
    'M16.5 7.5a3 3 0 11-6 0 3 3 0 016 0z',
    'M19.5 19.5a6 6 0 00-12 0',
    'M8.25 8.25a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
    'M2.25 19.5a4.5 4.5 0 014.5-4.5',
  ],
  roles: ['M12 3l7.5 3v6.75c0 4.5-3.75 7.5-7.5 8.25-3.75-.75-7.5-3.75-7.5-8.25V6L12 3z'],
  activity: ['M4.5 12h3l2.25-5.25L14.25 17.25 16.5 12h3', 'M3 6.75h18', 'M3 17.25h18'],
  organization: [
    'M4.5 21h15',
    'M6 21V6.75l6-3 6 3V21',
    'M9.75 10.5h4.5',
    'M9.75 14.25h4.5',
    'M10.5 21v-3.75h3V21',
  ],
  organizations: [
    'M3.75 21h16.5',
    'M5.25 21V9.75h5.25V21',
    'M13.5 21V6.75h5.25V21',
    'M7.125 13.5h1.5',
    'M15.375 10.5h1.5',
    'M15.375 14.25h1.5',
  ],
  children: [
    'M12 9.75a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z',
    'M8.25 19.5a3.75 3.75 0 017.5 0',
    'M6.75 12.75a1.5 1.5 0 100-3 1.5 1.5 0 000 3z',
    'M17.25 12.75a1.5 1.5 0 100-3 1.5 1.5 0 000 3z',
    'M4.5 19.5a2.25 2.25 0 014.5 0',
    'M15 19.5a2.25 2.25 0 014.5 0',
  ],
  metro: [
    'M6 5.25h12a1.5 1.5 0 011.5 1.5v8.25a4.5 4.5 0 01-4.5 4.5h-6A4.5 4.5 0 014.5 15V6.75A1.5 1.5 0 016 5.25z',
    'M9.75 9.75h4.5',
    'M12 19.5V21',
    'M7.5 21h9',
  ],
  geo: [
    'M12 3a9 9 0 100 18 9 9 0 000-18z',
    'M3.75 12h16.5',
    'M12 3c2.25 2.25 3.75 5.4 3.75 9s-1.5 6.75-3.75 9',
    'M12 3c-2.25 2.25-3.75 5.4-3.75 9s1.5 6.75 3.75 9',
  ],
};

const iconPaths = computed(() => iconPathMap[props.name]);
</script>

<style scoped lang="scss">
.admin-nav-svg-icon {
  width: 1rem;
  height: 1rem;
}
</style>
