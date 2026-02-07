<template>
  <NuxtLayout>
    <main>
      <NuxtPage />
    </main>
  </NuxtLayout>
</template>

<script setup lang="ts">
const { token, refreshUser, logout } = useAuth()

onMounted(async () => {
  if (!token.value) {
    return
  }

  try {
    await refreshUser()
  } catch {
    // Token may be stale/invalid - clear local auth state.
    await logout()
  }
})
</script>
