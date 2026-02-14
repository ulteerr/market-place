<template>
  <div :class="[styles.root, variant === 'card' ? styles.rootCard : styles.rootTable]">
    <button
      v-if="src"
      type="button"
      :class="[styles.trigger, variant === 'card' ? styles.triggerCard : styles.triggerTable]"
      :aria-label="openAriaLabel"
      @click="isOpen = true"
    >
      <img
        :src="src"
        :alt="alt"
        :class="[styles.image, variant === 'card' ? styles.imageCard : '']"
      />
    </button>

    <div v-else :class="[styles.placeholder, variant === 'card' ? styles.placeholderCard : '']">
      <span>{{ fallbackText }}</span>
    </div>

    <UiModal v-model="isOpen" :title="previewTitle" close-on-backdrop>
      <div :class="styles.previewWrap">
        <img v-if="src" :src="src" :alt="previewAlt || alt" :class="styles.previewImage" />
      </div>
    </UiModal>
  </div>
</template>

<script setup lang="ts">
import UiModal from '~/components/ui/Modal/UiModal.vue';
import styles from './UiImagePreview.module.scss';

const props = withDefaults(
  defineProps<{
    src?: string | null;
    alt?: string;
    previewAlt?: string;
    variant?: 'table' | 'card';
    fallbackText?: string;
    previewTitle?: string;
    openAriaLabel?: string;
  }>(),
  {
    src: null,
    alt: '',
    previewAlt: '',
    variant: 'table',
    fallbackText: '—',
    previewTitle: 'Preview',
    openAriaLabel: 'Open image preview',
  }
);

const isOpen = ref(false);
</script>
