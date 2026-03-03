<template>
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
          @click="emit('close-sidebar')"
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
                  (isSectionItemActive(section.items) || isSectionOpen(section.key)) && 'is-active'
                "
                :title="section.label"
                :aria-expanded="isSectionOpen(section.key)"
                :aria-controls="`admin-nav-section-${section.key}`"
                @click="emit('toggle-section', section.key)"
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
                  <li v-for="item in section.items" :key="`collapsed-${section.key}-${item.key}`">
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
                (isSectionItemActive(section.items) || isSectionOpen(section.key)) && 'is-active'
              "
              :title="isCollapsedNavigation ? section.label : undefined"
              :aria-expanded="isSectionOpen(section.key)"
              :aria-controls="`admin-nav-section-${section.key}`"
              @click="emit('toggle-section', section.key)"
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
</script>
