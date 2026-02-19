<template>
  <div class="admin-layout min-h-screen">
    <div class="flex min-h-screen">
      <aside
        class="admin-sidebar"
        :class="{ 'is-open': isSidebarOpen, 'is-collapsed': isCollapsedNavigation }"
      >
        <div class="flex h-full flex-col">
          <div class="admin-sidebar-header flex items-center justify-between px-5 py-4">
            <NuxtLink to="/admin" class="admin-title text-lg font-semibold tracking-wide">
              <span :class="isCollapsedNavigation ? 'lg:hidden' : ''">{{
                t('admin.layout.panelTitle')
              }}</span>
              <span :class="isCollapsedNavigation ? 'hidden lg:inline' : 'hidden'">{{
                t('admin.layout.shortPanelTitle')
              }}</span>
            </NuxtLink>
            <button
              type="button"
              class="admin-icon-button rounded-lg p-2 lg:hidden"
              :aria-label="t('admin.layout.closeSidebar')"
              @click="isSidebarOpen = false"
            >
              ✕
            </button>
          </div>

          <nav class="flex-1 overflow-y-auto px-3 py-4">
            <ul class="space-y-1">
              <li>
                <NuxtLink
                  :to="dashboardItem.to"
                  class="admin-nav-link group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors"
                  :class="{
                    'is-active': isDashboardActive,
                    'is-active-block': isCollapsedNavigation && isDashboardActive,
                  }"
                  :title="isCollapsedNavigation ? dashboardItem.label : undefined"
                >
                  <span
                    class="admin-nav-icon inline-flex h-7 w-7 items-center justify-center rounded-md text-xs"
                  >
                    <AdminNavIcon :name="dashboardItem.icon" />
                  </span>
                  <span class="admin-nav-label">{{ dashboardItem.label }}</span>
                </NuxtLink>
              </li>

              <li
                v-for="section in navigationSections"
                :key="section.key"
                class="admin-nav-section"
                :class="{
                  'is-active-block': isCollapsedNavigation && isSectionItemActive(section.items),
                }"
              >
                <div v-if="isCollapsedNavigation" class="admin-nav-collapsed-group">
                  <button
                    type="button"
                    class="admin-nav-link admin-nav-section-toggle admin-nav-section-collapsed-toggle group flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors"
                    :class="
                      (isSectionItemActive(section.items) || isSectionOpen(section.key)) &&
                      'is-active'
                    "
                    :title="section.label"
                    :aria-expanded="isSectionOpen(section.key)"
                    :aria-controls="`admin-nav-section-${section.key}`"
                    @click="toggleSection(section.key)"
                  >
                    <span
                      class="admin-nav-icon inline-flex h-7 w-7 items-center justify-center rounded-md text-xs"
                    >
                      <AdminNavIcon :name="section.icon" />
                    </span>
                    <span class="admin-nav-label">{{ section.label }}</span>
                    <span
                      class="admin-nav-section-arrow admin-nav-section-collapsed-arrow"
                      :class="{ 'is-open': isSectionOpen(section.key) }"
                      aria-hidden="true"
                    />
                  </button>

                  <div
                    v-show="isSectionOpen(section.key)"
                    :id="`admin-nav-section-${section.key}`"
                    class="admin-nav-collapsed-panel mt-2"
                    :class="{ 'is-active-block': isSectionItemActive(section.items) }"
                  >
                    <ul class="space-y-1">
                      <li
                        v-for="item in section.items"
                        :key="`collapsed-${section.key}-${item.key}`"
                      >
                        <NuxtLink
                          :to="item.to"
                          class="admin-nav-link admin-nav-collapsed-item group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors"
                          :class="isActive(item.to) && 'is-active'"
                          :title="item.label"
                        >
                          <span
                            class="admin-nav-icon inline-flex h-7 w-7 items-center justify-center rounded-md text-xs"
                          >
                            <AdminNavIcon :name="item.icon" />
                          </span>
                          <span class="admin-nav-label">{{ item.label }}</span>
                        </NuxtLink>
                      </li>
                    </ul>
                  </div>
                </div>

                <button
                  v-else
                  type="button"
                  class="admin-nav-link admin-nav-section-toggle group flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors"
                  :class="
                    (isSectionItemActive(section.items) || isSectionOpen(section.key)) &&
                    'is-active'
                  "
                  :title="isCollapsedNavigation ? section.label : undefined"
                  :aria-expanded="isSectionOpen(section.key)"
                  :aria-controls="`admin-nav-section-${section.key}`"
                  @click="toggleSection(section.key)"
                >
                  <span
                    class="admin-nav-icon inline-flex h-7 w-7 items-center justify-center rounded-md text-xs"
                  >
                    <AdminNavIcon :name="section.icon" />
                  </span>
                  <span class="admin-nav-label">{{ section.label }}</span>
                  <span
                    class="admin-nav-section-arrow ml-auto"
                    :class="{ 'is-open': isSectionOpen(section.key) }"
                    aria-hidden="true"
                  />
                </button>

                <ul
                  v-if="!isCollapsedNavigation"
                  v-show="!isCollapsedNavigation && isSectionOpen(section.key)"
                  :id="`admin-nav-section-${section.key}`"
                  class="admin-nav-submenu mt-1 space-y-1"
                >
                  <li v-for="item in section.items" :key="item.to">
                    <NuxtLink
                      :to="item.to"
                      class="admin-nav-link admin-nav-sub-link group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors"
                      :class="isActive(item.to) && 'is-active'"
                    >
                      <span
                        class="admin-nav-icon inline-flex h-7 w-7 items-center justify-center rounded-md text-xs"
                      >
                        <AdminNavIcon :name="item.icon" />
                      </span>
                      <span class="admin-nav-label">{{ item.label }}</span>
                    </NuxtLink>
                  </li>
                </ul>
              </li>
            </ul>
          </nav>

          <div class="admin-sidebar-footer p-3">
            <AdminUserMenu
              :initials="userInitials"
              :full-name="userFullName"
              :email="userEmail"
              :avatar-url="userAvatarUrl"
              :compact="isCollapsedNavigation"
              @select="onUserMenuSelect"
            />
          </div>
        </div>
      </aside>

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
        class="fixed inset-0 z-30 bg-black/50 lg:hidden"
        @click="isSidebarOpen = false"
      />

      <div class="flex min-h-screen min-w-0 flex-1 flex-col lg:ml-0">
        <header class="admin-topbar sticky top-0 z-20">
          <div class="admin-topbar-row flex h-16 items-center justify-between px-4 lg:px-8">
            <div class="admin-topbar-left flex items-center gap-3">
              <button
                type="button"
                class="admin-icon-button rounded-lg p-2 lg:hidden"
                :aria-label="t('admin.layout.closeSidebar')"
                @click="isSidebarOpen = true"
              >
                ☰
              </button>
              <h1 class="admin-topbar-heading text-sm font-semibold lg:text-base">
                {{ t('admin.layout.heading') }}
              </h1>
            </div>
            <div class="admin-topbar-right flex items-center gap-2">
              <div class="admin-locale-select">
                <UiSelect
                  class="admin-locale-ui-select"
                  :model-value="locale"
                  :options="localeSelectOptions"
                  :searchable="false"
                  :placeholder="String(locale).toUpperCase()"
                  @update:model-value="onLocaleChange"
                />
              </div>

              <button
                type="button"
                class="admin-mini-button theme-switcher-btn rounded-md px-2 py-2"
                :title="
                  resolvedIsDark
                    ? t('admin.layout.toggleLightMode')
                    : t('admin.layout.toggleDarkMode')
                "
                :aria-label="
                  resolvedIsDark
                    ? t('admin.layout.toggleLightMode')
                    : t('admin.layout.toggleDarkMode')
                "
                @click="toggleTheme"
              >
                <svg
                  v-if="!resolvedIsDark"
                  class="theme-switcher-icon"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"
                  ></path>
                </svg>
                <svg
                  v-else
                  class="theme-switcher-icon"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"
                  ></path>
                </svg>
              </button>
            </div>
          </div>
        </header>

        <main class="flex-1 min-w-0 px-4 py-6 lg:px-8 lg:py-8">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type {
  AdminNavigationItemDefinition,
  AdminNavigationSectionDefinition,
} from '~/config/admin-navigation';
import {
  adminDashboardItemDefinition,
  adminNavigationSectionDefinitions,
} from '~/config/admin-navigation';
import AdminNavIcon from '~/components/admin/Layout/AdminNavIcon.vue';
import AdminUserMenu from '~/components/admin/Layout/AdminUserMenu.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect.vue';

