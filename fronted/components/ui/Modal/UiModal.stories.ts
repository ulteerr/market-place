import type { Meta, StoryObj } from '@storybook/vue3';
import { ref } from 'vue';
import UiModal from './UiModal.vue';

const meta = {
  title: 'UI/Overlays/UiModal',
  component: UiModal,
  tags: ['autodocs'],
  args: {
    modelValue: true,
    mode: 'default',
    title: 'Подтверждение изменения',
    message: 'Вы уверены, что хотите применить новые настройки?',
    confirmLabel: 'Сохранить',
    cancelLabel: 'Отмена',
    loadingLabel: 'Сохранение...',
    confirmLoading: false,
    confirmDisabled: false,
    destructive: false,
    closeOnBackdrop: true,
  },
  render: (args) => ({
    components: { UiModal },
    setup() {
      const open = ref(args.modelValue);
      return { args, open };
    },
    template: `
      <div>
        <button type="button" style="padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 0.5rem;" @click="open = true">
          Открыть модалку
        </button>
        <UiModal v-bind="args" v-model="open">
          <p v-if="args.mode === 'default'">{{ args.message }}</p>
        </UiModal>
      </div>
    `,
  }),
} satisfies Meta<typeof UiModal>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Confirm: Story = {
  args: {
    mode: 'confirm',
  },
};

export const Destructive: Story = {
  args: {
    mode: 'confirm',
    title: 'Удалить элемент',
    message: 'Действие необратимо. Продолжить?',
    confirmLabel: 'Удалить',
    destructive: true,
  },
};

export const LoadingState: Story = {
  args: {
    mode: 'confirm',
    confirmLoading: true,
  },
};
