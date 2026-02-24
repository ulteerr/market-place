import type { Meta, StoryObj } from '@storybook/vue3';
import { ref } from 'vue';
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import UiSwitch from '~/components/ui/FormControls/UiSwitch/UiSwitch.vue';
import UiTextarea from '~/components/ui/FormControls/UiTextarea/UiTextarea.vue';
import UiDatePicker from '~/components/ui/FormControls/UiDatePicker/UiDatePicker.vue';

const sectionOptions = [
  { label: 'Пользователи', value: 'users' },
  { label: 'Организации', value: 'organizations' },
  { label: 'Роли', value: 'roles' },
];

const meta = {
  title: 'Admin/Screens/AdminFormShowcase',
  tags: ['autodocs'],
  parameters: {
    layout: 'padded',
  },
} satisfies Meta;

export default meta;
type Story = StoryObj<typeof meta>;

export const ContentEditorForm: Story = {
  render: () => ({
    components: { UiInput, UiSelect, UiSwitch, UiTextarea, UiDatePicker },
    setup() {
      const name = ref('Landing Hero');
      const section = ref('users');
      const published = ref(true);
      const description = ref('Короткое описание для карточки в списке.');
      const publishDate = ref('2026-02-21');
      return { name, section, published, description, publishDate, sectionOptions };
    },
    template: `
      <div style="max-width: 760px; display: grid; gap: 1rem;">
        <UiInput v-model="name" label="Название" hint="Видно в админ-списке" required />
        <UiSelect v-model="section" :options="sectionOptions" label="Раздел" />
        <UiDatePicker v-model="publishDate" label="Дата публикации" />
        <UiSwitch v-model="published" label="Опубликовано" description="Сразу отображать на витрине" />
        <UiTextarea v-model="description" label="Описание" :rows="4" />
      </div>
    `,
  }),
};
