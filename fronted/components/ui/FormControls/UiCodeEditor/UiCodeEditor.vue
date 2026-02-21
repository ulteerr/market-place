<template>
  <label :class="styles.field" :for="resolvedId">
    <span v-if="label" :class="styles.label">
      {{ label }}
      <span v-if="required" :class="styles.required">*</span>
    </span>

    <div
      :class="[
        styles.control,
        error ? styles.controlError : '',
        disabled ? styles.controlDisabled : '',
      ]"
      :style="controlStyle"
      @click="focusEditor"
    >
      <div v-if="showToolbar" :class="styles.toolbar" @click.stop>
        <template v-if="isCodeMode">
          <div :class="styles.toolbarGroup">
            <button
              type="button"
              :class="styles.toolbarButton"
              :disabled="disabled || readonly"
              title="Duplicate line (Ctrl/Cmd + D)"
              @click="duplicateCurrentLine"
            >
              Duplicate line
            </button>
            <button
              type="button"
              :class="styles.toolbarButton"
              :disabled="disabled || readonly"
              title="Format code (Ctrl/Cmd + I)"
              @click="onFormatClick"
            >
              Format
            </button>
          </div>
        </template>

        <template v-else-if="isWysiwygMode">
          <div :class="styles.toolbarGroup">
            <button
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Bold"
              @click="applyRichCommand('bold')"
            >
              <strong>B</strong>
            </button>
            <button
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Italic"
              @click="applyRichCommand('italic')"
            >
              <em>I</em>
            </button>
            <button
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Underline"
              @click="applyRichCommand('underline')"
            >
              <u>U</u>
            </button>
            <button
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Strike"
              @click="applyRichCommand('strikeThrough')"
            >
              <s>S</s>
            </button>
          </div>

          <div :class="styles.toolbarGroup">
            <label :class="styles.colorLabel" title="Text color">
              A
              <input
                type="color"
                :class="styles.colorPicker"
                :value="textColor"
                :disabled="disabled || readonly"
                @input="onTextColorInput"
              />
            </label>
            <label :class="styles.colorLabel" title="Background color">
              Bg
              <input
                type="color"
                :class="styles.colorPicker"
                :value="backgroundColor"
                :disabled="disabled || readonly"
                @input="onBackgroundColorInput"
              />
            </label>
          </div>

          <div :class="styles.toolbarGroup">
            <button
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Unordered list"
              @click="applyRichCommand('insertUnorderedList')"
            >
              • List
            </button>
            <button
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Ordered list"
              @click="applyRichCommand('insertOrderedList')"
            >
              1. List
            </button>
            <button
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Quote"
              @click="applyRichCommand('formatBlock', 'blockquote')"
            >
              Quote
            </button>
          </div>

          <div :class="styles.toolbarGroup">
            <button
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Align left"
              @click="applyRichCommand('justifyLeft')"
            >
              Left
            </button>
            <button
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Align center"
              @click="applyRichCommand('justifyCenter')"
            >
              Center
            </button>
            <button
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Align right"
              @click="applyRichCommand('justifyRight')"
            >
              Right
            </button>
          </div>

          <div :class="styles.toolbarGroup">
            <button
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Insert link"
              @click="insertLink"
            >
              Link
            </button>
            <button
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Remove link"
              @click="applyRichCommand('unlink')"
            >
              Unlink
            </button>
            <button
              v-if="allowImageUpload"
              type="button"
              :class="styles.toolbarIconButton"
              :disabled="disabled || readonly"
              title="Insert image"
              @click="triggerImagePicker"
            >
              Image
            </button>
          </div>
        </template>

        <div v-if="showModeSwitcher" :class="styles.toolbarGroup">
          <span :class="styles.modeLabel">Mode</span>
          <select
            :class="styles.modeSelect"
            :value="currentMode"
            :disabled="disabled"
            @change="onModeSelect"
          >
            <option v-for="mode in resolvedModeOptions" :key="mode" :value="mode">
              {{ getModeLabel(mode) }}
            </option>
          </select>
        </div>
      </div>

      <div
        v-if="isCodeMode && !editorInitFailed"
        ref="editorHostRef"
        :id="resolvedId"
        :class="styles.editor"
        :aria-label="label || name || 'Code editor'"
      />

      <div
        v-else-if="isWysiwygMode"
        ref="richEditorRef"
        :id="resolvedId"
        :class="styles.richEditor"
        :contenteditable="!disabled && !readonly"
        :data-placeholder="placeholder"
        :aria-label="label || name || 'Rich text editor'"
        @input="onRichInput"
        @focus="emit('focus')"
        @blur="emit('blur')"
      />

      <textarea
        v-else
        ref="fallbackRef"
        :id="resolvedId"
        :class="styles.fallback"
        :value="normalizedValue"
        :name="name"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :readonly="readonly"
        @input="onFallbackInput"
      />

      <input
        v-if="allowImageUpload"
        ref="imageInputRef"
        type="file"
        accept="image/*"
        :class="styles.hiddenFileInput"
        :disabled="disabled || readonly"
        @change="onImageFilesSelected"
      />

      <textarea
        v-if="name && (isCodeMode || isWysiwygMode)"
        :name="name"
        :value="normalizedValue"
        :disabled="disabled"
        :class="styles.hiddenInput"
      />
    </div>

    <span v-if="error" :class="styles.error">{{ error }}</span>
    <span v-else-if="hint" :class="styles.hint">{{ hint }}</span>
  </label>
