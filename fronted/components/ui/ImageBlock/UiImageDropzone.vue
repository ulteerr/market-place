<template>
  <section
    :class="[styles.zone, isDragging ? styles.zoneDragging : '']"
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
      @change="onInputChange"
    />

    <p :class="styles.title">{{ title }}</p>
    <p :class="styles.description">{{ description }}</p>

    <button type="button" :class="styles.browse" @click="openPicker">Выбрать файлы</button>
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
  }>(),
  {
    modelValue: () => [],
    accept: 'image/*',
    multiple: true,
    title: 'Перетащите изображения сюда',
    description: 'Или кликните на кнопку, чтобы выбрать файлы.',
  }
);

const emit = defineEmits<{
  (event: 'update:modelValue', value: File[]): void;
  (event: 'files-added', value: File[]): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const appendFiles = (files: File[]) => {
  const next = props.multiple ? [...props.modelValue, ...files] : files.slice(0, 1);
  emit('update:modelValue', next);
  emit('files-added', files);
};

const onInputChange = (event: Event) => {
  const target = event.target as HTMLInputElement | null;
  const files = Array.from(target?.files ?? []);
  if (!files.length) {
    return;
  }

  appendFiles(files);

  if (target) {
    target.value = '';
  }
};

const onDragEnter = () => {
  isDragging.value = true;
};

const onDragOver = () => {
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
  isDragging.value = false;

  const files = Array.from(event.dataTransfer?.files ?? []).filter((file) =>
    file.type.startsWith('image/')
  );

  if (!files.length) {
    return;
  }

  appendFiles(files);
};

const openPicker = () => {
  inputRef.value?.click();
};
</script>
