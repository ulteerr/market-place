<template>
  <div>
    <PageHero
      eyebrow="UI"
      title="Формы и изображения"
      description="Базовые шаблоны: input, textarea, select, dropdown, теги и drag-and-drop."
    />

    <section :class="styles.layout">
      <article :class="styles.card">
        <h2 :class="styles.cardTitle">Поля формы</h2>

        <div :class="styles.stack">
          <UiSwitch
            :model-value="isDark"
            label="Тёмная тема"
            description="Демонстрация нового switch-контрола"
            @update:model-value="onThemeToggle"
          />

          <UiInput v-model="form.title" label="Название" placeholder="Введите название" />

          <UiTextarea
            v-model="form.description"
            label="Описание"
            placeholder="Опишите товар"
            hint="Кратко и по делу"
          />

          <UiSelect
            v-model="form.category"
            label="Категория (поиск)"
            :options="categoryOptions"
            placeholder="Начните вводить..."
            searchable
          />

          <UiSelect
            v-model="form.tags"
            label="Теги (поиск + добавление)"
            :options="tagOptions"
            placeholder="Например: зима"
            multiple
            allow-create
            searchable
            hint="Нажмите Enter, чтобы выбрать или создать тег"
          />

          <UiDropdown
            v-model="form.status"
            label="Статус"
            :options="statusOptions"
            placeholder="Выберите статус"
          />
        </div>
      </article>

      <article :class="styles.card">
        <h2 :class="styles.cardTitle">Изображения</h2>

        <div :class="styles.stack">
          <UiImageDropzone v-model="uploadedFiles" @files-added="onFilesAdded" />

          <UiImageBlock
            title="Галерея товара"
            description="Основное и дополнительные фото"
            :images="images"
            @add="onAddImage"
            @remove="onRemoveImage"
          />
        </div>
      </article>
    </section>
  </div>
</template>

<script setup lang="ts">
import PageHero from '~/components/ui/PageHero/PageHero.vue';
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiTextarea from '~/components/ui/FormControls/UiTextarea/UiTextarea.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import UiDropdown from '~/components/ui/FormControls/UiDropdown/UiDropdown.vue';
import UiSwitch from '~/components/ui/FormControls/UiSwitch/UiSwitch.vue';
import UiImageBlock from '~/components/ui/ImageBlock/UiImageBlock/UiImageBlock.vue';
import UiImageDropzone from '~/components/ui/ImageBlock/UiImageDropzone/UiImageDropzone.vue';
import styles from './ui-kit.module.scss';

interface SelectOption {
  label: string;
  value: string;
}

const form = reactive({
  title: '',
  description: '',
  category: '',
  tags: [] as string[],
  status: '',
});

const { isDark, setTheme } = useUserSettings();

const categoryOptions = [
  { label: 'Одежда', value: 'fashion' },
  { label: 'Электроника', value: 'electronics' },
  { label: 'Дом и интерьер', value: 'home' },
];

const tagOptions = ref<SelectOption[]>([
  { label: 'Новинка', value: 'new' },
  { label: 'Хит', value: 'hit' },
  { label: 'Скидка', value: 'sale' },
]);

const statusOptions = [
  { label: 'Черновик', value: 'draft' },
  { label: 'На проверке', value: 'review' },
  { label: 'Опубликовано', value: 'published' },
];

const images = ref([
  {
    id: '1',
    src: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=800&q=80',
    caption: 'Обложка',
  },
  {
    id: '2',
    src: 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=800&q=80',
    caption: 'Деталь',
  },
]);

const uploadedFiles = ref<File[]>([]);

const onFilesAdded = (files: File[]) => {
  files.forEach((file) => {
    images.value.push({
      id: `${file.name}-${file.size}-${Date.now()}`,
      src: URL.createObjectURL(file),
      caption: file.name,
    });
  });
};

const onRemoveImage = (index: number) => {
  const image = images.value[index];
  if (typeof image?.src === 'string' && image.src.startsWith('blob:')) {
    URL.revokeObjectURL(image.src);
  }

  images.value.splice(index, 1);
};

const onAddImage = () => {
  images.value.push({
    id: String(Date.now()),
    src: 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=800&q=80',
    caption: 'Новое фото',
  });
};

const onThemeToggle = (value: boolean) => {
  setTheme(value ? 'dark' : 'light');
};
</script>