</template>

<script setup lang="ts">
import styles from './UiCodeEditor.module.scss';

type UiCodeLanguage = 'plaintext' | 'json' | 'javascript' | 'typescript' | 'html' | 'css' | 'sql';
type UiCodeMode = 'plain' | 'wysiwyg' | UiCodeLanguage;

const CODE_MODES: UiCodeMode[] = [
  'plaintext',
  'json',
  'javascript',
  'typescript',
  'html',
  'css',
  'sql',
];

const props = withDefaults(
  defineProps<{
    modelValue?: string | null;
    id?: string;
    name?: string;
    label?: string;
    placeholder?: string;
    hint?: string;
    error?: string;
    language?: UiCodeLanguage;
    mode?: UiCodeMode;
    modeOptions?: UiCodeMode[];
    showToolbar?: boolean;
    showModeSwitcher?: boolean;
    formatter?: ((value: string, mode: UiCodeMode) => string | Promise<string>) | null;
    imageUploader?: ((file: File) => string | Promise<string>) | null;
    allowImageUpload?: boolean;
    required?: boolean;
    disabled?: boolean;
    readonly?: boolean;
    lineNumbers?: boolean;
    tabSize?: number;
    minHeight?: string;
    maxHeight?: string;
  }>(),
  {
    modelValue: '',
    id: '',
    name: '',
    label: '',
    placeholder: '',
    hint: '',
    error: '',
    language: 'plaintext',
    mode: undefined,
    modeOptions: () => ['wysiwyg', 'html', 'plain', 'json', 'javascript', 'typescript', 'css'],
    showToolbar: true,
    showModeSwitcher: true,
    formatter: null,
    imageUploader: null,
    allowImageUpload: true,
    required: false,
    disabled: false,
    readonly: false,
    lineNumbers: true,
    tabSize: 2,
    minHeight: '12rem',
    maxHeight: '',
  }
);

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void;
  (event: 'update:mode', value: UiCodeMode): void;
  (event: 'focus'): void;
  (event: 'blur'): void;
  (event: 'ready'): void;
  (event: 'formatted', value: string): void;
  (event: 'format-error', error: unknown): void;
  (event: 'image-upload-error', error: unknown): void;
}>();

const uid = useId();
const resolvedId = computed(() => props.id || `ui-code-editor-${uid}`);
const normalizedValue = computed(() => props.modelValue ?? '');
const editorHostRef = ref<HTMLElement | null>(null);
const richEditorRef = ref<HTMLDivElement | null>(null);
const fallbackRef = ref<HTMLTextAreaElement | null>(null);
const imageInputRef = ref<HTMLInputElement | null>(null);
const editorInitFailed = ref(false);
const isSyncingFromEditor = ref(false);
const internalMode = ref<UiCodeMode>(props.mode || props.language || 'plaintext');
const textColor = ref('#111111');
const backgroundColor = ref('#fff59d');

const currentMode = computed<UiCodeMode>(() => props.mode || internalMode.value);
const isCodeMode = computed(() => CODE_MODES.includes(currentMode.value));
const isWysiwygMode = computed(() => currentMode.value === 'wysiwyg');
const activeLanguage = computed<UiCodeLanguage>(() => {
  if (isCodeMode.value) {
    return currentMode.value as UiCodeLanguage;
  }

  return props.language;
});
const resolvedModeOptions = computed<UiCodeMode[]>(() => {
  const modes = [...props.modeOptions];
  if (!modes.includes(currentMode.value)) {
    modes.unshift(currentMode.value);
  }
  return Array.from(new Set(modes));
});

