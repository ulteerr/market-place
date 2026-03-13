<template>
  <div data-error-reporter-ignore="true">
    <div
      v-if="highlightRect"
      :class="styles.highlighter"
      :style="highlightStyle"
      aria-hidden="true"
    />

    <button
      v-if="!isPanelOpen"
      type="button"
      :class="[styles.button, styles.buttonPrimary, styles.launcher]"
      data-test="error-reporter-launcher"
      @click="onOpenPanel"
    >
      {{ t('app.debug.reporter.start') }}
    </button>

    <aside v-if="isPanelOpen" :class="styles.panel" data-test="error-reporter-panel">
      <div :class="styles.panelHeader">
        <strong :class="styles.panelTitle">{{ t('app.debug.reporter.start') }}</strong>
        <button
          type="button"
          :class="styles.iconButton"
          :aria-label="t('app.debug.reporter.close')"
          data-test="error-reporter-close"
          @click="onClosePanel"
        >
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div v-if="isSelectionMode" :class="styles.selectionBadge">
        <span :class="styles.selectionBadgeDot" />
        {{ t('app.debug.reporter.selectionMode') }}
      </div>

      <div :class="styles.actions">
        <button
          v-if="!isSelectionMode"
          type="button"
          :class="[styles.button, styles.buttonPrimary]"
          data-test="error-reporter-start"
          @click="onStartSelection"
        >
          {{ t('app.debug.reporter.start') }}
        </button>

        <button
          v-if="isSelectionMode"
          type="button"
          :class="styles.button"
          data-test="error-reporter-cancel"
          @click="onCancelSelection"
        >
          {{ t('app.debug.reporter.cancel') }}
        </button>

        <button
          v-if="selectedBlock"
          type="button"
          :class="styles.button"
          data-test="error-reporter-reset"
          @click="onResetSelection"
        >
          {{ t('app.debug.reporter.reset') }}
        </button>
      </div>

      <p v-if="isSelectionMode" :class="styles.hint">
        {{ t('app.debug.reporter.selectionHint') }}
      </p>
      <p v-else :class="styles.hint">
        {{ t('app.debug.reporter.idleHint') }}
      </p>

      <div v-if="selectedBlock" :class="styles.selected" data-test="error-reporter-selected">
        <div>
          {{ t('app.debug.reporter.selectedBlock') }}: <strong>{{ selectedBlock.blockId }}</strong>
        </div>
        <div>{{ t('app.debug.reporter.source') }}: {{ selectedBlock.strategy }}</div>
        <div :class="styles.selectedCode">{{ selectedBlock.queryPath }}</div>
      </div>

      <form :class="styles.form" @submit.prevent="onBuildReport">
        <p
          :class="[styles.stateBadge, styles[`state-${reportState}`]]"
          data-test="error-reporter-state"
        >
          {{ t('app.debug.reporter.state') }}: {{ reportState }}
        </p>

        <label :class="styles.label" for="error-reporter-description">{{
          t('app.debug.reporter.descriptionLabel')
        }}</label>
        <textarea
          id="error-reporter-description"
          v-model="description"
          :class="styles.textarea"
          data-test="error-reporter-description"
          :placeholder="t('app.debug.reporter.descriptionPlaceholder')"
          rows="3"
        />
        <p v-if="formError" :class="styles.error">{{ formError }}</p>

        <label :class="styles.label" for="error-reporter-attachments">
          {{
            t('app.debug.reporter.attachmentsLabel', {
              count: ERROR_REPORT_MAX_ATTACHMENTS,
              size: maxFileSizeMb,
            })
          }}
        </label>
        <input
          id="error-reporter-attachments"
          type="file"
          multiple
          :accept="attachmentAccept"
          :class="styles.fileInput"
          data-test="error-reporter-attachments"
          @change="onAttachmentsChange"
        />
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

        <button
          type="submit"
          :class="[styles.button, styles.buttonPrimary]"
          data-test="error-reporter-build"
        >
          {{ t('app.debug.reporter.submitBuild') }}
        </button>
      </form>

      <div v-if="reportPayload" :class="styles.preview" data-test="error-reporter-preview">
        <div>
          <strong>{{ t('app.debug.reporter.url') }}:</strong> {{ reportPayload.page.url }}
        </div>
        <div>
          <strong>{{ t('app.debug.reporter.route') }}:</strong>
          {{ reportPayload.page.routeName || reportPayload.page.path }}
        </div>
        <div>
          <strong>{{ t('app.debug.reporter.blockId') }}:</strong> {{ reportPayload.block.id }}
        </div>
        <div>
          <strong>{{ t('app.debug.reporter.theme') }}:</strong> {{ reportPayload.context.theme }}
        </div>
        <div>
          <strong>{{ t('app.debug.reporter.locale') }}:</strong> {{ reportPayload.context.locale }}
        </div>
        <div>
          <strong>{{ t('app.debug.reporter.viewport') }}:</strong>
          {{ reportPayload.context.viewport.width }}x{{ reportPayload.context.viewport.height }}
        </div>
        <div>
          <strong>{{ t('app.debug.reporter.attachments') }}:</strong>
          {{ reportPayload.attachments.length }}
        </div>
        <div>
          <strong>{{ t('app.debug.reporter.timestamp') }}:</strong>
          {{ reportPayload.context.timestamp }}
        </div>

        <div :class="styles.actions">
          <button
            type="button"
            :class="[styles.button, styles.buttonPrimary]"
            data-test="error-reporter-send"
            :disabled="reportState === 'sending'"
            @click="onSendReport"
          >
            {{
              reportState === 'sending'
                ? t('app.debug.reporter.sendPending')
                : t('app.debug.reporter.send')
            }}
          </button>
          <button
            v-if="reportState === 'error'"
            type="button"
            :class="styles.button"
            data-test="error-reporter-retry"
            @click="onSendReport"
          >
            {{ t('app.debug.reporter.retry') }}
          </button>
        </div>

        <p v-if="sendResult" :class="styles.success" data-test="error-reporter-send-result">
          {{
            t('app.debug.reporter.sendResult', {
              reportId: sendResult.reportId,
              status: sendResult.status,
            })
          }}
        </p>
      </div>
    </aside>
  </div>
