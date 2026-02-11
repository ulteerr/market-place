<template>
  <section :class="styles.block">
    <header :class="styles.header">
      <div>
        <h3 :class="styles.title">{{ title }}</h3>
        <p v-if="description" :class="styles.description">{{ description }}</p>
      </div>

      <button v-if="showAddButton" type="button" :class="styles.addButton" @click="emit('add')">
        {{ addButtonText }}
      </button>
    </header>

    <div :class="styles.grid">
      <article v-for="(image, index) in images" :key="image.id || image.src" :class="styles.item">
        <img :src="image.src" :alt="image.alt || `image-${index + 1}`" :class="styles.image" />

        <div :class="styles.overlay">
          <span :class="styles.caption">{{ image.caption || `Изображение ${index + 1}` }}</span>

          <button
            v-if="removable"
            type="button"
            :class="styles.removeButton"
            @click="emit('remove', index)"
          >
            Удалить
          </button>
        </div>
      </article>

      <article v-if="!images.length" :class="styles.empty">
        <p>Пока нет изображений</p>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import styles from './UiImageBlock.module.scss';

interface ImageBlockItem {
  id?: string | number;
  src: string;
  alt?: string;
  caption?: string;
}

withDefaults(
  defineProps<{
    title?: string;
    description?: string;
    images: ImageBlockItem[];
    removable?: boolean;
    showAddButton?: boolean;
    addButtonText?: string;
  }>(),
  {
    title: 'Изображения',
    description: '',
    removable: true,
    showAddButton: true,
    addButtonText: 'Добавить',
  }
);

const emit = defineEmits<{
  (event: 'add'): void;
  (event: 'remove', index: number): void;
}>();
</script>
