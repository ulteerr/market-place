<template>
  <div class="admin-tag-filter">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      :class="['admin-tag-filter-item', { 'is-active': isSelected(option.value) }]"
      :data-testid="`admin-tag-${option.value}`"
      @click="toggle(option.value)"
    >
      {{ option.label }}
    </button>
  </div>
</template>

<script setup lang="ts">
interface TagFilterOption {
  value: string;
  label: string;
}

const props = withDefaults(
  defineProps<{
    modelValue: string[];
    options: TagFilterOption[];
    mode?: 'single' | 'multiple';
  }>(),
  {
    mode: 'multiple',
  }
);

const emit = defineEmits<{
  (event: 'update:modelValue', value: string[]): void;
}>();

const isSelected = (value: string): boolean => {
  return props.modelValue.includes(value);
};

const toggle = (value: string) => {
  if (props.mode === 'single') {
    if (props.modelValue[0] === value) {
      emit('update:modelValue', []);
      return;
    }

    emit('update:modelValue', [value]);
    return;
  }

  if (isSelected(value)) {
    emit(
      'update:modelValue',
      props.modelValue.filter((item) => item !== value)
    );
    return;
  }

  emit('update:modelValue', [...props.modelValue, value]);
};
</script>

<style scoped lang="scss">
.admin-tag-filter {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
}

.admin-tag-filter-item {
  border: 1px solid var(--border);
  background: color-mix(in srgb, var(--surface) 88%, transparent);
  color: var(--text);
  border-radius: 0.85rem;
  padding: 0.55rem 0.95rem;
  font-size: 0.95rem;
  transition:
    color 0.2s ease,
    border-color 0.2s ease,
    background 0.2s ease;
}

.admin-tag-filter-item:hover {
  border-color: var(--accent);
  color: var(--accent);
}

.admin-tag-filter-item.is-active {
  border-color: var(--accent);
  color: var(--accent);
  background: color-mix(in srgb, var(--surface) 72%, transparent);
}
</style>
