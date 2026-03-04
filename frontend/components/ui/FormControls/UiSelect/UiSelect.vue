<template>
  <div ref="rootRef" :class="styles.field">
    <label v-if="label" :for="resolvedId" :class="styles.label">
      {{ label }}
      <span v-if="required" :class="styles.required">*</span>
    </label>

    <div
      :class="[styles.control, isOpen ? styles.controlOpen : '', error ? styles.controlError : '']"
      data-ui-select-control
    >
      <div v-if="multiple && selectedOptions.length" :class="styles.tags">
        <button
          v-for="option in selectedOptions"
          :key="String(option.value)"
          type="button"
          :class="styles.tag"
          :disabled="isLockedValue(option.value)"
          :title="isLockedValue(option.value) ? option.label : undefined"
          @click="removeTag(option.value)"
        >
          {{ option.label }}
          <span v-if="!isLockedValue(option.value)" :class="styles.tagClose">×</span>
        </button>
      </div>

      <input
        :id="resolvedId"
        :value="inputValue"
        :class="[styles.input, showSelectedColor ? styles.inputWithColor : '']"
        :placeholder="inputPlaceholder"
        :disabled="disabled"
        :readonly="!searchable"
        :required="required && !selectedValues.length"
        :name="name"
        role="combobox"
        :aria-expanded="isOpen"
        :aria-controls="listboxId"
        :aria-activedescendant="activeDescendantId"
        :aria-autocomplete="searchable ? 'list' : 'none'"
        :aria-haspopup="'listbox'"
        autocomplete="off"
        data-ui-select-input
        @input="onInput"
        @focus="open"
        @click="open"
        @keydown.down.prevent="onArrowDown"
        @keydown.up.prevent="onArrowUp"
        @keydown.esc="close"
        @keydown.enter.prevent="onEnter"
      />
      <span
        v-if="showSelectedColor"
        :class="styles.selectedColor"
        :style="{ backgroundColor: singleSelectedOption?.color || 'transparent' }"
        aria-hidden="true"
      />

      <button
        type="button"
        :class="styles.toggle"
        :disabled="disabled"
        data-ui-select-toggle
        @click="toggle"
      >
        <span :class="[styles.arrow, isOpen ? styles.arrowOpen : '']" aria-hidden="true" />
      </button>
    </div>

    <div
      v-if="isOpen && !disabled"
      :id="listboxId"
      :class="styles.menu"
      role="listbox"
      :aria-multiselectable="multiple || undefined"
    >
      <button
        v-for="(option, index) in filteredOptions"
        :key="String(option.value)"
        :id="getOptionId(option.value)"
        type="button"
        :class="[styles.option, isSelected(option.value) ? styles.optionSelected : '']"
        role="option"
        :aria-selected="isSelected(option.value)"
        :disabled="option.disabled"
        @mousemove="setActiveIndex(index)"
        @click="selectOption(option.value)"
      >
        <span
          v-if="option.color"
          :class="styles.optionColor"
          :style="{ backgroundColor: option.color }"
          aria-hidden="true"
        />
        <span>{{ option.label }}</span>
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
  color?: string | null;
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
    lockedValues?: SelectValue[];
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
    lockedValues: () => [],
  }
);

const emit = defineEmits<{
  (event: 'update:modelValue', value: SelectValue | SelectValue[]): void;
  (event: 'create', value: SelectOption): void;
  (event: 'search', value: string): void;
}>();

const uid = useId();
const resolvedId = computed(() => props.id || `ui-select-${uid}`);
const listboxId = computed(() => `${resolvedId.value}-listbox`);
const rootRef = ref<HTMLElement | null>(null);
const isOpen = ref(false);
const activeIndex = ref(-1);
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

const selectedSet = computed(() => new Set(selectedValues.value));
const lockedSet = computed(() => new Set(props.lockedValues));
const selectedOptions = computed(() => {
  return allOptions.value.filter((option) => selectedSet.value.has(option.value));
});
const singleSelectedOption = computed(() => {
  if (props.multiple) {
    return null;
  }
  return selectedOptions.value[0] ?? null;
});

const normalizedQuery = computed(() => query.value.trim());
const showSelectedColor = computed(() => {
  return Boolean(
    !props.multiple && !isOpen.value && !normalizedQuery.value && singleSelectedOption.value?.color
  );
});

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
  const queryTokens = currentQuery
    .split(/\s+/u)
    .map((token) => token.trim())
    .filter((token) => token.length > 0);

  return allOptions.value.filter((option) => {
    if (props.multiple && selectedSet.value.has(option.value)) {
      return false;
    }

    if (!currentQuery || !props.searchable) {
      return true;
    }

    const normalizedLabel = option.label.toLowerCase();
    return queryTokens.every((token) => normalizedLabel.includes(token));
  });
});

