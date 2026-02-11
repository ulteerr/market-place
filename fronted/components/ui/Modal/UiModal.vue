<template>
  <Teleport to="body">
    <div v-if="modelValue" :class="styles.overlay" @click.self="onBackdropClick">
      <div
        :class="styles.dialog"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="title ? titleId : undefined"
      >
        <header v-if="title || $slots.header" :class="styles.header">
          <slot name="header">
            <h3 :id="titleId" :class="styles.title">
              {{ title }}
            </h3>
          </slot>
        </header>

        <section :class="styles.body">
          <slot>
            <p v-if="message" :class="styles.message">
              {{ message }}
            </p>
          </slot>
        </section>

        <footer v-if="mode === 'confirm'" :class="styles.footer">
          <button
            type="button"
            :class="[styles.button, styles.buttonSecondary]"
            :disabled="confirmLoading"
            @click="onCancel"
          >
            {{ cancelLabel }}
          </button>
          <button
            type="button"
            :class="[styles.button, destructive ? styles.buttonDanger : styles.buttonPrimary]"
            :disabled="confirmLoading || confirmDisabled"
            @click="onConfirm"
          >
            {{ confirmLoading ? loadingLabel : confirmLabel }}
          </button>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import styles from './UiModal.module.scss';

const props = withDefaults(
  defineProps<{
    modelValue: boolean;
    mode?: 'default' | 'confirm';
    title?: string;
    message?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    loadingLabel?: string;
    confirmLoading?: boolean;
    confirmDisabled?: boolean;
    destructive?: boolean;
    closeOnBackdrop?: boolean;
  }>(),
  {
    mode: 'default',
    title: '',
    message: '',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    loadingLabel: 'Loading...',
    confirmLoading: false,
    confirmDisabled: false,
    destructive: false,
    closeOnBackdrop: true,
  }
);

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void;
  (event: 'confirm'): void;
  (event: 'cancel'): void;
  (event: 'close'): void;
}>();

const titleId = `ui-modal-title-${useId()}`;

const closeModal = () => {
  emit('update:modelValue', false);
  emit('close');
};

const onBackdropClick = () => {
  if (!props.closeOnBackdrop || props.confirmLoading) {
    return;
  }

  closeModal();
};

const onConfirm = () => {
  emit('confirm');
};

const onCancel = () => {
  emit('cancel');
  closeModal();
};

const onEscape = (event: KeyboardEvent) => {
  if (!props.modelValue || event.key !== 'Escape' || props.confirmLoading) {
    return;
  }

  closeModal();
};

onMounted(() => {
  window.addEventListener('keydown', onEscape);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onEscape);
});
</script>
