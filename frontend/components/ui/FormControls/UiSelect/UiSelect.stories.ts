import type { Meta, StoryObj } from '@storybook/vue3';
import { ref } from 'vue';
import UiSelect from './UiSelect.vue';

const optionsRu = [
  { label: 'Пользователи', value: 'users' },
  { label: 'Роли', value: 'roles' },
  { label: 'Организации', value: 'organizations' },
  { label: 'Логи', value: 'logs' },
];
const optionsEn = [
  { label: 'Users', value: 'users' },
  { label: 'Roles', value: 'roles' },
  { label: 'Organizations', value: 'organizations' },
  { label: 'Logs', value: 'logs' },
];

const meta = {
  title: 'UI/Form Controls/UiSelect',
  component: UiSelect,
  tags: ['autodocs'],
  args: {
    label: 'Раздел',
    modelValue: 'users',
    options: optionsRu,
    placeholder: 'Выберите раздел',
    hint: 'Используется в фильтрах админки',
    error: '',
    required: false,
    disabled: false,
    searchable: true,
    multiple: false,
    allowCreate: false,
    lockedValues: [],
  },
  parameters: {
    localeArgs: {
      ru: {
        label: 'Раздел',
        options: optionsRu,
        placeholder: 'Выберите раздел',
        hint: 'Используется в фильтрах админки',
      },
      en: {
        label: 'Section',
        options: optionsEn,
        placeholder: 'Select section',
        hint: 'Used in admin filters',
      },
    },
  },
  render: (args) => ({
    components: { UiSelect },
    setup() {
      const model = ref(args.modelValue);
      return { args, model };
    },
    template: '<UiSelect v-bind="args" v-model="model" />',
  }),
} satisfies Meta<typeof UiSelect>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Multiple: Story = {
  args: {
    multiple: true,
    modelValue: ['users', 'roles'],
    placeholder: 'Добавьте разделы',
  },
  parameters: {
    localeArgs: {
      ru: { placeholder: 'Добавьте разделы' },
      en: { placeholder: 'Add sections' },
    },
  },
};

export const CreatableTags: Story = {
  args: {
    multiple: true,
    modelValue: [],
    allowCreate: true,
    placeholder: 'Введите и нажмите Enter',
  },
  parameters: {
    localeArgs: {
      ru: { placeholder: 'Введите и нажмите Enter' },
      en: { placeholder: 'Type and press Enter' },
    },
  },
};

export const WithError: Story = {
  args: {
    error: 'Выберите хотя бы один раздел',
    hint: '',
    modelValue: null,
  },
  parameters: {
    localeArgs: {
      ru: { error: 'Выберите хотя бы один раздел' },
      en: { error: 'Select at least one section' },
    },
  },
};
