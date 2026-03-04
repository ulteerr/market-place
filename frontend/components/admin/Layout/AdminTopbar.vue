<template>
  <header class="admin-topbar sticky top-0 z-20">
    <div class="admin-topbar-row flex h-16 items-center justify-between px-4 lg:px-8">
      <div class="admin-topbar-left flex items-center gap-3">
        <button
          type="button"
          class="admin-icon-button rounded-lg p-2 lg:hidden"
          :aria-label="t('admin.layout.closeSidebar')"
          @click="emit('open-sidebar')"
        >
          ☰
        </button>
        <h1 class="admin-topbar-heading text-sm font-semibold lg:text-base">
          {{ t('admin.layout.heading') }}
        </h1>
      </div>
      <div class="admin-topbar-right flex items-center gap-2">
        <div class="admin-locale-select">
          <UiSelect
            class="admin-locale-ui-select"
            :model-value="locale"
            :options="normalizedLocaleSelectOptions"
            :searchable="false"
            :placeholder="String(locale).toUpperCase()"
            @update:model-value="emit('locale-change', $event)"
          />
        </div>

        <button
          type="button"
          class="admin-mini-button theme-switcher-btn rounded-md px-2 py-2"
          :title="
            resolvedIsDark ? t('admin.layout.toggleLightMode') : t('admin.layout.toggleDarkMode')
          "
          :aria-label="
            resolvedIsDark ? t('admin.layout.toggleLightMode') : t('admin.layout.toggleDarkMode')
          "
          @click="emit('toggle-theme')"
        >
          <svg
            v-if="!resolvedIsDark"
            class="theme-switcher-icon"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"
            ></path>
          </svg>
          <svg
            v-else
            class="theme-switcher-icon"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"
            ></path>
          </svg>
        </button>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import type { PropType } from 'vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';

const props = defineProps({
  t: {
    type: Function as PropType<(key: string) => string>,
    required: true,
  },
  locale: {
    type: String,
    required: true,
  },
  localeSelectOptions: {
    type: Array as PropType<ReadonlyArray<{ value: string; label: string }>>,
    required: true,
  },
  resolvedIsDark: {
    type: Boolean,
    required: true,
  },
});

const emit = defineEmits<{
  'open-sidebar': [];
  'locale-change': [value: string | number | (string | number)[]];
  'toggle-theme': [];
}>();

const { t, locale, localeSelectOptions, resolvedIsDark } = toRefs(props);
const normalizedLocaleSelectOptions = computed(() => [...localeSelectOptions.value]);
</script>

<style lang="scss" src="./AdminTopbar.scss"></style>
