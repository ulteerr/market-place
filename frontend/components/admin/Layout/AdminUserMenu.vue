<template>
  <div ref="rootRef" class="admin-user-menu" :class="{ 'is-compact': compact }">
    <button
      type="button"
      class="admin-user-trigger"
      :title="compact ? fullName : undefined"
      aria-haspopup="menu"
      :aria-expanded="isOpen"
      :aria-controls="resolvedMenuId"
      @click="toggleMenu"
      @keydown.down.prevent="openMenu"
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

    <div v-if="isOpen" :id="resolvedMenuId" class="admin-user-dropdown" role="menu">
      <button type="button" class="admin-user-item" role="menuitem" @click="onSelect('profile')">
        {{ t('admin.userMenu.profile') }}
      </button>
      <button type="button" class="admin-user-item" role="menuitem" @click="onSelect('settings')">
        {{ t('admin.userMenu.settings') }}
      </button>
      <div class="admin-user-divider" />
      <button
        type="button"
        class="admin-user-item is-danger"
        role="menuitem"
        @click="onSelect('logout')"
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

const openMenu = () => {
  isOpen.value = true;
};

const toggleMenu = () => {
  isOpen.value = !isOpen.value;
};

const closeMenu = () => {
  isOpen.value = false;
};

const onSelect = (action: MenuAction) => {
  emit('select', action);
  closeMenu();
};

const onOutsideClick = (event: MouseEvent) => {
  const target = event.target as Node | null;

  if (!target || !rootRef.value || rootRef.value.contains(target)) {
    return;
  }

  closeMenu();
};

const onEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    closeMenu();
  }
};

onMounted(() => {
  document.addEventListener('mousedown', onOutsideClick);
  document.addEventListener('keydown', onEscape);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onOutsideClick);
  document.removeEventListener('keydown', onEscape);
});
</script>

<style lang="scss" scoped src="./AdminUserMenu.scss"></style>
