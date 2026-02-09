<template>
  <div :class="styles.field">
    <div :class="styles.row">
      <button
        :id="resolvedId"
        type="button"
        role="switch"
        :aria-checked="String(modelValue)"
        :aria-label="label || name || 'Switch'"
        :disabled="disabled"
        :class="[
          styles.control,
          modelValue ? styles.controlChecked : '',
          error ? styles.controlError : '',
        ]"
        @click="onToggle"
      >
        <span :class="styles.thumb" aria-hidden="true" />
      </button>

      <div :class="styles.texts">
        <span v-if="label" :class="styles.label">
          {{ label }}
          <span v-if="required" :class="styles.required">*</span>
        </span>
        <span v-if="description" :class="styles.description">{{ description }}</span>
      </div>
    </div>

    <span v-if="error" :class="styles.error">{{ error }}</span>
    <span v-else-if="hint" :class="styles.hint">{{ hint }}</span>
  </div>
</template>

<script setup lang="ts">
import styles from './UiSwitch.module.scss';

const props = withDefaults(
  defineProps<{
    modelValue?: boolean;
    id?: string;
    name?: string;
    label?: string;
    description?: string;
    hint?: string;
    error?: string;
    required?: boolean;
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
    required: false,
    disabled: false,
  }
);

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void;
}>();

const uid = useId();
const resolvedId = computed(() => props.id || `ui-switch-${uid}`);

const onToggle = () => {
  if (props.disabled) {
    return;
  }

  emit('update:modelValue', !props.modelValue);
};
</script>