const controlStyle = computed(() => ({
  '--ui-code-min-height': props.minHeight,
  '--ui-code-max-height': props.maxHeight || 'none',
}));

let editorView: any = null;
let languageCompartment: any = null;
let editableCompartment: any = null;
let createEditableExtension: ((value: boolean) => any) | null = null;

const getModeLabel = (mode: UiCodeMode) => {
  if (mode === 'plain') {
    return 'Text';
  }

  if (mode === 'wysiwyg') {
    return 'Visual';
  }

  if (mode === 'plaintext') {
    return 'Plain code';
  }

  return mode.toUpperCase();
};

const getLanguageExtension = (
  language: UiCodeLanguage,
  langModules: {
    json: () => any;
    javascript: (options?: { typescript?: boolean }) => any;
    html: () => any;
    css: () => any;
    sql: () => any;
  }
) => {
  if (language === 'json') {
    return langModules.json();
  }

  if (language === 'javascript') {
    return langModules.javascript();
  }

  if (language === 'typescript') {
    return langModules.javascript({ typescript: true });
  }

  if (language === 'html') {
    return langModules.html();
  }

  if (language === 'css') {
    return langModules.css();
  }

  if (language === 'sql') {
    return langModules.sql();
  }

  return [];
};

const syncRichEditorHtml = (value: string) => {
  if (!richEditorRef.value || richEditorRef.value.innerHTML === value) {
    return;
  }

  richEditorRef.value.innerHTML = value;
};

const updateMode = (mode: UiCodeMode) => {
  if (!props.mode) {
    internalMode.value = mode;
  }

  emit('update:mode', mode);
};

const onModeSelect = (event: Event) => {
  const target = event.target as HTMLSelectElement | null;
  const nextMode = (target?.value as UiCodeMode | undefined) || currentMode.value;
  updateMode(nextMode);
};

const destroyEditor = () => {
  if (editorView) {
    editorView.destroy();
    editorView = null;
  }
};

const setEditorValue = (nextValue: string) => {
  if (!editorView) {
    emit('update:modelValue', nextValue);
    return;
  }

  const currentValue = editorView.state.doc.toString();
  if (currentValue === nextValue) {
    return;
  }

  isSyncingFromEditor.value = true;
  editorView.dispatch({
    changes: { from: 0, to: currentValue.length, insert: nextValue },
  });
  emit('update:modelValue', nextValue);
  queueMicrotask(() => {
    isSyncingFromEditor.value = false;
  });
};

const duplicateSelectionOrLine = (view: any) => {
  const doc = view.state.doc;
  const ranges = view.state.selection.ranges;
  const changes = ranges
    .map((range: any) => {
      const startLine = doc.lineAt(range.from);
      const endLine = doc.lineAt(range.to);
      const lineText = doc.sliceString(startLine.from, endLine.to);
      return { from: endLine.to, insert: `\n${lineText}` };
    })
    .sort((left: { from: number }, right: { from: number }) => right.from - left.from);

  if (!changes.length) {
    return false;
  }

  view.dispatch({ changes });
  return true;
};

const formatWithPrettier = async (value: string, mode: UiCodeMode): Promise<string | null> => {
  const parserByMode: Partial<Record<UiCodeMode, string>> = {
    json: 'json',
    javascript: 'babel',
    typescript: 'typescript',
    html: 'html',
    css: 'css',
  };

  const parser = parserByMode[mode];
  if (!parser) {
    return null;
  }

  const prettier = await import('prettier/standalone');
  const plugins = await Promise.all([
    import('prettier/plugins/babel'),
    import('prettier/plugins/estree'),
    import('prettier/plugins/html'),
    import('prettier/plugins/postcss'),
    import('prettier/plugins/typescript'),
  ]);

  const result = await prettier.format(value, {
    parser,
    plugins,
    tabWidth: Math.max(1, props.tabSize),
  });

  return typeof result === 'string' ? result : null;
};

