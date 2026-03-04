<template>
  <aside
    class="admin-sidebar"
    :class="{ 'is-open': isSidebarOpen, 'is-collapsed': isCollapsedNavigation }"
  >
    <div class="admin-sidebar-body">
      <div class="admin-sidebar-header">
        <NuxtLink to="/admin" class="admin-title">
          <span class="admin-title-full">{{ t('admin.layout.panelTitle') }}</span>
          <span class="admin-title-short">{{ t('admin.layout.shortPanelTitle') }}</span>
        </NuxtLink>
        <button
          type="button"
          class="admin-icon-button admin-sidebar-close"
          :aria-label="t('admin.layout.closeSidebar')"
          @click="emit('close-sidebar')"
        >
          ✕
        </button>
      </div>

      <nav class="admin-sidebar-nav">
        <ul class="admin-sidebar-nav-list">
          <li>
            <AdminSidebarLink
              :to="dashboardItem.to"
              :icon="dashboardItem.icon"
              :label="dashboardItem.label"
              :is-active="isDashboardActive"
              :collapsed="isCollapsedNavigation"
              is-active-block
            />
          </li>

          <li
            v-for="section in navigationSections"
            :key="section.key"
            class="admin-nav-section"
            :class="{
              'is-active-block': isCollapsedNavigation && isSectionItemActive(section.items),
            }"
          >
            <div :class="{ 'admin-nav-collapsed-group': isCollapsedNavigation }">
              <button
                type="button"
                class="admin-nav-link admin-nav-section-toggle"
                :class="[
                  isCollapsedNavigation && 'admin-nav-section-collapsed-toggle',
                  isSectionHighlighted(section) && 'is-active',
                ]"
                :title="isCollapsedNavigation ? section.label : undefined"
                :aria-expanded="isSectionOpen(section.key)"
                :aria-controls="getSectionPanelId(section.key)"
                @click="emit('toggle-section', section.key)"
              >
                <span class="admin-nav-icon">
                  <AdminNavIcon :name="section.icon" />
                </span>
                <span class="admin-nav-label">{{ section.label }}</span>
                <span
                  class="admin-nav-section-arrow"
                  :class="[
                    isCollapsedNavigation
                      ? 'admin-nav-section-collapsed-arrow'
                      : 'admin-nav-section-arrow-end',
                    { 'is-open': isSectionOpen(section.key) },
                  ]"
                  aria-hidden="true"
                />
              </button>

              <div
                v-show="isSectionOpen(section.key)"
                :id="getSectionPanelId(section.key)"
                :class="[
                  isCollapsedNavigation ? 'admin-nav-collapsed-panel' : 'admin-nav-submenu',
                  {
                    'is-active-block': isCollapsedNavigation && isSectionItemActive(section.items),
                  },
                ]"
              >
                <ul class="admin-nav-submenu-list">
                  <li v-for="item in section.items" :key="`${section.key}-${item.key}`">
                    <AdminSidebarLink
                      :to="item.to"
                      :icon="item.icon"
                      :label="item.label"
                      :is-active="isActive(item.to)"
                      :collapsed="isCollapsedNavigation"
                      collapsed-class="admin-nav-collapsed-item"
                      expanded-class="admin-nav-sub-link"
                    />
                  </li>
                </ul>
              </div>
            </div>
          </li>
        </ul>
      </nav>

      <div class="admin-sidebar-footer">
        <AdminUserMenu
          :initials="userInitials"
          :full-name="userFullName"
          :email="userEmail"
          :avatar-url="userAvatarUrl"
          :compact="isCollapsedNavigation"
          @select="emit('user-menu-select', $event)"
        />
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import type {
  AdminNavigationItemView,
  AdminNavigationSectionView,
} from '~/composables/useAdminNavigation';
import AdminNavIcon from '~/components/admin/Layout/AdminNavIcon/AdminNavIcon.vue';
import AdminSidebarLink from '~/components/admin/Layout/AdminSidebarLink/AdminSidebarLink.vue';
import AdminUserMenu from '~/components/admin/Layout/AdminUserMenu/AdminUserMenu.vue';

const props = withDefaults(
  defineProps<{
    t: (key: string) => string;
    isSidebarOpen: boolean;
    isCollapsedNavigation: boolean;
    dashboardItem: AdminNavigationItemView;
    navigationSections: AdminNavigationSectionView[];
    isDashboardActive: boolean;
    isSectionOpen: (key: string) => boolean;
    isSectionItemActive: (items: Array<{ to: string }>) => boolean;
    isActive: (path: string) => boolean;
    userInitials: string;
    userFullName: string;
    userEmail: string;
    userAvatarUrl?: string | null;
  }>(),
  {
    userAvatarUrl: null,
  }
);

const emit = defineEmits<{
  'close-sidebar': [];
  'toggle-section': [key: string];
  'user-menu-select': [action: 'profile' | 'settings' | 'logout'];
}>();

const {
  t,
  isSidebarOpen,
  isCollapsedNavigation,
  dashboardItem,
  navigationSections,
  isDashboardActive,
  isSectionOpen,
  isSectionItemActive,
  isActive,
  userInitials,
  userFullName,
  userEmail,
  userAvatarUrl,
} = toRefs(props);

const isSectionHighlighted = (section: AdminNavigationSectionView): boolean =>
  isSectionItemActive.value(section.items) || isSectionOpen.value(section.key);

const getSectionPanelId = (sectionKey: string): string => `admin-nav-section-${sectionKey}`;
</script>

<style lang="scss" src="./AdminSidebar.scss"></style>
