<template>
  <NuxtLink
    :to="to"
    class="admin-nav-link"
    :class="[
      collapsed ? collapsedClass : expandedClass,
      {
        'is-active': isActive,
        'is-active-block': collapsed && isActiveBlock,
      },
    ]"
    :title="collapsed ? label : undefined"
  >
    <span class="admin-nav-icon">
      <AdminNavIcon :name="icon" />
    </span>
    <span class="admin-nav-label">{{ label }}</span>
  </NuxtLink>
</template>

<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router';
import AdminNavIcon from '~/components/admin/Layout/AdminNavIcon/AdminNavIcon.vue';

withDefaults(
  defineProps<{
    to: RouteLocationRaw;
    icon: string;
    label: string;
    isActive: boolean;
    collapsed: boolean;
    isActiveBlock?: boolean;
    collapsedClass?: string;
    expandedClass?: string;
  }>(),
  {
    isActiveBlock: false,
    collapsedClass: '',
    expandedClass: '',
  }
);
</script>

<style lang="scss" scoped src="./AdminSidebarLink.scss"></style>
