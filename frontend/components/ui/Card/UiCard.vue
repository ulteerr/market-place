<template>
  <component
    :is="tag"
    :class="[
      styles.card,
      styles[`variant${normalizedVariant}`],
      styles[`padding${normalizedPadding}`],
      interactive ? styles.interactive : '',
    ]"
    :data-test="dataTest"
  >
    <header v-if="$slots.header" :class="styles.header">
      <slot name="header" />
    </header>

    <div :class="styles.body">
      <slot />
    </div>

    <footer v-if="$slots.footer" :class="styles.footer">
      <slot name="footer" />
    </footer>
  </component>
</template>

<script setup lang="ts">
import styles from './UiCard.module.scss';

type CardVariant = 'default' | 'elevated' | 'outline';
type CardPadding = 'sm' | 'md' | 'lg';

const props = withDefaults(
  defineProps<{
    tag?: string;
    variant?: CardVariant;
    padding?: CardPadding;
    interactive?: boolean;
    dataTest?: string;
  }>(),
  {
    tag: 'article',
    variant: 'default',
    padding: 'md',
    interactive: false,
    dataTest: 'ui-card',
  }
);

const normalizedVariant = computed(() => {
  const allowed: CardVariant[] = ['default', 'elevated', 'outline'];

  return allowed.includes(props.variant) ? props.variant : 'default';
});

const normalizedPadding = computed(() => {
  const allowed: CardPadding[] = ['sm', 'md', 'lg'];

  return allowed.includes(props.padding) ? props.padding : 'md';
});
</script>
