import type { Meta, StoryObj } from '@storybook/vue3';
import { createVModelRender } from '@/.storybook/vue-vmodel-render';
import UiCodeEditor from './UiCodeEditor.vue';

const meta = {
  title: 'UI/Form Controls/UiCodeEditor',
  component: UiCodeEditor,
  tags: ['autodocs'],
  argTypes: {
    mode: {
      control: 'select',
      options: ['plain', 'json', 'wysiwyg'],
    },
  },
  args: {
    label: 'Шаблон уведомления',
    modelValue: '{\n  "title": "Welcome",\n  "enabled": true\n}',
    mode: 'plain',
    modeOptions: ['plain', 'json', 'wysiwyg'],
    hint: 'Можно переключить режим редактирования',
    error: '',
    showToolbar: true,
    showModeSwitcher: true,
    allowImageUpload: false,
    readonly: false,
    disabled: false,
    lineNumbers: true,
    tabSize: 2,
    minHeight: '14rem',
    maxHeight: '',
  },
  parameters: {
    localeArgs: {
      ru: {
        label: 'Шаблон уведомления',
        hint: 'Можно переключить режим редактирования',
      },
      en: {
        label: 'Notification template',
        hint: 'You can switch editor mode',
      },
    },
  },
  render: createVModelRender(UiCodeEditor, 'UiCodeEditor'),
} satisfies Meta<typeof UiCodeEditor>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const JsonMode: Story = {
  args: {
    mode: 'json',
    modelValue: '{\n  "feature": "storyboard",\n  "enabled": true\n}',
  },
};

export const WysiwygMode: Story = {
  args: {
    mode: 'wysiwyg',
    modelValue: '<p><strong>Привет!</strong> Это визуальный режим.</p>',
    allowImageUpload: true,
  },
  parameters: {
    localeArgs: {
      ru: {
        modelValue: '<p><strong>Привет!</strong> Это визуальный режим.</p>',
      },
      en: {
        modelValue: '<p><strong>Hello!</strong> This is visual mode.</p>',
      },
    },
  },
};

export const WithError: Story = {
  args: {
    error: 'Невалидный JSON',
    hint: '',
    mode: 'json',
    modelValue: '{"broken": true,}',
  },
  parameters: {
    localeArgs: {
      ru: { error: 'Невалидный JSON' },
      en: { error: 'Invalid JSON' },
    },
  },
};