const { t, locale, setLocale } = useI18n();
const route = useRoute();
const { user, logout, isAuthenticated } = useAuth();
const { isDark, toggleTheme, settings, toggleCollapseMenu, updateSettings } = useUserSettings();
const isThemeUiMounted = ref(false);
const isApplyingLocaleFromSettings = ref(false);
const localeStorageKey = 'preferred_locale';

const isSidebarOpen = ref(false);
const isDesktopViewport = ref(false);
const resolvedIsDark = computed(() => (isThemeUiMounted.value ? isDark.value : false));
const isMenuCollapsed = computed(() => settings.value.collapse_menu);
const isCollapsedNavigation = computed(() => isMenuCollapsed.value && isDesktopViewport.value);
const localeSelectOptions = [
  { value: 'ru', label: 'RU' },
  { value: 'en', label: 'EN' },
];

type NavigationItemView = AdminNavigationItemDefinition & {
  label: string;
};

type NavigationSectionView = Omit<AdminNavigationSectionDefinition, 'items'> & {
  label: string;
  items: NavigationItemView[];
};

const navigationPermissions: Record<string, string> = {
  users: 'admin.users.read',
  roles: 'admin.roles.read',
  organizations: 'org.company.profile.read',
  children: 'org.children.read',
  'action-logs': 'admin.action-log.read',
};
const { hasPermission } = usePermissions();

