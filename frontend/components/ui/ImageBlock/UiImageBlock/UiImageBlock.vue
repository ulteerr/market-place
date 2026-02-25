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
      <article
        v-for="(image, index) in images"
        :key="image.id || image.src"
        :class="styles.item"
        data-testid="ui-image-block-item"
      >
        <img :src="image.src" :alt="image.alt || `image-${index + 1}`" :class="styles.image" />

        <div :class="styles.overlay">
          <span :class="styles.caption">{{
            image.caption || `${captionPrefix} ${index + 1}`
          }}</span>

          <button
            v-if="removable"
            type="button"
            :class="styles.removeButton"
            @click="emit('remove', index)"
          >
            {{ removeButtonText }}
          </button>
        </div>
      </article>

      <article v-if="!images.length" :class="styles.empty">
        <p>{{ emptyText }}</p>
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
    removeButtonText?: string;
    emptyText?: string;
    captionPrefix?: string;
  }>(),
  {
    title: 'Изображения',
    description: '',
    removable: true,
    showAddButton: true,
    addButtonText: 'Добавить',
    removeButtonText: 'Удалить',
    emptyText: 'Пока нет изображений',
    captionPrefix: 'Изображение',
  }
);

const emit = defineEmits<{
  (event: 'add'): void;
  (event: 'remove', index: number): void;
}>();
</script>
