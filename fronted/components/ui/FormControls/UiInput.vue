<template>
  <label :class="styles.field" :for="resolvedId">
    <span v-if="label" :class="styles.label">
      {{ label }}
      <span v-if="required" :class="styles.required">*</span>
    </span>

    <div :class="styles.controlWrap">
      <input
        :id="resolvedId"
        :class="[
          styles.control,
          hasAppend ? styles.controlWithAppend : '',
          hasInternalPasswordToggle && hasCustomAppend ? styles.controlWithDoubleAppend : '',
          error ? styles.controlError : '',
        ]"
        :value="normalizedValue"
        :name="name"
        :type="resolvedType"
        :placeholder="placeholder"
        :autocomplete="resolvedAutocomplete"
        :inputmode="resolvedInputmode"
        :required="required"
        :disabled="disabled"
        @input="onInput"
      />

      <span v-if="hasAppend" :class="styles.append">
        <slot v-if="hasCustomAppend" name="append" />

        <button
          v-if="hasInternalPasswordToggle"
          type="button"
          :class="styles.passwordToggle"
          :aria-label="isPasswordVisible ? 'Скрыть пароль' : 'Показать пароль'"
          :title="isPasswordVisible ? 'Скрыть пароль' : 'Показать пароль'"
          @click="isPasswordVisible = !isPasswordVisible"
        >
          <svg
            v-if="!isPasswordVisible"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z" stroke-width="1.8" />
            <circle cx="12" cy="12" r="3" stroke-width="1.8" />
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path d="M3 3l18 18" stroke-width="1.8" />
            <path d="M10.6 10.6A3 3 0 0 0 9 12a3 3 0 0 0 4.4 2.6" stroke-width="1.8" />
            <path
              d="M6.7 6.7A14.9 14.9 0 0 0 2 12s3.5 6 10 6c2.2 0 4-.6 5.6-1.5"
              stroke-width="1.8"
            />
            <path d="M14.9 5.2A10.9 10.9 0 0 1 22 12s-1.1 1.9-3.2 3.6" stroke-width="1.8" />
          </svg>
        </button>
      </span>
    </div>

    <span v-if="error" :class="styles.error">{{ error }}</span>
    <span v-else-if="hint" :class="styles.hint">{{ hint }}</span>
  </label>
</template>

<script setup lang="ts">
import styles from './UiInput.module.scss';

type UiInputPreset = 'text' | 'email' | 'password' | 'number' | 'phone' | 'url' | 'search';

const INPUT_PRESETS: Record<
  UiInputPreset,
  { type: string; autocomplete: string; inputmode?: string }
> = {
  text: { type: 'text', autocomplete: 'off' },
  email: { type: 'email', autocomplete: 'email', inputmode: 'email' },
  password: { type: 'password', autocomplete: 'current-password' },
  number: { type: 'number', autocomplete: 'off', inputmode: 'decimal' },
  phone: { type: 'tel', autocomplete: 'tel', inputmode: 'tel' },
  url: { type: 'url', autocomplete: 'url', inputmode: 'url' },
  search: { type: 'search', autocomplete: 'off', inputmode: 'search' },
};

const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null;
    id?: string;
    name?: string;
    label?: string;
    preset?: UiInputPreset;
    type?: string;
    placeholder?: string;
    autocomplete?: string;
    inputmode?: string;
    passwordToggle?: boolean;
    hint?: string;
    error?: string;
    required?: boolean;
    disabled?: boolean;
  }>(),
  {
    modelValue: '',
    id: '',
    name: '',
    label: '',
    preset: 'text',
    type: '',
    placeholder: '',
    autocomplete: '',
    inputmode: '',
    passwordToggle: false,
    hint: '',
    error: '',
    required: false,
    disabled: false,
  }
);
const slots = useSlots();

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void;
}>();

const uid = useId();
const resolvedId = computed(() => props.id || `ui-input-${uid}`);
const normalizedValue = computed(() => String(props.modelValue ?? ''));
const hasCustomAppend = computed(() => Boolean(slots.append));
const resolvedBaseType = computed(() => props.type || INPUT_PRESETS[props.preset].type);
const isPasswordVisible = ref(false);
const hasInternalPasswordToggle = computed(
  () => resolvedBaseType.value === 'password' && props.passwordToggle && !props.disabled
);
const resolvedType = computed(() => {
  if (!hasInternalPasswordToggle.value) {
    return resolvedBaseType.value;
  }

  return isPasswordVisible.value ? 'text' : 'password';
});
const resolvedAutocomplete = computed(
  () => props.autocomplete || INPUT_PRESETS[props.preset].autocomplete
);
const resolvedInputmode = computed(
  () => props.inputmode || INPUT_PRESETS[props.preset].inputmode || undefined
);
const hasAppend = computed(() => hasCustomAppend.value || hasInternalPasswordToggle.value);

const onInput = (event: Event) => {
  const target = event.target as HTMLInputElement | null;
  emit('update:modelValue', target?.value ?? '');
};
</script>
