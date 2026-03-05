import type { Meta, StoryObj } from '@storybook/vue3';
import { expect, userEvent, within } from 'storybook/test';
import { ref } from 'vue';
import UiModal from './UiModal.vue';

const meta = {
  title: 'UI/Overlays/UiModal',
  component: UiModal,
  tags: ['autodocs'],
  argTypes: {
    mode: {
      control: 'select',
      options: ['default', 'confirm'],
    },
    confirmLoading: { control: 'boolean' },
    destructive: { control: 'boolean' },
    closeOnBackdrop: { control: 'boolean' },
  },
  args: {
    modelValue: false,
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
  parameters: {
    localeArgs: {
      ru: {
        title: 'Подтверждение изменения',
        message: 'Вы уверены, что хотите применить новые настройки?',
        confirmLabel: 'Сохранить',
        cancelLabel: 'Отмена',
        loadingLabel: 'Сохранение...',
      },
      en: {
        title: 'Confirm change',
        message: 'Are you sure you want to apply the new settings?',
        confirmLabel: 'Save',
        cancelLabel: 'Cancel',
        loadingLabel: 'Saving...',
      },
    },
  },
  render: (args: any) => ({
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
  parameters: {
    localeArgs: {
      ru: {
        title: 'Удалить элемент',
        message: 'Действие необратимо. Продолжить?',
        confirmLabel: 'Удалить',
      },
      en: {
        title: 'Delete item',
        message: 'This action cannot be undone. Continue?',
        confirmLabel: 'Delete',
      },
    },
  },
};

export const LoadingState: Story = {
  args: {
    mode: 'confirm',
    confirmLoading: true,
  },
};

export const InteractionOpenModal: Story = {
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const trigger = canvas.getByRole('button', {
      name: /Открыть модалку|Open modal/i,
    });
    await userEvent.click(trigger);
    const dialog = canvas.getByRole('dialog');
    await expect(dialog).toBeInTheDocument();
    await expect(
      canvas.getByText(/Подтверждение изменения|Confirm change/i)
    ).toBeInTheDocument();
  },
};
