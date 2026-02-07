<template>
  <label :class="styles.field" :for="resolvedId">
    <span v-if="label" :class="styles.label">
      {{ label }}
      <span v-if="required" :class="styles.required">*</span>
    </span>

    <textarea
      :id="resolvedId"
      :class="[styles.control, error ? styles.controlError : '']"
      :value="normalizedValue"
      :name="name"
      :rows="rows"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      @input="onInput"
    />

    <span v-if="error" :class="styles.error">{{ error }}</span>
    <span v-else-if="hint" :class="styles.hint">{{ hint }}</span>
  </label>
</template>

<script setup lang="ts">
import styles from './UiTextarea.module.scss'

const props = withDefaults(
  defineProps<{
    modelValue?: string | null
    id?: string
    name?: string
    label?: string
    placeholder?: string
    hint?: string
    error?: string
    rows?: number
    required?: boolean
    disabled?: boolean
  }>(),
  {
    modelValue: '',
    id: '',
    name: '',
    label: '',
    placeholder: '',
    hint: '',
    error: '',
    rows: 4,
    required: false,
    disabled: false
  }
)

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void
}>()

const uid = useId()
const resolvedId = computed(() => props.id || `ui-textarea-${uid}`)
const normalizedValue = computed(() => props.modelValue ?? '')

const onInput = (event: Event) => {
  const target = event.target as HTMLTextAreaElement | null
  emit('update:modelValue', target?.value ?? '')
}
</script>
