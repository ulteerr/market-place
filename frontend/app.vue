<template>
  <div>
    <NuxtLayout>
      <main>
        <NuxtPage />
      </main>
    </NuxtLayout>
    <ClientOnly>
      <GlobalErrorReporter />
    </ClientOnly>
  </div>
</template>

<script setup lang="ts">
import GlobalErrorReporter from '~/components/debug/GlobalErrorReporter/GlobalErrorReporter.vue';

const { token, refreshUser, logout } = useAuth();
const { applyServerSettings } = useUserSettings();
const route = useRoute();

const shouldRefreshSessionForPath = (path: string) =>
  path.startsWith('/admin') || path.startsWith('/account') || path.startsWith('/organizations');

const revealUi = () => {
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      document.documentElement.setAttribute('data-ui-ready', '1');
      document.body.style.visibility = 'visible';
      document.getElementById('app-boot-loader')?.remove();
    });
  });
};

const waitForFontsAndRevealUi = async () => {
  try {
    await document.fonts.ready;
  } catch {
    // fall back to the timeout gate from nuxt.config.ts if font loading fails
  }

  revealUi();
};

onMounted(async () => {
  void waitForFontsAndRevealUi();

  if (!token.value || !shouldRefreshSessionForPath(route.path)) {
    return;
  }

  try {
    const refreshedUser = await refreshUser();
    applyServerSettings(
      (refreshedUser?.settings ?? null) as {
        theme?: 'light' | 'dark';
        collapse_menu?: boolean;
        public_city?: {
          city_id: string;
          city_name: string;
          source: 'ip_auto' | 'manual';
          region_id?: string | null;
          region_name?: string | null;
          country_id: string;
          country_name: string;
        } | null;
        admin_crud_preferences?: Record<
          string,
          {
            contentMode?: 'table' | 'table-cards' | 'cards';
            tableOnDesktop?: boolean;
          }
        >;
        admin_navigation_sections?: Record<
          string,
          {
            open?: boolean;
          }
        >;
      } | null
    );
  } catch {
    await logout();
  }
});
</script>