const formatValue = async (value: string): Promise<string> => {
  if (props.formatter) {
    return await props.formatter(value, currentMode.value);
  }

  if (currentMode.value === 'json') {
    return JSON.stringify(JSON.parse(value), null, Math.max(1, props.tabSize));
  }

  const prettierResult = await formatWithPrettier(value, currentMode.value);
  return prettierResult ?? value;
};

const formatDocument = async () => {
  try {
    const formatted = await formatValue(normalizedValue.value);
    if (formatted === normalizedValue.value) {
      return;
    }

    setEditorValue(formatted);
    emit('formatted', formatted);
  } catch (error) {
    emit('format-error', error);
  }
};

const duplicateCurrentLine = () => {
  if (props.disabled || props.readonly || !editorView) {
    return;
  }

  duplicateSelectionOrLine(editorView);
  editorView.focus();
};

const onFormatClick = () => {
  if (props.disabled || props.readonly || !isCodeMode.value) {
    return;
  }

  void formatDocument();
};

const applyRichCommand = (command: string, value?: string) => {
  if (!process.client || !isWysiwygMode.value || props.disabled || props.readonly) {
    return;
  }

  richEditorRef.value?.focus();
  document.execCommand(command, false, value);
  emit('update:modelValue', richEditorRef.value?.innerHTML ?? '');
};

const onTextColorInput = (event: Event) => {
  const target = event.target as HTMLInputElement | null;
  const next = target?.value || '#111111';
  textColor.value = next;
  applyRichCommand('foreColor', next);
};

const onBackgroundColorInput = (event: Event) => {
  const target = event.target as HTMLInputElement | null;
  const next = target?.value || '#fff59d';
  backgroundColor.value = next;
  applyRichCommand('hiliteColor', next);
};

const insertLink = () => {
  if (!process.client || props.disabled || props.readonly) {
    return;
  }

  const url = window.prompt('Введите URL', 'https://');
  if (!url) {
    return;
  }

  applyRichCommand('createLink', url);
};

const triggerImagePicker = () => {
  if (props.disabled || props.readonly) {
    return;
  }

  imageInputRef.value?.click();
};

const onImageFilesSelected = async (event: Event) => {
  const target = event.target as HTMLInputElement | null;
  const files = Array.from(target?.files ?? []);

  if (!files.length) {
    return;
  }

  for (const file of files) {
    try {
      const uploaded = props.imageUploader
        ? await props.imageUploader(file)
        : URL.createObjectURL(file);
      if (uploaded) {
        applyRichCommand('insertImage', uploaded);
      }
    } catch (error) {
      emit('image-upload-error', error);
    }
  }

  if (target) {
    target.value = '';
  }
};

const initEditor = async () => {
  if (!process.client || !editorHostRef.value || editorView || !isCodeMode.value) {
    return;
  }

  try {
    const [
      stateModule,
      viewModule,
      commandsModule,
      languageModule,
      langJsonModule,
      langJavascriptModule,
      langHtmlModule,
      langCssModule,
      langSqlModule,
    ] = await Promise.all([
      import('@codemirror/state'),
      import('@codemirror/view'),
      import('@codemirror/commands'),
      import('@codemirror/language'),
      import('@codemirror/lang-json'),
      import('@codemirror/lang-javascript'),
      import('@codemirror/lang-html'),
      import('@codemirror/lang-css'),
      import('@codemirror/lang-sql'),
    ]);

    const { EditorState, Compartment } = stateModule;
    const { EditorView, keymap, lineNumbers, drawSelection, highlightActiveLine, placeholder } =
      viewModule;
    const { defaultKeymap, history, historyKeymap, indentWithTab } = commandsModule;
    const { syntaxHighlighting, defaultHighlightStyle } = languageModule;
    const { json } = langJsonModule;
    const { javascript } = langJavascriptModule;
    const { html } = langHtmlModule;
    const { css } = langCssModule;
    const { sql } = langSqlModule;

    languageCompartment = new Compartment();
    editableCompartment = new Compartment();

    createEditableExtension = (value: boolean) => EditorView.editable.of(value);

    const duplicateCommand = {
      key: 'Mod-d',
      run: (view: any) => {
        if (props.disabled || props.readonly) {
          return true;
        }

        return duplicateSelectionOrLine(view);
      },
    };

    const formatCommand = {
      key: 'Mod-i',
      run: () => {
        if (props.disabled || props.readonly) {
          return true;
        }

        void formatDocument();
        return true;
      },
    };

    const extensions = [
      drawSelection(),
      highlightActiveLine(),
      history(),
      syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
      keymap.of([
        duplicateCommand,
        formatCommand,
        ...defaultKeymap,
        ...historyKeymap,
        indentWithTab,
      ]),
      EditorView.lineWrapping,
      EditorState.tabSize.of(Math.max(1, props.tabSize)),
      editableCompartment.of(createEditableExtension(!props.readonly && !props.disabled)),
      languageCompartment.of(
        getLanguageExtension(activeLanguage.value, { json, javascript, html, css, sql })
      ),
      EditorView.updateListener.of((update: any) => {
        if (!update.docChanged || isSyncingFromEditor.value) {
          return;
        }

        emit('update:modelValue', update.state.doc.toString());
      }),
      EditorView.domEventHandlers({
        focus: () => emit('focus'),
        blur: () => emit('blur'),
      }),
    ];

    if (props.placeholder) {
      extensions.push(placeholder(props.placeholder));
    }

    if (props.lineNumbers) {
      extensions.unshift(lineNumbers());
    }

    const state = EditorState.create({
      doc: normalizedValue.value,
      extensions,
    });

    editorView = new EditorView({
      state,
      parent: editorHostRef.value,
    });

    emit('ready');
  } catch (_error) {
    editorInitFailed.value = true;
  }
};

