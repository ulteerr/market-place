<template>
  <NuxtLayout>
    <main>
      <NuxtPage />
    </main>
  </NuxtLayout>
</template>

<script setup lang="ts">
const { token, refreshUser, logout } = useAuth();
const { applyServerSettings } = useUserSettings();

onMounted(async () => {
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      document.documentElement.setAttribute('data-ui-ready', '1');
      document.body.style.visibility = 'visible';
      document.getElementById('app-boot-loader')?.remove();
    });
  });

  if (!token.value) {
    return;
  }

  try {
    const refreshedUser = await refreshUser();
    applyServerSettings(
      (refreshedUser?.settings ?? null) as {
        theme?: 'light' | 'dark';
        collapse_menu?: boolean;
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
