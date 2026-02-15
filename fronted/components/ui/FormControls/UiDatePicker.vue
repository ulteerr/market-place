<template>
  <div ref="rootRef" :class="styles.field">
    <label v-if="label" :for="resolvedId" :class="styles.label">
      {{ label }}
      <span v-if="required" :class="styles.required">*</span>
    </label>

    <button
      :id="resolvedId"
      type="button"
      :class="[styles.control, isOpen ? styles.controlOpen : '', error ? styles.controlError : '']"
      :disabled="disabled"
      @click="toggle"
    >
      <template v-if="mode === 'range'">
        <div :class="styles.rangeValues">
          <span :class="styles.rangeValue">
            <span :class="!selectedStart ? styles.placeholder : ''">
              {{ selectedStart ? formatDisplayDate(selectedStart) : placeholderStart }}
            </span>
          </span>
          <span :class="styles.rangeValue">
            <span :class="!selectedEnd ? styles.placeholder : ''">
              {{ selectedEnd ? formatDisplayDate(selectedEnd) : placeholderEnd }}
            </span>
          </span>
        </div>
      </template>
      <template v-else>
        <span :class="[styles.singleValue, !selectedDate ? styles.placeholder : '']">
          {{ selectedDate ? formatDisplayDate(selectedDate) : placeholder }}
        </span>
      </template>

      <svg :class="styles.icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path
          d="M7 2v3M17 2v3M3 9h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </button>

    <div v-if="isOpen && !disabled" :class="styles.menu">
      <div :class="styles.header">
        <button type="button" :class="styles.navBtn" @click="shiftMonth(-1)">‹</button>
        <span :class="styles.monthLabel">{{ currentMonthLabel }}</span>
        <button type="button" :class="styles.navBtn" @click="shiftMonth(1)">›</button>
      </div>

      <div :class="styles.weekdays">
        <span v-for="weekday in weekdays" :key="weekday" :class="styles.weekday">
          {{ weekday }}
        </span>
      </div>

      <div :class="styles.days">
        <button
          v-for="day in calendarDays"
          :key="day.iso"
          type="button"
          :disabled="day.disabled"
          :class="[
            styles.day,
            !day.inMonth ? styles.dayMuted : '',
            day.selected ? styles.daySelected : '',
            day.inRange ? styles.dayInRange : '',
            day.disabled ? styles.dayDisabled : '',
          ]"
          @mousedown.prevent="onDayMouseDown(day)"
          @mouseenter="onDayMouseEnter(day)"
          @click="onDayClick(day)"
        >
          {{ day.label }}
        </button>
      </div>

      <div :class="styles.footer">
        <button type="button" :class="styles.clearBtn" @click="clearValue">
          {{ t('admin.toolbar.reset') }}
        </button>
      </div>
    </div>

    <span v-if="error" :class="styles.error">{{ error }}</span>
    <span v-else-if="hint" :class="styles.hint">{{ hint }}</span>
  </div>
</template>

<script setup lang="ts">
import styles from './UiDatePicker.module.scss';

type DateValue = string | null;
type DateRangeValue = [DateValue, DateValue];
type DatePickerMode = 'single' | 'range';

const props = withDefaults(
  defineProps<{
    modelValue?: DateValue | DateRangeValue;
    mode?: DatePickerMode;
    id?: string;
    label?: string;
    placeholder?: string;
    placeholderStart?: string;
    placeholderEnd?: string;
    hint?: string;
    error?: string;
    required?: boolean;
    disabled?: boolean;
    min?: string | null;
    max?: string | null;
  }>(),
  {
    modelValue: null,
    mode: 'single',
    id: '',
    label: '',
    placeholder: 'dd.mm.yyyy',
    placeholderStart: 'dd.mm.yyyy',
    placeholderEnd: 'dd.mm.yyyy',
    hint: '',
    error: '',
    required: false,
    disabled: false,
    min: null,
    max: null,
  }
);

const emit = defineEmits<{
  (event: 'update:modelValue', value: DateValue | DateRangeValue): void;
}>();

