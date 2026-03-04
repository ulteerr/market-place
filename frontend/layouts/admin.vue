<template>
  <div class="admin-layout min-h-screen">
    <div class="admin-layout__shell flex min-h-screen">
      <AdminSidebar
        :t="t"
        :is-sidebar-open="isSidebarOpen"
        :is-collapsed-navigation="isCollapsedNavigation"
        :dashboard-item="dashboardItem"
        :navigation-sections="navigationSections"
        :is-dashboard-active="isDashboardActive"
        :is-section-open="isSectionOpen"
        :is-section-item-active="isSectionItemActive"
        :is-active="isActive"
        :user-initials="userInitials"
        :user-full-name="userFullName"
        :user-email="userEmail"
        :user-avatar-url="userAvatarUrl"
        @close-sidebar="isSidebarOpen = false"
        @toggle-section="toggleSection"
        @user-menu-select="onUserMenuSelect"
      />

      <button
        type="button"
        class="admin-sidebar-toggle"
        :title="
          isCollapsedNavigation
            ? t('admin.layout.sidebarToggleExpand')
            : t('admin.layout.sidebarToggleCollapse')
        "
        :aria-label="
          isCollapsedNavigation
            ? t('admin.layout.sidebarToggleExpand')
            : t('admin.layout.sidebarToggleCollapse')
        "
        @click="toggleCollapseMenu"
      >
        <span
          class="admin-sidebar-toggle-chevron"
          :class="{ 'is-collapsed': isCollapsedNavigation }"
          aria-hidden="true"
        />
      </button>

      <button
        v-if="isSidebarOpen"
        type="button"
        class="admin-layout__sidebar-backdrop"
        :aria-label="t('admin.layout.closeSidebar')"
        @click="isSidebarOpen = false"
      />

      <div class="admin-layout__content flex min-h-screen min-w-0 flex-1 flex-col lg:ml-0">
        <AdminTopbar
          :t="t"
          :locale="locale"
          :locale-select-options="localeSelectOptions"
          :resolved-is-dark="resolvedIsDark"
          @open-sidebar="isSidebarOpen = true"
          @locale-change="onLocaleChange"
          @toggle-theme="toggleTheme"
        />

        <main class="admin-layout__main flex-1 min-w-0 px-4 py-6 lg:px-8 lg:py-8">
          <AdminBreadcrumbs />
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import AdminBreadcrumbs from '~/components/admin/Layout/AdminBreadcrumbs/AdminBreadcrumbs.vue';
import AdminSidebar from '~/components/admin/Layout/AdminSidebar/AdminSidebar.vue';
import AdminTopbar from '~/components/admin/Layout/AdminTopbar/AdminTopbar.vue';

const { t, locale, setLocale } = useI18n();
const route = useRoute();
const { user, logout, isAuthenticated } = useAuth();
const { isDark, toggleTheme, settings, toggleCollapseMenu, updateSettings } = useUserSettings();
const { onUserMenuSelect } = useAdminUserMenuActions({ logout });

const isThemeUiMounted = ref(false);
const resolvedIsDark = computed(() => (isThemeUiMounted.value ? isDark.value : false));
const isMenuCollapsed = computed(() => settings.value.collapse_menu);

const { isSidebarOpen, isCollapsedNavigation } = useAdminViewport({
  route,
  isMenuCollapsed,
});

const {
  dashboardItem,
  navigationSections,
  isSectionOpen,
  toggleSection,
  isActive,
  isSectionItemActive,
  isDashboardActive,
} = useAdminNavigation({
  t,
  route,
  settings,
  updateSettings,
});

const { localeSelectOptions, onLocaleChange } = useAdminLocaleSync({
  locale,
  setLocale,
  isAuthenticated,
  settings,
  updateSettings,
});

const userFullName = computed(() => {
  if (!user.value) {
    return t('admin.layout.user.guest');
  }

  const first = user.value.first_name?.trim() ?? '';
  const last = user.value.last_name?.trim() ?? '';
  const middle = user.value.middle_name?.trim() ?? '';
  const fullName = [first, last, middle].filter(Boolean).join(' ');

  return fullName || user.value.email;
});

const userEmail = computed(() => user.value?.email ?? t('admin.layout.user.noEmail'));
const userAvatarUrl = computed(() => user.value?.avatar?.url ?? null);

const userInitials = computed(() => {
  const first = user.value?.first_name?.trim()?.[0] ?? '';
  const last = user.value?.last_name?.trim()?.[0] ?? '';
  const initials = `${first}${last}`.toUpperCase();

  return initials || user.value?.email?.[0]?.toUpperCase() || 'AD';
});

const onGlobalEscape = (event: KeyboardEvent) => {
  if (event.key !== 'Escape' || !isSidebarOpen.value) {
    return;
  }

  isSidebarOpen.value = false;
};

onMounted(() => {
  isThemeUiMounted.value = true;
  document.addEventListener('keydown', onGlobalEscape);
});

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onGlobalEscape);
});
</script>

<style lang="scss" src="./admin.scss"></style>
