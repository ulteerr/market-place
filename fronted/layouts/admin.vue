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
            <div class="admin-user-card flex items-center gap-3 rounded-xl p-3">
              <div class="admin-avatar flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold">
                {{ userInitials }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium">
                  {{ userFullName }}
                </p>
                <p class="truncate text-xs admin-muted-text">
                  {{ userEmail }}
                </p>
              </div>
              <button
                type="button"
                class="admin-mini-button rounded-md px-2 py-1 text-xs"
                @click="handleLogout"
              >
                Выйти
              </button>
            </div>
          </div>
        </div>
      </aside>

      <button
        v-if="isSidebarOpen"
        type="button"
        class="fixed inset-0 z-30 bg-black/50 lg:hidden"
        @click="isSidebarOpen = false"
      />

      <div class="flex min-h-screen flex-1 flex-col lg:ml-0">
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

            <button type="button" class="admin-mini-button rounded-md px-3 py-2 text-xs" @click="toggleTheme">
              {{ isDark ? 'Светлая тема' : 'Тёмная тема' }}
            </button>
          </div>
        </header>

        <main class="flex-1 px-4 py-6 lg:px-8 lg:py-8">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { user, logout } = useAuth()
const { isDark, toggleTheme } = useUserSettings()

const isSidebarOpen = ref(false)

const menuItems = [
  { to: '/admin', label: 'Главная', icon: '🏠' },
  { to: '/admin/users', label: 'Пользователи', icon: '👤' },
  { to: '/admin/roles', label: 'Роли', icon: '🛡' },
  { to: '/admin/settings', label: 'Настройки', icon: '⚙' }
]

const isActive = (path: string) => route.path === path || route.path.startsWith(`${path}/`)

const userFullName = computed(() => {
  if (!user.value) {
    return 'Гость'
  }

  const first = user.value.first_name?.trim() ?? ''
  const last = user.value.last_name?.trim() ?? ''
  const fullName = `${first} ${last}`.trim()

  return fullName || user.value.email
})

const userEmail = computed(() => user.value?.email ?? 'Нет email')

const userInitials = computed(() => {
  const first = user.value?.first_name?.trim()?.[0] ?? ''
  const last = user.value?.last_name?.trim()?.[0] ?? ''
  const initials = `${first}${last}`.toUpperCase()

  return initials || user.value?.email?.[0]?.toUpperCase() || 'AD'
})

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
</script>

<style scoped>
.admin-layout {
  background: var(--surface-soft);
  color: var(--text);
}

.admin-sidebar {
  border-right: 1px solid var(--border);
  background: color-mix(in srgb, var(--surface) 96%, transparent);
}

.admin-sidebar-header,
.admin-sidebar-footer {
  border-color: var(--border);
}

.admin-sidebar-header {
  border-bottom: 1px solid var(--border);
}

.admin-sidebar-footer {
  border-top: 1px solid var(--border);
}

.admin-title {
  color: var(--text);
}

.admin-topbar {
  border-bottom: 1px solid var(--border);
  background: color-mix(in srgb, var(--surface-soft) 88%, transparent);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.admin-nav-link {
  color: var(--muted);
}

.admin-nav-link:hover {
  background: color-mix(in srgb, var(--surface) 85%, transparent);
  color: var(--text);
}

.admin-nav-link.is-active {
  background: color-mix(in srgb, var(--surface) 75%, transparent);
  color: var(--text);
}

.admin-nav-icon {
  background: color-mix(in srgb, var(--surface) 70%, transparent);
}

.admin-nav-link.is-active .admin-nav-icon {
  background: color-mix(in srgb, var(--surface) 85%, transparent);
}

.admin-user-card {
  background: color-mix(in srgb, var(--surface) 75%, transparent);
}

.admin-avatar {
  background: color-mix(in srgb, var(--surface) 60%, transparent);
}

.admin-muted-text {
  color: var(--muted);
}

.admin-mini-button,
.admin-icon-button {
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--surface) 82%, transparent);
  color: var(--muted);
  transition: color 0.2s ease, border-color 0.2s ease;
}

.admin-mini-button:hover,
.admin-icon-button:hover {
  border-color: var(--accent);
  color: var(--accent);
}
</style>