const { locale, t } = useI18n();
const uid = useId();
const resolvedId = computed(() => props.id || `ui-date-picker-${uid}`);

const rootRef = ref<HTMLElement | null>(null);
const isOpen = ref(false);
const pointerDown = ref(false);
const pointerDidDrag = ref(false);
const dragAnchor = ref<string | null>(null);
const dragMode = ref<'new' | 'start' | 'end' | null>(null);

const isoRegex = /^\d{4}-\d{2}-\d{2}$/;

const normalizeIso = (value: unknown): string | null => {
  if (typeof value !== 'string' || !isoRegex.test(value)) {
    return null;
  }

  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return value;
};

const normalizeRange = (value: unknown): DateRangeValue => {
  if (!Array.isArray(value)) {
    return [null, null];
  }

  const start = normalizeIso(value[0]);
  const end = normalizeIso(value[1]);

  if (start && end && start > end) {
    return [end, start];
  }

  return [start, end];
};

const selectedDate = computed<DateValue>(() => {
  if (props.mode !== 'single') {
    return null;
  }

  return normalizeIso(props.modelValue);
});

const selectedRange = computed<DateRangeValue>(() => {
  if (props.mode !== 'range') {
    return [null, null];
  }

  return normalizeRange(props.modelValue);
});

const selectedStart = computed(() => selectedRange.value[0]);
const selectedEnd = computed(() => selectedRange.value[1]);

const initialMonthIso = computed(() => selectedStart.value || selectedDate.value || todayIso());
const currentMonth = ref(firstDayOfMonth(initialMonthIso.value));

watch(
  initialMonthIso,
  (nextIso) => {
    if (!isOpen.value) {
      currentMonth.value = firstDayOfMonth(nextIso);
    }
  },
  { flush: 'post' }
);

const weekdays = computed(() => {
  const base = new Date('2026-02-16T00:00:00');
  const formatter = new Intl.DateTimeFormat(locale.value, { weekday: 'short' });

  return Array.from({ length: 7 }, (_, index) => {
    const date = new Date(base);
    date.setDate(base.getDate() + index);
    return formatter.format(date);
  });
});

const currentMonthLabel = computed(() => {
  const date = parseIso(currentMonth.value);
  return new Intl.DateTimeFormat(locale.value, { month: 'long', year: 'numeric' }).format(date);
});

const minIso = computed(() => normalizeIso(props.min));
const maxIso = computed(() => normalizeIso(props.max));

interface CalendarDay {
  iso: string;
  label: number;
  inMonth: boolean;
  selected: boolean;
  inRange: boolean;
  disabled: boolean;
}

const calendarDays = computed<CalendarDay[]>(() => {
  const monthDate = parseIso(currentMonth.value);
  const monthStartWeekday = ((monthDate.getDay() + 6) % 7) + 1;
  const gridStart = new Date(monthDate);
  gridStart.setDate(monthDate.getDate() - (monthStartWeekday - 1));

  return Array.from({ length: 42 }, (_, index) => {
    const date = new Date(gridStart);
    date.setDate(gridStart.getDate() + index);
    const iso = toIso(date);
    const monthIsoPrefix = currentMonth.value.slice(0, 7);
    const inMonth = iso.startsWith(monthIsoPrefix);
    const isSelectedSingle = props.mode === 'single' && selectedDate.value === iso;
    const isSelectedRange =
      props.mode === 'range' && (selectedStart.value === iso || selectedEnd.value === iso);
    const inRange =
      props.mode === 'range' &&
      Boolean(
        selectedStart.value &&
        selectedEnd.value &&
        selectedStart.value <= iso &&
        iso <= selectedEnd.value
      );
    const disabled = Boolean(
      (minIso.value && iso < minIso.value) || (maxIso.value && iso > maxIso.value)
    );

    return {
      iso,
      label: date.getDate(),
      inMonth,
      selected: isSelectedSingle || isSelectedRange,
      inRange,
      disabled,
    };
  });
});

const toggle = () => {
  if (props.disabled) {
    return;
  }

  isOpen.value = !isOpen.value;
};