const dashboardItem = computed<NavigationItemView>(() => ({
  ...adminDashboardItemDefinition,
  label: t(adminDashboardItemDefinition.labelKey),
}));

const isNavigationItemVisible = (item: AdminNavigationItemDefinition): boolean => {
  const permission = navigationPermissions[item.key];

  if (!permission) {
    return true;
  }

  return hasPermission(permission);
};

const navigationSections = computed<NavigationSectionView[]>(() =>
  adminNavigationSectionDefinitions
    .map((section) => ({
      ...section,
      label: t(section.labelKey),
      items: section.items
        .filter((item) => isNavigationItemVisible(item))
        .map((item) => ({
          ...item,
          label: t(item.labelKey),
        })),
    }))
    .filter((section) => section.items.length > 0)
);

const isSectionOpen = (key: string) => settings.value.admin_navigation_sections[key]?.open === true;

const setSectionOpen = (key: string, open: boolean) => {
  updateSettings({
    admin_navigation_sections: {
      [key]: { open },
    },
  });
};

const toggleSection = (key: string) => {
  setSectionOpen(key, !isSectionOpen(key));
};

const isActive = (path: string) => route.path === path || route.path.startsWith(`${path}/`);
const isSectionItemActive = (items: Array<{ to: string }>) =>
  items.some((item) => isActive(item.to));
const isDashboardActive = computed(() => route.path === dashboardItem.value.to);

const updateDesktopViewportState = () => {
  if (!process.client) {
    return;
  }

  isDesktopViewport.value = window.matchMedia('(min-width: 1024px)').matches;
};

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

const onUserMenuSelect = async (action: 'profile' | 'settings' | 'logout') => {
  if (action === 'profile') {
    await navigateTo('/admin/profile');
  } else if (action === 'settings') {
    await navigateTo('/admin/settings');
  } else if (action === 'logout') {
    await handleLogout();
  }
};

const handleLogout = async () => {
  await logout();
  await navigateTo('/login');
};

const onLocaleChange = async (value: string | number | (string | number)[]) => {
  const nextLocale = Array.isArray(value) ? value[0] : value;
  if (nextLocale === 'ru' || nextLocale === 'en') {
    await setLocale(nextLocale);
  }
};

const syncLocaleFromSource = async () => {
  if (!process.client) {
    return;
  }

  if (isAuthenticated.value) {
    window.localStorage.removeItem(localeStorageKey);
    const savedLocale = settings.value.locale;

    if (savedLocale === 'ru' || savedLocale === 'en') {
      if (locale.value !== savedLocale) {
        await setLocale(savedLocale);
      }
      return;
    }

    if (locale.value === 'ru' || locale.value === 'en') {
      updateSettings({ locale: locale.value });
    }
    return;
  }

  const storedLocale = window.localStorage.getItem(localeStorageKey);
  if (storedLocale === 'ru' || storedLocale === 'en') {
    if (locale.value !== storedLocale) {
      await setLocale(storedLocale);
    }
    return;
  }

  if (locale.value === 'ru' || locale.value === 'en') {
    window.localStorage.setItem(localeStorageKey, locale.value);
  }
};

watch(
  () => route.path,
  () => {
    isSidebarOpen.value = false;
  }
);

onMounted(() => {
  isThemeUiMounted.value = true;
  updateDesktopViewportState();
  window.addEventListener('resize', updateDesktopViewportState);
  void syncLocaleFromSource();
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateDesktopViewportState);
});

watch(
  () => locale.value,
  (nextLocale) => {
    if (nextLocale !== 'ru' && nextLocale !== 'en') {
      return;
    }

    if (isAuthenticated.value) {
      if (process.client) {
        window.localStorage.removeItem(localeStorageKey);
      }
      if (isApplyingLocaleFromSettings.value) {
        return;
      }
      if (settings.value.locale !== nextLocale) {
        updateSettings({ locale: nextLocale });
      }
      return;
    }

    if (process.client) {
      window.localStorage.setItem(localeStorageKey, nextLocale);
    }
  }
);

watch(
  () => settings.value.locale,
  async (nextLocale) => {
    if (!isAuthenticated.value) {
      return;
    }
    if (nextLocale !== 'ru' && nextLocale !== 'en') {
      return;
    }
    if (locale.value === nextLocale) {
      return;
    }

    isApplyingLocaleFromSettings.value = true;
    try {
      await setLocale(nextLocale);
    } finally {
      isApplyingLocaleFromSettings.value = false;
    }
  }
);

watch(
  () => isAuthenticated.value,
  () => {
    void syncLocaleFromSource();
  }
);
</script>

<style lang="scss" scoped src="./admin.scss"></style>
