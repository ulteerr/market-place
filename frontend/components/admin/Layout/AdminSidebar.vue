<template>
  <aside
    class="admin-sidebar"
    :class="{ 'is-open': isSidebarOpen, 'is-collapsed': isCollapsedNavigation }"
  >
    <div class="admin-sidebar-body flex h-full flex-col">
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
          @click="emit('close-sidebar')"
        >
          ✕
        </button>
      </div>

      <nav class="admin-sidebar-nav flex-1 overflow-y-auto px-3 py-4">
        <ul class="admin-sidebar-nav-list space-y-1">
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
            <div :class="{ 'admin-nav-collapsed-group': isCollapsedNavigation }">
              <button
                type="button"
                class="admin-nav-link admin-nav-section-toggle group flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors"
                :class="[
                  isCollapsedNavigation && 'admin-nav-section-collapsed-toggle',
                  isSectionHighlighted(section) && 'is-active',
                ]"
                :title="isCollapsedNavigation ? section.label : undefined"
                :aria-expanded="isSectionOpen(section.key)"
                :aria-controls="getSectionPanelId(section.key)"
                @click="emit('toggle-section', section.key)"
              >
                <span
                  class="admin-nav-icon inline-flex h-7 w-7 items-center justify-center rounded-md text-xs"
                >
                  <AdminNavIcon :name="section.icon" />
                </span>
                <span class="admin-nav-label">{{ section.label }}</span>
                <span
                  class="admin-nav-section-arrow"
                  :class="[
                    isCollapsedNavigation ? 'admin-nav-section-collapsed-arrow' : 'ml-auto',
                    { 'is-open': isSectionOpen(section.key) },
                  ]"
                  aria-hidden="true"
                />
              </button>

              <div
                v-show="isSectionOpen(section.key)"
                :id="getSectionPanelId(section.key)"
                :class="[
                  isCollapsedNavigation
                    ? 'admin-nav-collapsed-panel mt-2'
                    : 'admin-nav-submenu mt-1',
                  {
                    'is-active-block': isCollapsedNavigation && isSectionItemActive(section.items),
                  },
                ]"
              >
                <ul class="space-y-1">
                  <li v-for="item in section.items" :key="`${section.key}-${item.key}`">
                    <NuxtLink
                      :to="item.to"
                      class="admin-nav-link group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors"
                      :class="[
                        isCollapsedNavigation ? 'admin-nav-collapsed-item' : 'admin-nav-sub-link',
                        isActive(item.to) && 'is-active',
                      ]"
                      :title="isCollapsedNavigation ? item.label : undefined"
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
          @select="emit('user-menu-select', $event)"
        />
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import type { PropType } from 'vue';
import type {
  AdminNavigationItemView,
  AdminNavigationSectionView,
} from '~/composables/useAdminNavigation';
import AdminNavIcon from '~/components/admin/Layout/AdminNavIcon.vue';
import AdminUserMenu from '~/components/admin/Layout/AdminUserMenu.vue';

const props = defineProps({
  t: {
    type: Function as PropType<(key: string) => string>,
    required: true,
  },
  isSidebarOpen: {
    type: Boolean,
    required: true,
  },
  isCollapsedNavigation: {
    type: Boolean,
    required: true,
  },
  dashboardItem: {
    type: Object as PropType<AdminNavigationItemView>,
    required: true,
  },
  navigationSections: {
    type: Array as PropType<AdminNavigationSectionView[]>,
    required: true,
  },
  isDashboardActive: {
    type: Boolean,
    required: true,
  },
  isSectionOpen: {
    type: Function as PropType<(key: string) => boolean>,
    required: true,
  },
  isSectionItemActive: {
    type: Function as PropType<(items: Array<{ to: string }>) => boolean>,
    required: true,
  },
  isActive: {
    type: Function as PropType<(path: string) => boolean>,
    required: true,
  },
  userInitials: {
    type: String,
    required: true,
  },
  userFullName: {
    type: String,
    required: true,
  },
  userEmail: {
    type: String,
    required: true,
  },
  userAvatarUrl: {
    type: String as PropType<string | null>,
    default: null,
  },
});

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
