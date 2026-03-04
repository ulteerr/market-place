<template>
  <div :class="toolbarClass">
    <input
      :value="searchValue"
      type="text"
      class="admin-input rounded-lg px-3 py-2 text-sm"
      :placeholder="searchPlaceholder || t('admin.toolbar.search')"
      @input="onSearchInput"
      @keyup.enter="$emit('apply')"
    />

    <div class="toolbar-select">
      <UiSelect
        :model-value="perPage"
        :options="perPageSelectOptions"
        :placeholder="t('admin.toolbar.perPage', { count: 10 })"
        :searchable="false"
        @update:model-value="onPerPageChange"
      />
    </div>

    <button
      v-if="showApply"
      type="button"
      class="admin-button-secondary rounded-lg px-3 py-2 text-xs"
      :disabled="loading"
      @click="$emit('apply')"
    >
      {{ t('admin.toolbar.find') }}
    </button>

    <button
      type="button"
      class="admin-button-secondary rounded-lg px-3 py-2 text-xs"
      :disabled="loading"
      @click="$emit('reset')"
    >
      {{ t('admin.toolbar.reset') }}
    </button>
  </div>
</template>

<script setup lang="ts">
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
const { t } = useI18n();

const props = withDefaults(
  defineProps<{
    searchValue: string;
    searchPlaceholder?: string;
    perPage: number;
    perPageOptions: number[];
    loading?: boolean;
    showApply?: boolean;
  }>(),
  {
    searchPlaceholder: undefined,
    loading: false,
    showApply: true,
  }
);

const emit = defineEmits<{
  (event: 'update:searchValue', value: string): void;
  (event: 'update:perPage', value: number): void;
  (event: 'apply'): void;
  (event: 'reset'): void;
}>();

const onSearchInput = (event: Event) => {
  const target = event.target as HTMLInputElement | null;
  emit('update:searchValue', target?.value ?? '');
};

const perPageSelectOptions = computed(() => {
  return props.perPageOptions.map((option) => ({
    label: t('admin.toolbar.perPage', { count: option }),
    value: option,
  }));
});

const toolbarClass = computed(() => {
  return props.showApply
    ? 'grid gap-3 lg:grid-cols-[1fr_auto_auto_auto]'
    : 'grid gap-3 lg:grid-cols-[1fr_auto_auto]';
});

const onPerPageChange = (value: string | number | (string | number)[]) => {
  const parsed = Number(Array.isArray(value) ? value[0] : (value ?? props.perPage));
  emit('update:perPage', Number.isFinite(parsed) ? parsed : props.perPage);
};
</script>

<style lang="scss" scoped src="./AdminListToolbar.scss"></style>
