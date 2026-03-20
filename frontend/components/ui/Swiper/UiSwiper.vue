<template>
  <div :class="styles.swiper" :data-test="dataTest">
    <div :class="styles.stage">
      <button
        v-if="hasMultipleItems"
        type="button"
        :class="[styles.navButton, styles.navButtonPrev]"
        :aria-label="prevLabel"
        @click="showPrevious"
      >
        ‹
      </button>

      <div :class="styles.mainFrame">
        <img
          v-if="currentItem"
          :key="currentItem.id"
          :src="currentItem.src"
          :alt="currentItem.alt || ''"
          :class="styles.mainImage"
          :loading="currentIndex === 0 ? 'eager' : 'lazy'"
          data-test="ui-swiper-main-image"
        />
      </div>

      <button
        v-if="hasMultipleItems"
        type="button"
        :class="[styles.navButton, styles.navButtonNext]"
        :aria-label="nextLabel"
        @click="showNext"
      >
        ›
      </button>
    </div>

    <div v-if="hasMultipleItems" :class="styles.thumbs" data-test="ui-swiper-thumbs">
      <button
        v-for="(item, index) in items"
        :key="item.id"
        type="button"
        :class="[styles.thumbButton, index === currentIndex ? styles.thumbButtonActive : '']"
        :aria-label="item.alt || `${thumbLabel} ${index + 1}`"
        @click="setCurrentIndex(index)"
      >
        <img
          :src="item.thumbSrc || item.src"
          :alt="item.alt || ''"
          :class="styles.thumbImage"
          loading="lazy"
        />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import styles from './UiSwiper.module.scss';

export type UiSwiperItem = {
  id: string;
  src: string;
  alt?: string;
  thumbSrc?: string;
};

const props = withDefaults(
  defineProps<{
    items: UiSwiperItem[];
    dataTest?: string;
    prevLabel?: string;
    nextLabel?: string;
    thumbLabel?: string;
  }>(),
  {
    dataTest: 'ui-swiper',
    prevLabel: 'Previous slide',
    nextLabel: 'Next slide',
    thumbLabel: 'Slide',
  }
);

const currentIndex = ref(0);
const hasMultipleItems = computed(() => props.items.length > 1);
const currentItem = computed(() => props.items[currentIndex.value] || props.items[0] || null);

const setCurrentIndex = (index: number) => {
  currentIndex.value = index;
};

const showPrevious = () => {
  if (!props.items.length) {
    return;
  }

  currentIndex.value = (currentIndex.value - 1 + props.items.length) % props.items.length;
};

const showNext = () => {
  if (!props.items.length) {
    return;
  }

  currentIndex.value = (currentIndex.value + 1) % props.items.length;
};

watch(
  () => props.items,
  (items) => {
    if (!items.length) {
      currentIndex.value = 0;

      return;
    }

    if (currentIndex.value > items.length - 1) {
      currentIndex.value = 0;
    }
  },
  { deep: true }
);
</script>