</template>

<script setup lang="ts">
import type { CSSProperties } from 'vue';
import {
  ERROR_REPORT_ALLOWED_MIME_TYPES,
  ERROR_REPORT_MAX_ATTACHMENTS,
  ERROR_REPORT_MAX_FILE_SIZE_BYTES,
  type ReportAttachmentMeta,
  validateReportAttachments,
} from '~/composables/error-reporting/attachments';
import { resolveReportBlock } from '~/composables/error-reporting/block-resolver';
import {
  buildUiErrorReportPayload,
  type UiErrorReportPayload,
} from '~/composables/error-reporting/report-payload';
import styles from './GlobalErrorReporter.module.scss';

type Rect = {
  left: number;
  top: number;
  width: number;
  height: number;
};

const {
  isSelectionMode,
  selectedBlock,
  startSelection,
  stopSelection,
  clearSelectedBlock,
  setSelectedBlock,
} = useUiErrorReporter();
const route = useRoute();
const { locale, t } = useI18n();
const api = useApi();

const hoveredElement = ref<HTMLElement | null>(null);
const selectedElement = ref<HTMLElement | null>(null);
const hoveredRect = ref<Rect | null>(null);
const selectedRect = ref<Rect | null>(null);
const description = ref('');
const formError = ref('');
const attachmentError = ref('');
const attachments = ref<ReportAttachmentMeta[]>([]);
const reportPayload = ref<UiErrorReportPayload | null>(null);
const sendResult = ref<{ reportId: string; status: string } | null>(null);
const reportState = ref<'draft' | 'sending' | 'sent' | 'error'>('draft');
const isPanelOpen = ref(false);

const attachmentAccept = ERROR_REPORT_ALLOWED_MIME_TYPES.join(',');
const maxFileSizeMb = Math.round(ERROR_REPORT_MAX_FILE_SIZE_BYTES / (1024 * 1024));

const highlightRect = computed(() => hoveredRect.value ?? selectedRect.value);
const highlightStyle = computed<CSSProperties>(() => {
  const rect = highlightRect.value;
  if (!rect) {
    return {};
  }

  return {
    left: `${Math.max(0, rect.left)}px`,
    top: `${Math.max(0, rect.top)}px`,
    width: `${Math.max(0, rect.width)}px`,
    height: `${Math.max(0, rect.height)}px`,
  };
});

const toRect = (element: HTMLElement): Rect => {
  const box = element.getBoundingClientRect();
  return {
    left: box.left,
    top: box.top,
    width: box.width,
    height: box.height,
  };
};

const shouldIgnoreTarget = (target: EventTarget | null): target is HTMLElement => {
  return (
    target instanceof HTMLElement && target.closest('[data-error-reporter-ignore="true"]') !== null
  );
};

const updateSelectedRect = () => {
  if (!selectedElement.value) {
    selectedRect.value = null;
    return;
  }

  selectedRect.value = toRect(selectedElement.value);
};

const onOpenPanel = () => {
  isPanelOpen.value = true;
};

const onClosePanel = () => {
  isPanelOpen.value = false;
  stopSelection();
  hoveredElement.value = null;
  hoveredRect.value = null;
};

const onStartSelection = () => {
  isPanelOpen.value = true;
  startSelection();
  hoveredElement.value = null;
  hoveredRect.value = null;
};

const onCancelSelection = () => {
  stopSelection();
  hoveredElement.value = null;
  hoveredRect.value = null;
};

