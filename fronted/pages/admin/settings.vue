<template>
  <section class="settings-page mx-auto w-full max-w-4xl space-y-5">
    <div class="settings-card rounded-2xl p-6 lg:p-7">
      <h2 class="text-2xl font-semibold">{{ t('admin.settings.title') }}</h2>
      <p class="settings-muted mt-2 text-sm">
        {{ t('admin.settings.subtitle') }}
      </p>
    </div>

    <div class="settings-card space-y-5 rounded-2xl p-6">
      <UiSwitch
        :model-value="isDark"
        :label="t('admin.settings.theme.label')"
        :description="t('admin.settings.theme.description')"
        :hint="t('admin.settings.theme.hint')"
        @update:model-value="onThemeToggle"
      />
      <UiSwitch
        :model-value="isMenuCollapsed"
        :label="t('admin.settings.menu.label')"
        :description="t('admin.settings.menu.description')"
        :hint="t('admin.settings.menu.hint')"
        @update:model-value="onMenuCollapseToggle"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import UiSwitch from '~/components/ui/FormControls/UiSwitch.vue';
const { t } = useI18n();

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
