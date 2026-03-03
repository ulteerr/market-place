import type { Ref } from 'vue';

interface UseAdminViewportOptions {
  route: ReturnType<typeof useRoute>;
  isMenuCollapsed: Readonly<Ref<boolean>>;
}

export const useAdminViewport = ({ route, isMenuCollapsed }: UseAdminViewportOptions) => {
  const isSidebarOpen = ref(false);
  const isDesktopViewport = ref(false);

  const isCollapsedNavigation = computed(() => isMenuCollapsed.value && isDesktopViewport.value);

  const updateDesktopViewportState = () => {
    if (!process.client) {
      return;
    }

    isDesktopViewport.value = window.matchMedia('(min-width: 1024px)').matches;
  };

  watch(
    () => route.path,
    () => {
      isSidebarOpen.value = false;
    }
  );

  onMounted(() => {
    updateDesktopViewportState();
    window.addEventListener('resize', updateDesktopViewportState);
  });

  onBeforeUnmount(() => {
    window.removeEventListener('resize', updateDesktopViewportState);
  });

  return {
    isSidebarOpen,
    isDesktopViewport,
    isCollapsedNavigation,
  };
};
