<template>
  <div ref="rootRef" :class="styles.field">
    <label v-if="label" :for="resolvedId" :class="styles.label">
      {{ label }}
      <span v-if="required" :class="styles.required">*</span>
    </label>

    <div
      :class="[styles.control, isOpen ? styles.controlOpen : '', error ? styles.controlError : '']"
    >
      <div v-if="multiple && selectedOptions.length" :class="styles.tags">
        <button
          v-for="option in selectedOptions"
          :key="String(option.value)"
          type="button"
          :class="styles.tag"
          @click="removeTag(option.value)"
        >
          {{ option.label }}
          <span :class="styles.tagClose">×</span>
        </button>
      </div>

      <input
        :id="resolvedId"
        :value="inputValue"
        :class="styles.input"
        :placeholder="inputPlaceholder"
        :disabled="disabled"
        :readonly="!searchable"
        :required="required && !selectedValues.length"
        :name="name"
        autocomplete="off"
        @input="onInput"
        @focus="open"
        @click="open"
        @keydown.down.prevent="open"
        @keydown.esc="close"
        @keydown.enter.prevent="onEnter"
      />

      <button type="button" :class="styles.toggle" :disabled="disabled" @click="toggle">
        <span :class="[styles.arrow, isOpen ? styles.arrowOpen : '']" aria-hidden="true" />
      </button>
    </div>

    <div v-if="isOpen && !disabled" :class="styles.menu" role="listbox">
      <button
        v-for="option in filteredOptions"
        :key="String(option.value)"
        type="button"
        :class="[styles.option, isSelected(option.value) ? styles.optionSelected : '']"
        :disabled="option.disabled"
        @click="selectOption(option.value)"
      >
        {{ option.label }}
      </button>

      <button
        v-if="canCreateTag"
        type="button"
        :class="[styles.option, styles.optionCreate]"
        @click="createTag"
      >
        Создать тег "{{ normalizedQuery }}"
      </button>

      <p v-if="!filteredOptions.length && !canCreateTag" :class="styles.empty">Ничего не найдено</p>
    </div>

    <span v-if="error" :class="styles.error">{{ error }}</span>
    <span v-else-if="hint" :class="styles.hint">{{ hint }}</span>
  </div>
</template>

<script setup lang="ts">
import styles from './UiSelect.module.scss';

type SelectValue = string | number;

interface SelectOption {
  label: string;
  value: SelectValue;
  disabled?: boolean;
}

const props = withDefaults(
  defineProps<{
    modelValue?: SelectValue | SelectValue[] | null;
    id?: string;
    name?: string;
    label?: string;
    placeholder?: string;
    hint?: string;
    error?: string;
    options: SelectOption[];
    required?: boolean;
    disabled?: boolean;
    searchable?: boolean;
    multiple?: boolean;
    allowCreate?: boolean;
  }>(),
  {
    modelValue: null,
    id: '',
    name: '',
    label: '',
    placeholder: 'Выберите значение',
    hint: '',
    error: '',
    required: false,
    disabled: false,
    searchable: true,
    multiple: false,
    allowCreate: false,
  }
);

const emit = defineEmits<{
  (event: 'update:modelValue', value: SelectValue | SelectValue[]): void;
  (event: 'create', value: SelectOption): void;
}>();

const uid = useId();
const resolvedId = computed(() => props.id || `ui-select-${uid}`);
const rootRef = ref<HTMLElement | null>(null);
const isOpen = ref(false);
const query = ref('');
const createdOptions = ref<SelectOption[]>([]);

const allOptions = computed(() => [...props.options, ...createdOptions.value]);

const selectedValues = computed<SelectValue[]>(() => {
  if (props.multiple) {
    return Array.isArray(props.modelValue) ? props.modelValue : [];
  }

  if (props.modelValue === null || Array.isArray(props.modelValue)) {
    return [];
  }

  return [props.modelValue];
});

const selectedOptions = computed(() => {
  return allOptions.value.filter((option) => selectedValues.value.includes(option.value));
});

const normalizedQuery = computed(() => query.value.trim());

const inputPlaceholder = computed(() => {
  if (props.multiple) {
    return selectedValues.value.length ? 'Добавить еще...' : props.placeholder;
  }

  if (!isOpen.value && selectedOptions.value[0]) {
    return selectedOptions.value[0].label;
  }

  return props.placeholder;
});

const inputValue = computed(() => {
  if (!props.searchable && !props.multiple) {
    return selectedOptions.value[0]?.label ?? '';
  }

  return query.value;
});

const filteredOptions = computed(() => {
  const currentQuery = normalizedQuery.value.toLowerCase();

  return allOptions.value.filter((option) => {
    if (props.multiple && selectedValues.value.includes(option.value)) {
      return false;
    }

    if (!currentQuery || !props.searchable) {
      return true;
    }

    return option.label.toLowerCase().includes(currentQuery);
  });
});

const canCreateTag = computed(() => {
  if (!props.allowCreate || !normalizedQuery.value) {
    return false;
  }

  const queryLower = normalizedQuery.value.toLowerCase();
  return !allOptions.value.some((option) => option.label.toLowerCase() === queryLower);
});

const isSelected = (value: SelectValue) => selectedValues.value.includes(value);

const onInput = (event: Event) => {
  if (!props.searchable) {
    return;
  }

  const target = event.target as HTMLInputElement | null;
  query.value = target?.value ?? '';
};

const open = () => {
  if (props.disabled) {
    return;
  }

  isOpen.value = true;
};

const close = () => {
  isOpen.value = false;

  if (!props.multiple && selectedOptions.value[0] && !query.value.trim()) {
    query.value = '';
  }
};

const toggle = () => {
  if (isOpen.value) {
    close();
    return;
  }

  open();
};

const emitSingleValue = (value: SelectValue) => {
  emit('update:modelValue', value);
};

const emitMultipleValue = (values: SelectValue[]) => {
  emit('update:modelValue', values);
};

const selectOption = (value: SelectValue) => {
  if (props.multiple) {
    if (!selectedValues.value.includes(value)) {
      emitMultipleValue([...selectedValues.value, value]);
    }

    query.value = '';
    return;
  }

  emitSingleValue(value);
  query.value = '';
  close();
};

const removeTag = (value: SelectValue) => {
  if (!props.multiple) {
    return;
  }

  emitMultipleValue(selectedValues.value.filter((item) => item !== value));
};

const createTag = () => {
  const label = normalizedQuery.value;
  if (!label) {
    return;
  }

  const newOption: SelectOption = {
    label,
    value: `tag-${label.toLowerCase().replace(/\s+/g, '-')}`,
  };

  createdOptions.value.push(newOption);
  emit('create', newOption);
  selectOption(newOption.value);
};

const onEnter = () => {
  if (canCreateTag.value) {
    createTag();
    return;
  }

  if (filteredOptions.value[0]) {
    selectOption(filteredOptions.value[0].value);
  }
};

const onClickOutside = (event: MouseEvent) => {
  const target = event.target as Node | null;
  if (!target || !rootRef.value || rootRef.value.contains(target)) {
    return;
  }

  close();
};

watch(
  () => props.modelValue,
  () => {
    if (!props.multiple && !isOpen.value) {
      query.value = '';
    }
  }
);

onMounted(() => {
  document.addEventListener('mousedown', onClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onClickOutside);
});
</script>
