export default defineNuxtPlugin(() => {
  const { initSettings } = useUserSettings()
  initSettings()
})