const canCreateTag = computed(() => {
  if (!props.allowCreate || !normalizedQuery.value) {
    return false;
  }

  const queryLower = normalizedQuery.value.toLowerCase();
  return !allOptions.value.some((option) => option.label.toLowerCase() === queryLower);
});

const activeDescendantId = computed(() => {
  if (!isOpen.value || activeIndex.value < 0) {
    return undefined;
  }

  const option = filteredOptions.value[activeIndex.value];
  return option ? getOptionId(option.value) : undefined;
});

const getOptionId = (value: SelectValue): string => {
  const normalizedValue = String(value)
    .trim()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9\-_]/gi, '')
    .toLowerCase();
  return `${resolvedId.value}-option-${normalizedValue || 'value'}`;
};

const findFirstEnabledIndex = (): number =>
  filteredOptions.value.findIndex((option) => !option.disabled);

const findLastEnabledIndex = (): number => {
  for (let index = filteredOptions.value.length - 1; index >= 0; index -= 1) {
    if (!filteredOptions.value[index]?.disabled) {
      return index;
    }
  }
  return -1;
};

const findNearestEnabledIndex = (start: number, direction: 1 | -1): number => {
  const optionsCount = filteredOptions.value.length;
  if (!optionsCount) {
    return -1;
  }

  let index = start;
  for (let i = 0; i < optionsCount; i += 1) {
    index = (index + direction + optionsCount) % optionsCount;
    if (!filteredOptions.value[index]?.disabled) {
      return index;
    }
  }

  return -1;
};

const getDefaultActiveIndex = (): number => {
  const selectedIndex = filteredOptions.value.findIndex(
    (option) => selectedSet.value.has(option.value) && !option.disabled
  );
  return selectedIndex >= 0 ? selectedIndex : findFirstEnabledIndex();
};

const setActiveIndex = (index: number): void => {
  if (index < 0 || index >= filteredOptions.value.length) {
    return;
  }

  if (filteredOptions.value[index]?.disabled) {
    return;
  }

  activeIndex.value = index;
};

const isSelected = (value: SelectValue) => selectedSet.value.has(value);
const isLockedValue = (value: SelectValue) => lockedSet.value.has(value);

const onInput = (event: Event) => {
  if (!props.searchable) {
    return;
  }

  const target = event.target as HTMLInputElement | null;
  query.value = target?.value ?? '';
  emit('search', query.value);
};

const open = () => {
  if (props.disabled) {
    return;
  }

  isOpen.value = true;
  activeIndex.value = getDefaultActiveIndex();
  emit('search', query.value);
};

const close = () => {
  isOpen.value = false;
  activeIndex.value = -1;

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
    if (!selectedSet.value.has(value)) {
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

  if (isLockedValue(value)) {
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

  if (activeIndex.value >= 0 && filteredOptions.value[activeIndex.value]) {
    selectOption(filteredOptions.value[activeIndex.value].value);
    return;
  }

  if (filteredOptions.value[0] && !filteredOptions.value[0].disabled) {
    selectOption(filteredOptions.value[0].value);
  }
};

const onArrowDown = () => {
  if (!isOpen.value) {
    open();
    return;
  }

  if (!filteredOptions.value.length) {
    return;
  }

  if (activeIndex.value < 0) {
    activeIndex.value = findFirstEnabledIndex();
    return;
  }

  activeIndex.value = findNearestEnabledIndex(activeIndex.value, 1);
};

const onArrowUp = () => {
  if (!isOpen.value) {
    open();
    activeIndex.value = findLastEnabledIndex();
    return;
  }

  if (!filteredOptions.value.length) {
    return;
  }

  if (activeIndex.value < 0) {
    activeIndex.value = findLastEnabledIndex();
    return;
  }

  activeIndex.value = findNearestEnabledIndex(activeIndex.value, -1);
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

watch(filteredOptions, () => {
  if (!isOpen.value) {
    return;
  }

  if (activeIndex.value < 0 || activeIndex.value >= filteredOptions.value.length) {
    activeIndex.value = getDefaultActiveIndex();
    return;
  }

  if (filteredOptions.value[activeIndex.value]?.disabled) {
    activeIndex.value = findNearestEnabledIndex(activeIndex.value, 1);
  }
});

onMounted(() => {
  document.addEventListener('mousedown', onClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onClickOutside);
});
</script>
