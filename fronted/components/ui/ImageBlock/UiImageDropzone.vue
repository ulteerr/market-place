<template>
  <section
    :class="[
      styles.zone,
      isDragging ? styles.zoneDragging : '',
      disabled ? styles.zoneDisabled : '',
    ]"
    @dragenter.prevent="onDragEnter"
    @dragover.prevent="onDragOver"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
  >
    <input
      ref="inputRef"
      type="file"
      :multiple="multiple"
      :accept="accept"
      :class="styles.input"
      :disabled="disabled"
      @change="onInputChange"
    />

    <p :class="styles.title">{{ title }}</p>
    <p :class="styles.description">{{ description }}</p>

    <button type="button" :class="styles.browse" :disabled="disabled" @click="openPicker">
      {{ browseButtonText }}
    </button>
  </section>
</template>

<script setup lang="ts">
import styles from './UiImageDropzone.module.scss';

const props = withDefaults(
  defineProps<{
    modelValue?: File[];
    accept?: string;
    multiple?: boolean;
    title?: string;
    description?: string;
    browseButtonText?: string;
    disabled?: boolean;
  }>(),
  {
    modelValue: () => [],
    accept: 'image/*',
    multiple: true,
    title: 'Перетащите изображения сюда',
    description: 'Или кликните на кнопку, чтобы выбрать файлы.',
    browseButtonText: 'Выбрать файлы',
    disabled: false,
  }
);

const emit = defineEmits<{
  (event: 'update:modelValue', value: File[]): void;
  (event: 'files-added', value: File[]): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const isAcceptedFile = (file: File): boolean => {
  if (!props.accept || props.accept === '*/*') {
    return true;
  }

  const rules = props.accept
    .split(',')
    .map((value) => value.trim().toLowerCase())
    .filter(Boolean);

  if (!rules.length) {
    return true;
  }

  const mimeType = (file.type || '').toLowerCase();
  const extension = file.name.includes('.') ? `.${file.name.split('.').pop()?.toLowerCase()}` : '';

  return rules.some((rule) => {
    if (rule.endsWith('/*')) {
      const prefix = rule.slice(0, -1);
      return mimeType.startsWith(prefix);
    }

    if (rule.startsWith('.')) {
      return extension === rule;
    }

    return mimeType === rule;
  });
};

const appendFiles = (files: File[]) => {
  if (props.disabled) {
    return;
  }

  const next = props.multiple ? [...props.modelValue, ...files] : files.slice(0, 1);
  emit('update:modelValue', next);
  emit('files-added', files);
};

const onInputChange = (event: Event) => {
  const target = event.target as HTMLInputElement | null;
  const files = Array.from(target?.files ?? []).filter(isAcceptedFile);
  if (!files.length) {
    return;
  }

  appendFiles(files);

  if (target) {
    target.value = '';
  }
};

const onDragEnter = () => {
  if (props.disabled) {
    return;
  }

  isDragging.value = true;
};

const onDragOver = () => {
  if (props.disabled) {
    return;
  }

  isDragging.value = true;
};

const onDragLeave = (event: DragEvent) => {
  if (!event.currentTarget) {
    isDragging.value = false;
    return;
  }

  const target = event.currentTarget as HTMLElement;
  const relatedTarget = event.relatedTarget as Node | null;

  if (!relatedTarget || !target.contains(relatedTarget)) {
    isDragging.value = false;
  }
};

const onDrop = (event: DragEvent) => {
  if (props.disabled) {
    isDragging.value = false;
    return;
  }

  isDragging.value = false;

  const files = Array.from(event.dataTransfer?.files ?? []).filter(isAcceptedFile);

  if (!files.length) {
    return;
  }

  appendFiles(files);
};

const openPicker = () => {
  if (props.disabled) {
    return;
  }

  inputRef.value?.click();
};
</script>
