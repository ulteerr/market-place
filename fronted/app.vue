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
    await logout()
  }
})
</script>
