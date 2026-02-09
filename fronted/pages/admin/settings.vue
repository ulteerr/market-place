<template>
  <section class="settings-page mx-auto w-full max-w-4xl space-y-5">
    <div class="settings-card rounded-2xl p-6 lg:p-7">
      <h2 class="text-2xl font-semibold">Настройки пользователя</h2>
      <p class="settings-muted mt-2 text-sm">
        Настройки автоматически сохраняются в браузере и синхронизируются с backend.
      </p>
    </div>

    <div class="settings-card space-y-5 rounded-2xl p-6">
      <UiSwitch
        :model-value="isDark"
        label="Тёмная тема"
        description="Переключает цветовую схему интерфейса"
        hint="Сохраняется в профиле пользователя"
        @update:model-value="onThemeToggle"
      />
      <UiSwitch
        :model-value="isMenuCollapsed"
        label="Collapse menu"
        description="Сворачивает боковое меню до режима с иконками"
        hint="Управляет шириной sidebar в админке"
        @update:model-value="onMenuCollapseToggle"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import UiSwitch from '~/components/ui/FormControls/UiSwitch.vue';

definePageMeta({
  layout: 'admin',
});

const { isDark, settings, setTheme, setCollapseMenu } = useUserSettings();
const isMenuCollapsed = computed(() => settings.value.collapse_menu);

const onThemeToggle = (value: boolean) => {
  setTheme(value ? 'dark' : 'light');
};

const onMenuCollapseToggle = (value: boolean) => {
  setCollapseMenu(value);
};
</script>

<style lang="scss" scoped src="./settings.scss"></style>
