<template>
  <label :for="resolvedId" :class="[styles.root, disabled ? styles.rootDisabled : '']">
    <input
      :id="resolvedId"
      :name="name"
      :checked="modelValue"
      :disabled="disabled"
      type="checkbox"
      :class="styles.native"
      @change="onChange"
    />

    <span
      :class="[styles.box, modelValue ? styles.boxChecked : '', error ? styles.boxError : '']"
      aria-hidden="true"
    >
      <svg v-if="modelValue" viewBox="0 0 16 16" :class="styles.icon" focusable="false">
        <path
          d="M3.2 8.2l2.9 2.9 6.7-6.7"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </span>

    <span :class="styles.content">
      <span v-if="label" :class="styles.label">{{ label }}</span>
      <span v-if="description" :class="styles.description">{{ description }}</span>
      <span v-if="error" :class="styles.error">{{ error }}</span>
      <span v-else-if="hint" :class="styles.hint">{{ hint }}</span>
    </span>
  </label>
</template>

<script setup lang="ts">
import styles from './UiCheckbox.module.scss';

const props = withDefaults(
  defineProps<{
    modelValue?: boolean;
    id?: string;
    name?: string;
    label?: string;
    description?: string;
    hint?: string;
    error?: string;
    disabled?: boolean;
  }>(),
  {
    modelValue: false,
    id: '',
    name: '',
    label: '',
    description: '',
    hint: '',
    error: '',
    disabled: false,
  }
);

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void;
}>();

const uid = useId();
const resolvedId = computed(() => props.id || `ui-checkbox-${uid}`);

const onChange = (event: Event) => {
  const target = event.target as HTMLInputElement | null;
  emit('update:modelValue', Boolean(target?.checked));
};
</script>