const onResetSelection = () => {
  clearSelectedBlock();
  selectedElement.value = null;
  selectedRect.value = null;
  reportPayload.value = null;
  attachments.value = [];
  attachmentError.value = '';
  sendResult.value = null;
  reportState.value = 'draft';
  startSelection();
};

const resolveIssueMessage = (reason: string): string => {
  if (reason === 'too-many-files') {
    return t('app.debug.reporter.issues.tooManyFiles', { count: ERROR_REPORT_MAX_ATTACHMENTS });
  }

  if (reason === 'file-too-large') {
    return t('app.debug.reporter.issues.fileTooLarge', { size: maxFileSizeMb });
  }

  if (reason === 'empty-file') {
    return t('app.debug.reporter.issues.emptyFile');
  }

  return t('app.debug.reporter.issues.invalidType');
};

const onAttachmentsChange = (event: Event) => {
  const target = event.target as HTMLInputElement | null;
  const files = target?.files;
  if (!files) {
    attachments.value = [];
    attachmentError.value = '';
    return;
  }

  const { accepted, issues } = validateReportAttachments(files);
  attachments.value = accepted;

  if (issues.length > 0) {
    const issue = issues[0];
    attachmentError.value = `${issue.fileName}: ${resolveIssueMessage(issue.reason)}`;
    return;
  }

  attachmentError.value = '';
};

const onBuildReport = () => {
  if (!selectedBlock.value) {
    formError.value = t('app.debug.reporter.issues.selectBlock');
    reportPayload.value = null;
    return;
  }

  if (!description.value.trim()) {
    formError.value = t('app.debug.reporter.issues.describeProblem');
    reportPayload.value = null;
    return;
  }

  if (attachmentError.value) {
    formError.value = t('app.debug.reporter.issues.attachmentsInvalid');
    reportPayload.value = null;
    return;
  }

  formError.value = '';
  reportPayload.value = buildUiErrorReportPayload({
    selectedBlock: selectedBlock.value,
    description: description.value,
    attachments: attachments.value,
    route: {
      fullPath: route.fullPath,
      path: route.path,
      name: typeof route.name === 'string' ? route.name : null,
    },
    locale: locale.value,
  });
  sendResult.value = null;
  reportState.value = 'draft';
};

const onSendReport = async () => {
  if (!reportPayload.value) {
    return;
  }

  reportState.value = 'sending';
  formError.value = '';

  try {
    const response = await api<{
      status: string;
      data?: { reportId?: string; status?: string };
      message?: string;
    }>('/api/reports/ui-errors', {
      method: 'POST',
      body: reportPayload.value,
    });

    const reportId = String(response?.data?.reportId ?? '');
    const status = String(response?.data?.status ?? 'received');
    if (!reportId) {
      throw new Error('missing report id');
    }

    sendResult.value = {
      reportId,
      status,
    };
    reportState.value = 'sent';
  } catch {
    formError.value = t('app.debug.reporter.issues.sendFailed');
    sendResult.value = null;
    reportState.value = 'error';
  }
};

const onDocumentMouseMove = (event: MouseEvent) => {
  if (
    !isSelectionMode.value ||
    shouldIgnoreTarget(event.target) ||
    !(event.target instanceof HTMLElement)
  ) {
    return;
  }

  hoveredElement.value = event.target;
  hoveredRect.value = toRect(event.target);
};

const onDocumentClick = (event: MouseEvent) => {
  if (!isSelectionMode.value) {
    return;
  }

  if (shouldIgnoreTarget(event.target) || !(event.target instanceof HTMLElement)) {
    return;
  }

  event.preventDefault();
  event.stopPropagation();
  event.stopImmediatePropagation();

  const block = resolveReportBlock(event.target);
  setSelectedBlock(block);
  selectedElement.value = event.target;
  selectedRect.value = toRect(event.target);
  stopSelection();
  hoveredElement.value = null;
  hoveredRect.value = null;
};

const onDocumentKeyDown = (event: KeyboardEvent) => {
  if (!isSelectionMode.value) {
    return;
  }

  if (event.key === 'Escape') {
    event.preventDefault();
    onCancelSelection();
  }
};

onMounted(() => {
  document.addEventListener('mousemove', onDocumentMouseMove, true);
  document.addEventListener('click', onDocumentClick, true);
  document.addEventListener('keydown', onDocumentKeyDown, true);
  window.addEventListener('resize', updateSelectedRect);
  window.addEventListener('scroll', updateSelectedRect, true);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousemove', onDocumentMouseMove, true);
  document.removeEventListener('click', onDocumentClick, true);
  document.removeEventListener('keydown', onDocumentKeyDown, true);
  window.removeEventListener('resize', updateSelectedRect);
  window.removeEventListener('scroll', updateSelectedRect, true);
});

watch(selectedBlock, (value) => {
  if (!value) {
    reportPayload.value = null;
    sendResult.value = null;
    reportState.value = 'draft';
  }
});
</script>
