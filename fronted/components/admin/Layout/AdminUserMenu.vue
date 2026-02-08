<template>
  <div ref="rootRef" class="admin-user-menu">
    <button type="button" class="admin-user-trigger" @click="toggleMenu">
      <span class="admin-avatar">{{ initials }}</span>
      <span class="admin-user-text">
        <span class="admin-user-name">{{ fullName }}</span>
        <span class="admin-user-email">{{ email }}</span>
      </span>
    </button>

    <div v-if="isOpen" class="admin-user-dropdown">
      <button type="button" class="admin-user-item" @click="onSelect('settings')">Настройки</button>
      <div class="admin-user-divider" />
      <button type="button" class="admin-user-item is-danger" @click="onSelect('logout')">Выйти</button>
    </div>
  </div>
</template>

<script setup lang="ts">
type MenuAction = 'settings' | 'logout'

defineProps<{
  initials: string
  fullName: string
  email: string
}>()

const emit = defineEmits<{
  (e: 'select', action: MenuAction): void
}>()

const rootRef = ref<HTMLElement | null>(null)
const isOpen = ref(false)

const toggleMenu = () => {
  isOpen.value = !isOpen.value
}

const closeMenu = () => {
  isOpen.value = false
}

const onSelect = (action: MenuAction) => {
  emit('select', action)
  closeMenu()
}

const onOutsideClick = (event: MouseEvent) => {
  const target = event.target as Node | null

  if (!target || !rootRef.value || rootRef.value.contains(target)) {
    return
  }

  closeMenu()
}

const onEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    closeMenu()
  }
}

onMounted(() => {
  document.addEventListener('mousedown', onOutsideClick)
  document.addEventListener('keydown', onEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onOutsideClick)
  document.removeEventListener('keydown', onEscape)
})
</script>

<style lang="scss" scoped src="./AdminUserMenu.scss"></style>
