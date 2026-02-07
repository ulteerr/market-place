<template>
  <label :class="styles.field" :for="resolvedId">
    <span v-if="label" :class="styles.label">
      {{ label }}
      <span v-if="required" :class="styles.required">*</span>
    </span>

    <input
      :id="resolvedId"
      :class="[styles.control, error ? styles.controlError : '']"
      :value="normalizedValue"
      :name="name"
      :type="type"
      :placeholder="placeholder"
      :autocomplete="autocomplete"
      :required="required"
      :disabled="disabled"
      @input="onInput"
    />

    <span v-if="error" :class="styles.error">{{ error }}</span>
    <span v-else-if="hint" :class="styles.hint">{{ hint }}</span>
  </label>
</template>

<script setup lang="ts">
import styles from './UiInput.module.scss'

const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null
    id?: string
    name?: string
    label?: string
    type?: string
    placeholder?: string
    autocomplete?: string
    hint?: string
    error?: string
    required?: boolean
    disabled?: boolean
  }>(),
  {
    modelValue: '',
    id: '',
    name: '',
    label: '',
    type: 'text',
    placeholder: '',
    autocomplete: 'off',
    hint: '',
    error: '',
    required: false,
    disabled: false
  }
)

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void
}>()

const uid = useId()
const resolvedId = computed(() => props.id || `ui-input-${uid}`)
const normalizedValue = computed(() => String(props.modelValue ?? ''))

const onInput = (event: Event) => {
  const target = event.target as HTMLInputElement | null
  emit('update:modelValue', target?.value ?? '')
}
</script>
