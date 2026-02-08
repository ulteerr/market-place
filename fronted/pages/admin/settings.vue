<template>
  <section class="settings-page mx-auto w-full max-w-4xl space-y-5">
    <div class="settings-card rounded-2xl p-6 lg:p-7">
      <h2 class="text-2xl font-semibold">Настройки пользователя</h2>
      <p class="settings-muted mt-2 text-sm">
        Пока сохраняем локально в браузере. Следующим шагом подключим сохранение на backend.
      </p>
    </div>

    <div class="settings-card rounded-2xl p-6">
      <UiSwitch
        :model-value="isDark"
        label="Тёмная тема"
        description="Переключает цветовую схему интерфейса"
        hint="Настройка сохраняется в localStorage"
        @update:model-value="onThemeToggle"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import UiSwitch from '~/components/ui/FormControls/UiSwitch.vue'

definePageMeta({
  layout: 'admin'
})

const { isDark, setTheme } = useUserSettings()

const onThemeToggle = (value: boolean) => {
  setTheme(value ? 'dark' : 'light')
}
</script>

<style lang="scss" scoped src="./settings.scss"></style>
