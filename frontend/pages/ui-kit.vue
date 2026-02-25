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

          <UiCheckbox
            v-model="form.agreement"
            label="Согласие на обработку данных"
            description="Показывает UiCheckbox в базовом состоянии"
          />

          <UiInput v-model="form.title" label="Название" placeholder="Введите название" />

          <UiTextarea
            v-model="form.description"
            label="Описание"
            placeholder="Опишите товар"
            hint="Кратко и по делу"
          />

          <UiCodeEditor
            v-model="form.payload"
            label="JSON payload"
            language="json"
            hint="Поддерживает подсветку синтаксиса и tab-вставку"
            min-height="10rem"
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

          <UiDatePicker
            v-model="form.publishedAt"
            label="Дата публикации"
            hint="Одиночный выбор даты"
          />

          <UiDatePicker
            v-model="form.saleRange"
            mode="range"
            label="Период акции"
            hint="Диапазон дат"
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

          <div :class="styles.previewGrid">
            <UiImagePreview
              :src="images[0]?.src || null"
              alt="Preview table"
              variant="table"
              preview-title="Preview table variant"
            />
            <UiImagePreview
              :src="images[1]?.src || null"
              alt="Preview card"
              variant="card"
              preview-title="Preview card variant"
            />
            <UiImagePreview :src="null" fallback-text="Нет картинки" variant="table" />
          </div>
        </div>
      </article>

      <article :class="styles.card">
        <h2 :class="styles.cardTitle">Modal</h2>

        <div :class="styles.stack">
          <button type="button" :class="styles.demoButton" @click="isModalOpen = true">
            Открыть обычную модалку
          </button>
          <button type="button" :class="styles.demoButton" @click="isConfirmModalOpen = true">
            Открыть confirm-модалку
          </button>
          <span :class="styles.modalStatus">{{ modalStatus }}</span>
        </div>
      </article>
    </section>

    <UiModal v-model="isModalOpen" title="Пример UiModal">
      <p>Это базовый режим UiModal с произвольным контентом.</p>
    </UiModal>

    <UiModal
      v-model="isConfirmModalOpen"
      mode="confirm"
      title="Удалить запись?"
      message="Действие нельзя отменить."
      confirm-label="Удалить"
      cancel-label="Отмена"
      :destructive="true"
      @confirm="onConfirmModal"
      @cancel="modalStatus = 'Отменено'"
      @close="onModalClosed"
    />
  </div>
</template>

<script setup lang="ts">
import PageHero from '~/components/ui/PageHero/PageHero.vue';
import UiCheckbox from '~/components/ui/FormControls/UiCheckbox/UiCheckbox.vue';
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiTextarea from '~/components/ui/FormControls/UiTextarea/UiTextarea.vue';
import UiCodeEditor from '~/components/ui/FormControls/UiCodeEditor/UiCodeEditor.vue';
import UiDatePicker from '~/components/ui/FormControls/UiDatePicker/UiDatePicker.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import UiDropdown from '~/components/ui/FormControls/UiDropdown/UiDropdown.vue';
import UiSwitch from '~/components/ui/FormControls/UiSwitch/UiSwitch.vue';
import UiImageBlock from '~/components/ui/ImageBlock/UiImageBlock/UiImageBlock.vue';
import UiImageDropzone from '~/components/ui/ImageBlock/UiImageDropzone/UiImageDropzone.vue';
import UiImagePreview from '~/components/ui/ImagePreview/UiImagePreview.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import styles from './ui-kit.module.scss';

interface SelectOption {
  label: string;
  value: string;
}

const form = reactive({
  agreement: false,
  title: '',
  description: '',
  payload: '{\n  "id": 1,\n  "name": "Sample"\n}',
  category: '',
  tags: [] as string[],
  status: '',
  publishedAt: null as string | null,
  saleRange: [null, null] as [string | null, string | null],
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
const isModalOpen = ref(false);
const isConfirmModalOpen = ref(false);
const modalStatus = ref('Ожидает действия');

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

const onConfirmModal = () => {
  modalStatus.value = 'Подтверждено';
  isConfirmModalOpen.value = false;
};

const onModalClosed = () => {
  if (modalStatus.value !== 'Подтверждено') {
    modalStatus.value = 'Закрыто';
  }
};
</script>
