<template>
  <div data-error-reporter-ignore="true">
    <aside :class="styles.panel" data-test="error-reporter-panel-preview">
      <div v-if="isSelectionMode" :class="styles.selectionBadge">
        <span :class="styles.selectionBadgeDot" />
        Режим выбора блока
      </div>

      <div :class="styles.actions">
        <button
          v-if="!isSelectionMode"
          type="button"
          :class="[styles.button, styles.buttonPrimary]"
        >
          Сообщить об ошибке
        </button>

        <button v-if="isSelectionMode" type="button" :class="styles.button">Отменить</button>

        <button v-if="selectedBlock" type="button" :class="styles.button">Выбрать заново</button>
      </div>

      <p v-if="isSelectionMode" :class="styles.hint">
        Кликните по проблемному блоку. `Esc` отменяет режим выбора.
      </p>
      <p v-else :class="styles.hint">
        Включите режим и выберите блок, чтобы зафиксировать его идентификатор.
      </p>

      <div v-if="selectedBlock" :class="styles.selected">
        <div>
          Выбранный block id: <strong>{{ selectedBlock.blockId }}</strong>
        </div>
        <div>Источник: {{ selectedBlock.strategy }}</div>
        <div :class="styles.selectedCode">{{ selectedBlock.queryPath }}</div>
      </div>

      <div :class="styles.form">
        <p :class="[styles.stateBadge, styles[`state-${reportState}`]]">
          Состояние: {{ reportState }}
        </p>

        <label :class="styles.label">Описание ошибки</label>
        <textarea
          :class="styles.textarea"
          placeholder="Что пошло не так и какие шаги привели к ошибке"
          rows="3"
          :value="description"
          readonly
        />
        <p v-if="formError" :class="styles.error">{{ formError }}</p>

        <label :class="styles.label">Вложения</label>
        <input type="file" multiple :class="styles.fileInput" disabled />
        <p v-if="attachmentError" :class="styles.error">{{ attachmentError }}</p>

        <ul v-if="attachments.length > 0" :class="styles.attachmentList">
          <li
            v-for="attachment in attachments"
            :key="`${attachment.safeName}-${attachment.size}`"
            :class="styles.attachmentItem"
          >
            {{ attachment.safeName }} ({{ attachment.type }}, {{ attachment.size }}b)
          </li>
        </ul>

        <button type="button" :class="[styles.button, styles.buttonPrimary]">
          Сформировать отчет
        </button>
      </div>

      <div v-if="showPreview" :class="styles.preview">
        <div><strong>URL:</strong> {{ pageUrl }}</div>
        <div><strong>Route:</strong> {{ routeName }}</div>
        <div><strong>Block ID:</strong> {{ selectedBlock?.blockId ?? 'public-header' }}</div>
        <div><strong>Theme:</strong> {{ theme }}</div>
        <div><strong>Locale:</strong> {{ locale }}</div>
        <div><strong>Viewport:</strong> {{ viewport }}</div>
        <div><strong>Attachments:</strong> {{ attachments.length }}</div>

        <div :class="styles.actions">
          <button
            type="button"
            :class="[styles.button, styles.buttonPrimary]"
            :disabled="reportState === 'sending'"
          >
            {{ reportState === 'sending' ? 'Отправка...' : 'Отправить администратору' }}
          </button>
          <button v-if="reportState === 'error'" type="button" :class="styles.button">
            Повторить отправку
          </button>
        </div>

        <p v-if="sendResult" :class="styles.success">
          Отчет отправлен: {{ sendResult.reportId }} (status: {{ sendResult.status }})
        </p>
      </div>
    </aside>
  </div>
</template>

<script setup lang="ts">
import styles from './GlobalErrorReporter.module.scss';

type PreviewState = 'draft' | 'sending' | 'sent' | 'error';

type PreviewBlock = {
  blockId: string;
  strategy: string;
  queryPath: string;
};

type PreviewAttachment = {
  safeName: string;
  type: string;
  size: number;
};

withDefaults(
  defineProps<{
    reportState?: PreviewState;
    isSelectionMode?: boolean;
    showPreview?: boolean;
    description?: string;
    formError?: string;
    attachmentError?: string;
    selectedBlock?: PreviewBlock | null;
    attachments?: PreviewAttachment[];
    sendResult?: { reportId: string; status: string } | null;
    pageUrl?: string;
    routeName?: string;
    theme?: string;
    locale?: string;
    viewport?: string;
  }>(),
  {
    reportState: 'draft',
    isSelectionMode: false,
    showPreview: false,
    description: 'Клик по каталогу не открывает меню.',
    formError: '',
    attachmentError: '',
    selectedBlock: () => ({
      blockId: 'public-header',
      strategy: 'data-test',
      queryPath: 'header:nth-of-type(1)',
    }),
    attachments: () => [
      {
        safeName: 'report.txt',
        type: 'text/plain',
        size: 128,
      },
    ],
    sendResult: null,
    pageUrl: 'https://example.test/',
    routeName: 'index',
    theme: 'light',
    locale: 'ru',
    viewport: '1366x900',
  }
);
</script>
