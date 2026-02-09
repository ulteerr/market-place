<template>
  <div class="admin-layout min-h-screen">
    <div class="flex min-h-screen">
      <aside
        class="admin-sidebar fixed inset-y-0 left-0 z-40 w-72 transition-transform duration-200 lg:static lg:translate-x-0"
        :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
      >
        <div class="flex h-full flex-col">
          <div class="admin-sidebar-header flex items-center justify-between px-5 py-4">
            <NuxtLink to="/admin" class="admin-title text-lg font-semibold tracking-wide">
              Admin Panel
            </NuxtLink>
            <button
              type="button"
              class="admin-icon-button rounded-lg p-2 lg:hidden"
              @click="isSidebarOpen = false"
            >
              ✕
            </button>
          </div>

          <nav class="flex-1 overflow-y-auto px-3 py-4">
            <ul class="space-y-1">
              <li v-for="item in menuItems" :key="item.to">
                <NuxtLink
                  :to="item.to"
                  class="admin-nav-link group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors"
                  :class="isActive(item.to) && 'is-active'"
                >
                  <span class="admin-nav-icon inline-flex h-7 w-7 items-center justify-center rounded-md text-xs">
                    {{ item.icon }}
                  </span>
                  <span>{{ item.label }}</span>
                </NuxtLink>
              </li>
            </ul>
          </nav>

          <div class="admin-sidebar-footer p-3">
            <AdminUserMenu
              :initials="userInitials"
              :full-name="userFullName"
              :email="userEmail"
              @select="onUserMenuSelect"
            />
          </div>
        </div>
      </aside>

      <button
        v-if="isSidebarOpen"
        type="button"
        class="fixed inset-0 z-30 bg-black/50 lg:hidden"
        @click="isSidebarOpen = false"
      />

      <div class="flex min-h-screen min-w-0 flex-1 flex-col lg:ml-0">
        <header class="admin-topbar sticky top-0 z-20">
          <div class="flex h-16 items-center justify-between px-4 lg:px-8">
            <div class="flex items-center gap-3">
              <button
                type="button"
                class="admin-icon-button rounded-lg p-2 lg:hidden"
                @click="isSidebarOpen = true"
              >
                ☰
              </button>
              <h1 class="text-sm font-semibold lg:text-base">Административная панель</h1>
            </div>

            <button
              type="button"
              class="admin-mini-button theme-switcher-btn rounded-md px-2 py-2"
              :title="resolvedIsDark ? 'Toggle light mode' : 'Toggle dark mode'"
              :aria-label="resolvedIsDark ? 'Toggle light mode' : 'Toggle dark mode'"
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"></path>
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"></path>
              </svg>
            </button>
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
import AdminUserMenu from '~/components/admin/Layout/AdminUserMenu.vue'

const route = useRoute()
const { user, logout } = useAuth()
const { isDark, toggleTheme } = useUserSettings()
const isThemeUiMounted = ref(false)

const isSidebarOpen = ref(false)
const resolvedIsDark = computed(() => (isThemeUiMounted.value ? isDark.value : false))

const menuItems = [
  { to: '/admin', label: 'Главная', icon: '🏠' },
  { to: '/admin/users', label: 'Пользователи', icon: '👤' },
  { to: '/admin/roles', label: 'Роли', icon: '🛡' }
]

const isActive = (path: string) => route.path === path || route.path.startsWith(`${path}/`)

const userFullName = computed(() => {
  if (!user.value) {
    return 'Гость'
  }

  const first = user.value.first_name?.trim() ?? ''
  const last = user.value.last_name?.trim() ?? ''
  const middle = user.value.middle_name?.trim() ?? ''
  const fullName = [first, last, middle].filter(Boolean).join(' ')

  return fullName || user.value.email
})

const userEmail = computed(() => user.value?.email ?? 'Нет email')

const userInitials = computed(() => {
  const first = user.value?.first_name?.trim()?.[0] ?? ''
  const last = user.value?.last_name?.trim()?.[0] ?? ''
  const initials = `${first}${last}`.toUpperCase()

  return initials || user.value?.email?.[0]?.toUpperCase() || 'AD'
})

const onUserMenuSelect = async (action: 'profile' | 'settings' | 'logout') => {
  if (action === 'profile') {
    await navigateTo('/admin/profile')
  } else if (action === 'settings') {
    await navigateTo('/admin/settings')
  } else if (action === 'logout') {
    await handleLogout()
  }
}

const handleLogout = async () => {
  await logout()
  await navigateTo('/login')
}

watch(
  () => route.path,
  () => {
    isSidebarOpen.value = false
  }
)

onMounted(() => {
  isThemeUiMounted.value = true
})
</script>

<style lang="scss" scoped src="./admin.scss"></style>