const focusEditor = () => {
  if (props.disabled) {
    return;
  }

  if (isCodeMode.value && editorView) {
    editorView.focus();
    return;
  }

  if (isWysiwygMode.value) {
    richEditorRef.value?.focus();
    return;
  }

  fallbackRef.value?.focus();
};

const onFallbackInput = (event: Event) => {
  const target = event.target as HTMLTextAreaElement | null;
  emit('update:modelValue', target?.value ?? '');
};

const onRichInput = () => {
  emit('update:modelValue', richEditorRef.value?.innerHTML ?? '');
};

watch(
  normalizedValue,
  (nextValue) => {
    if (isWysiwygMode.value) {
      syncRichEditorHtml(nextValue);
      return;
    }

    if (!editorView || isSyncingFromEditor.value) {
      return;
    }

    const currentValue = editorView.state.doc.toString();
    if (currentValue === nextValue) {
      return;
    }

    isSyncingFromEditor.value = true;
    editorView.dispatch({
      changes: { from: 0, to: currentValue.length, insert: nextValue },
    });
    queueMicrotask(() => {
      isSyncingFromEditor.value = false;
    });
  },
  { flush: 'post' }
);

watch(
  () => props.language,
  (nextLanguage) => {
    if (!props.mode) {
      internalMode.value = nextLanguage;
    }
  }
);

watch(
  currentMode,
  async (nextMode, prevMode) => {
    if (nextMode === prevMode) {
      return;
    }

    if (!isCodeMode.value) {
      destroyEditor();
      await nextTick();
      if (nextMode === 'wysiwyg') {
        syncRichEditorHtml(normalizedValue.value);
      }
      return;
    }

    await nextTick();
    await initEditor();
  },
  { flush: 'post' }
);

watch(activeLanguage, async (nextLanguage) => {
  if (!editorView || !languageCompartment || !isCodeMode.value) {
    return;
  }

  const [langJsonModule, langJavascriptModule, langHtmlModule, langCssModule, langSqlModule] =
    await Promise.all([
      import('@codemirror/lang-json'),
      import('@codemirror/lang-javascript'),
      import('@codemirror/lang-html'),
      import('@codemirror/lang-css'),
      import('@codemirror/lang-sql'),
    ]);

  editorView.dispatch({
    effects: languageCompartment.reconfigure(
      getLanguageExtension(nextLanguage, {
        json: langJsonModule.json,
        javascript: langJavascriptModule.javascript,
        html: langHtmlModule.html,
        css: langCssModule.css,
        sql: langSqlModule.sql,
      })
    ),
  });
});

watch([() => props.readonly, () => props.disabled], ([readonly, disabled]) => {
  if (!editorView || !editableCompartment || !createEditableExtension) {
    return;
  }

  editorView.dispatch({
    effects: editableCompartment.reconfigure(createEditableExtension(!readonly && !disabled)),
  });
});

onMounted(async () => {
  if (isWysiwygMode.value) {
    await nextTick();
    syncRichEditorHtml(normalizedValue.value);
    return;
  }

  await initEditor();
});

onBeforeUnmount(() => {
  destroyEditor();
});
</script>
