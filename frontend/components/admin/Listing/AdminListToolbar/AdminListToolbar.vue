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

    <div v-if="showPerPageControl" class="toolbar-select">
      <UiSelect
        :model-value="effectivePerPage"
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
    totalCount?: number;
    showPerPage?: boolean;
    loading?: boolean;
    showApply?: boolean;
  }>(),
  {
    searchPlaceholder: undefined,
    showPerPage: true,
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
  const rawOptions = props.perPageOptions.filter((option) => Number.isFinite(option) && option > 0);

  const availableOptions =
    typeof props.totalCount === 'number' && props.totalCount >= 0
      ? rawOptions.filter((option) => option <= props.totalCount)
      : rawOptions;

  return availableOptions.map((option) => ({
    label: t('admin.toolbar.perPage', { count: option }),
    value: option,
  }));
});

const showPerPageControl = computed(() => {
  return props.showPerPage && perPageSelectOptions.value.length > 0;
});

const effectivePerPage = computed(() => {
  const values = perPageSelectOptions.value.map((option) => Number(option.value));
  if (values.includes(props.perPage)) {
    return props.perPage;
  }

  return values[0] ?? props.perPage;
});

const toolbarClass = computed(() => {
  if (props.showApply && showPerPageControl.value) {
    return 'grid gap-3 lg:grid-cols-[1fr_auto_auto_auto]';
  }

  if (props.showApply && !showPerPageControl.value) {
    return 'grid gap-3 lg:grid-cols-[1fr_auto_auto]';
  }

  if (!props.showApply && showPerPageControl.value) {
    return 'grid gap-3 lg:grid-cols-[1fr_auto_auto]';
  }

  return 'grid gap-3 lg:grid-cols-[1fr_auto]';
});

const onPerPageChange = (value: string | number | (string | number)[]) => {
  const parsed = Number(Array.isArray(value) ? value[0] : (value ?? props.perPage));
  emit('update:perPage', Number.isFinite(parsed) ? parsed : props.perPage);
};
</script>

<style lang="scss" scoped src="./AdminListToolbar.scss"></style>