const close = () => {
  isOpen.value = false;
};

const shiftMonth = (delta: number) => {
  const date = parseIso(currentMonth.value);
  date.setMonth(date.getMonth() + delta);
  date.setDate(1);
  currentMonth.value = toIso(date);
};

const selectDay = (iso: string) => {
  if (props.mode === 'single') {
    emit('update:modelValue', iso);
    close();
    return;
  }

  const [start, end] = selectedRange.value;

  if (!start || end) {
    emit('update:modelValue', [iso, null]);
    return;
  }

  if (iso < start) {
    emit('update:modelValue', [iso, start]);
    close();
    return;
  }

  emit('update:modelValue', [start, iso]);
  close();
};

const onDayMouseDown = (day: CalendarDay) => {
  if (day.disabled) {
    return;
  }

  pointerDown.value = true;
  pointerDidDrag.value = false;

  if (props.mode !== 'range') {
    return;
  }

  const [start, end] = selectedRange.value;
  dragAnchor.value = day.iso;

  if (start && end && day.iso === start) {
    dragMode.value = 'start';
    return;
  }

  if (start && end && day.iso === end) {
    dragMode.value = 'end';
    return;
  }

  dragMode.value = 'new';
};

const onDayMouseEnter = (day: CalendarDay) => {
  if (!pointerDown.value || day.disabled || props.mode !== 'range') {
    return;
  }

  pointerDidDrag.value = true;

  const [start, end] = selectedRange.value;

  if (dragMode.value === 'start' && end) {
    if (day.iso <= end) {
      emit('update:modelValue', [day.iso, end]);
    } else {
      emit('update:modelValue', [end, day.iso]);
      dragMode.value = 'end';
    }
    return;
  }

  if (dragMode.value === 'end' && start) {
    if (day.iso >= start) {
      emit('update:modelValue', [start, day.iso]);
    } else {
      emit('update:modelValue', [day.iso, start]);
      dragMode.value = 'start';
    }
    return;
  }

  const anchor = dragAnchor.value || day.iso;
  if (day.iso >= anchor) {
    emit('update:modelValue', [anchor, day.iso]);
  } else {
    emit('update:modelValue', [day.iso, anchor]);
  }
};

const onDayClick = (day: CalendarDay) => {
  if (day.disabled) {
    return;
  }

  if (pointerDidDrag.value) {
    pointerDidDrag.value = false;
    return;
  }

  selectDay(day.iso);
};

const clearValue = () => {
  if (props.mode === 'single') {
    emit('update:modelValue', null);
  } else {
    emit('update:modelValue', [null, null]);
  }
};

const formatDisplayDate = (iso: string): string => {
  const date = parseIso(iso);
  return new Intl.DateTimeFormat(locale.value, {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(date);
};

const onDocumentClick = (event: MouseEvent) => {
  const target = event.target as Node | null;
  if (!target || !rootRef.value || rootRef.value.contains(target)) {
    return;
  }

  close();
};

const onEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    pointerDown.value = false;
    pointerDidDrag.value = false;
    dragAnchor.value = null;
    dragMode.value = null;
    close();
  }
};

const onMouseUp = () => {
  if (!pointerDown.value) {
    return;
  }

  pointerDown.value = false;
  dragAnchor.value = null;

  if (props.mode === 'range' && pointerDidDrag.value && selectedStart.value && selectedEnd.value) {
    close();
  }

  pointerDidDrag.value = false;
  dragMode.value = null;
};

onMounted(() => {
  document.addEventListener('mousedown', onDocumentClick);
  document.addEventListener('keydown', onEscape);
  document.addEventListener('mouseup', onMouseUp);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocumentClick);
  document.removeEventListener('keydown', onEscape);
  document.removeEventListener('mouseup', onMouseUp);
});

function parseIso(iso: string): Date {
  return new Date(`${iso}T00:00:00`);
}

function toIso(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function todayIso(): string {
  return toIso(new Date());
}

function firstDayOfMonth(iso: string): string {
  const date = parseIso(iso);
  date.setDate(1);
  return toIso(date);
}
</script>
