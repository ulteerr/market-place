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
      } | null
    );
  } catch {
    await logout();
  }
});
</script>
