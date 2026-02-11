<template>
  <div ref="rootRef" :class="styles.dropdown">
    <span v-if="label" :class="styles.label">{{ label }}</span>

    <button
      type="button"
      :class="[styles.trigger, isOpen ? styles.triggerOpen : '']"
      :disabled="disabled"
      @click="toggle"
    >
      <span>{{ currentLabel }}</span>
      <span :class="[styles.chevron, isOpen ? styles.chevronOpen : '']" aria-hidden="true" />
    </button>

    <div v-if="isOpen" :class="styles.menu" role="listbox">
      <button
        v-for="option in options"
        :key="String(option.value)"
        type="button"
        :class="[styles.option, isSelected(option.value) ? styles.optionSelected : '']"
        :disabled="option.disabled"
        @click="select(option.value)"
      >
        {{ option.label }}
      </button>
    </div>

    <span v-if="error" :class="styles.error">{{ error }}</span>
    <span v-else-if="hint" :class="styles.hint">{{ hint }}</span>
  </div>
</template>

<script setup lang="ts">
import styles from './UiDropdown.module.scss';

interface DropdownOption {
  label: string;
  value: string | number;
  disabled?: boolean;
}

const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null;
    options: DropdownOption[];
    label?: string;
    placeholder?: string;
    hint?: string;
    error?: string;
    disabled?: boolean;
  }>(),
  {
    modelValue: '',
    label: '',
    placeholder: 'Выберите значение',
    hint: '',
    error: '',
    disabled: false,
  }
);

const emit = defineEmits<{
  (event: 'update:modelValue', value: string | number): void;
}>();

const rootRef = ref<HTMLElement | null>(null);
const isOpen = ref(false);

const currentLabel = computed(() => {
  const currentOption = props.options.find((option) => option.value === props.modelValue);
  return currentOption?.label ?? props.placeholder;
});

const isSelected = (value: string | number) => value === props.modelValue;

const toggle = () => {
  if (props.disabled) {
    return;
  }

  isOpen.value = !isOpen.value;
};

const close = () => {
  isOpen.value = false;
};

const select = (value: string | number) => {
  emit('update:modelValue', value);
  close();
};

const onDocumentClick = (event: MouseEvent) => {
  const target = event.target as Node | null;

  if (!target || !rootRef.value || rootRef.value.contains(target)) {
    return;
  }

  close();
};

const onEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    close();
  }
};

onMounted(() => {
  document.addEventListener('mousedown', onDocumentClick);
  document.addEventListener('keydown', onEscape);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocumentClick);
  document.removeEventListener('keydown', onEscape);
});
</script>
