<template>
  <div ref="rootRef" class="admin-user-menu" :class="{ 'is-compact': compact }">
    <button
      :id="triggerId"
      type="button"
      class="admin-user-trigger"
      :title="compact ? fullName : undefined"
      aria-haspopup="menu"
      :aria-expanded="isOpen"
      :aria-controls="resolvedMenuId"
      @click="toggleMenu"
      @keydown.down.prevent="openMenu('first')"
      @keydown.up.prevent="openMenu('last')"
      @keydown.esc.prevent="closeMenu"
    >
      <span class="admin-avatar">
        <img v-if="avatarUrl" :src="avatarUrl" :alt="fullName" class="admin-avatar-image" />
        <span v-else>{{ initials }}</span>
      </span>
      <span class="admin-user-text">
        <span class="admin-user-name">{{ fullName }}</span>
        <span class="admin-user-email">{{ email }}</span>
      </span>
    </button>

    <div
      v-if="isOpen"
      :id="resolvedMenuId"
      class="admin-user-dropdown"
      role="menu"
      :aria-labelledby="triggerId"
    >
      <button
        type="button"
        class="admin-user-item"
        role="menuitem"
        @click="onSelect('profile')"
        @keydown="onMenuItemKeydown"
      >
        {{ t('admin.userMenu.profile') }}
      </button>
      <button
        type="button"
        class="admin-user-item"
        role="menuitem"
        @click="onSelect('settings')"
        @keydown="onMenuItemKeydown"
      >
        {{ t('admin.userMenu.settings') }}
      </button>
      <div class="admin-user-divider" />
      <button
        type="button"
        class="admin-user-item is-danger"
        role="menuitem"
        @click="onSelect('logout')"
        @keydown="onMenuItemKeydown"
      >
        {{ t('admin.userMenu.logout') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
type MenuAction = 'profile' | 'settings' | 'logout';
const { t } = useI18n();

defineProps<{
  initials: string;
  fullName: string;
  email: string;
  avatarUrl?: string | null;
  compact?: boolean;
}>();

const emit = defineEmits<{
  (e: 'select', action: MenuAction): void;
}>();

const rootRef = ref<HTMLElement | null>(null);
const isOpen = ref(false);
const menuId = useId();
const resolvedMenuId = computed(() => `admin-user-menu-${menuId}`);
const triggerId = computed(() => `${resolvedMenuId.value}-trigger`);

const getMenuItems = (): HTMLButtonElement[] => {
  if (!rootRef.value) {
    return [];
  }

  return Array.from(
    rootRef.value.querySelectorAll<HTMLButtonElement>('.admin-user-dropdown .admin-user-item')
  );
};

const focusMenuItem = (index: number) => {
  const items = getMenuItems();
  if (!items.length) {
    return;
  }

  const normalizedIndex = ((index % items.length) + items.length) % items.length;
  items[normalizedIndex]?.focus();
};

const openMenu = (focusTarget: 'none' | 'first' | 'last' = 'none') => {
  isOpen.value = true;

  if (focusTarget === 'none') {
    return;
  }

  nextTick(() => {
    if (focusTarget === 'first') {
      focusMenuItem(0);
      return;
    }

    focusMenuItem(getMenuItems().length - 1);
  });
};

const toggleMenu = () => {
  if (isOpen.value) {
    isOpen.value = false;
    return;
  }

  openMenu();
};

const closeMenu = () => {
  isOpen.value = false;
};

const onSelect = (action: MenuAction) => {
  emit('select', action);
  closeMenu();
};

const onOutsidePointerDown = (event: PointerEvent) => {
  const target = event.target as Node | null;

  if (!target || !rootRef.value || rootRef.value.contains(target)) {
    return;
  }

  closeMenu();
};

const onEscape = (event: KeyboardEvent) => {
  if (event.key !== 'Escape') {
    return;
  }

  closeMenu();
};

const onMenuItemKeydown = (event: KeyboardEvent) => {
  const items = getMenuItems();
  if (!items.length) {
    return;
  }

  const activeIndex = items.findIndex((item) => item === document.activeElement);

  if (event.key === 'ArrowDown') {
    event.preventDefault();
    focusMenuItem(activeIndex + 1);
    return;
  }

  if (event.key === 'ArrowUp') {
    event.preventDefault();
    focusMenuItem(activeIndex - 1);
    return;
  }

  if (event.key === 'Home') {
    event.preventDefault();
    focusMenuItem(0);
    return;
  }

  if (event.key === 'End') {
    event.preventDefault();
    focusMenuItem(items.length - 1);
    return;
  }

  if (event.key === 'Escape') {
    event.preventDefault();
    closeMenu();
    nextTick(() => {
      const trigger = rootRef.value?.querySelector<HTMLButtonElement>('.admin-user-trigger');
      trigger?.focus();
    });
    return;
  }

  if (event.key === 'Tab') {
    closeMenu();
  }
};

onMounted(() => {
  document.addEventListener('pointerdown', onOutsidePointerDown);
  document.addEventListener('keydown', onEscape);
});

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onOutsidePointerDown);
  document.removeEventListener('keydown', onEscape);
});
</script>

<style lang="scss" scoped src="./AdminUserMenu.scss"></style>
