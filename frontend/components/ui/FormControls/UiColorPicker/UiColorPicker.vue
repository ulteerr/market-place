<template>
  <div :class="styles.root">
    <UiInput
      :id="id"
      :name="name"
      :model-value="displayValue"
      :label="label"
      :placeholder="placeholder"
      :hint="hint"
      :error="error"
      :required="required"
      :disabled="disabled"
      @update:model-value="onInput"
    >
      <template #append>
        <span
          :class="styles.preview"
          :style="{ backgroundColor: previewColor }"
          :aria-label="previewAriaLabel"
          aria-live="polite"
        />
      </template>
    </UiInput>

    <section
      :class="[styles.panel, disabled ? styles.panelDisabled : '']"
      :aria-label="paletteLabel"
    >
      <div :class="styles.summary">
        <span
          :class="styles.summaryPreview"
          :style="{ backgroundColor: previewColor }"
          aria-hidden="true"
        />
        <div :class="styles.summaryValues">
          <p>
            <span>HEX</span> <strong>{{ currentHex.slice(1) }}</strong>
          </p>
          <p>
            <span>RGB</span> <strong>{{ currentRgbLabel }}</strong>
          </p>
        </div>
      </div>

      <div
        ref="saturationRef"
        :class="styles.saturationArea"
        :style="{ backgroundColor: saturationBackground }"
        role="presentation"
        @pointerdown="onSaturationPointerDown"
      >
        <span :class="styles.whiteOverlay" />
        <span :class="styles.blackOverlay" />
        <span :class="styles.cursor" :style="cursorStyle" aria-hidden="true" />
      </div>

      <input
        :class="styles.hueSlider"
        type="range"
        min="0"
        max="360"
        :value="hue"
        :disabled="disabled"
        aria-label="Hue"
        @input="onHueInput"
      />

      <div :class="styles.palette" role="group" :aria-label="paletteLabel">
        <button
          v-for="color in colors"
          :key="color"
          type="button"
          :class="[styles.swatch, isSelectedColor(color) ? styles.swatchSelected : '']"
          :style="{ backgroundColor: color }"
          :aria-label="color"
          :title="color"
          :disabled="disabled"
          @click="selectColor(color)"
        />
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import UiInput from '../UiInput/UiInput.vue';
import styles from './UiColorPicker.module.scss';

const props = withDefaults(
  defineProps<{
    modelValue?: string | null;
    id?: string;
    name?: string;
    label?: string;
    placeholder?: string;
    hint?: string;
    error?: string;
    required?: boolean;
    disabled?: boolean;
    colors?: string[];
    paletteLabel?: string;
    previewAriaLabel?: string;
  }>(),
  {
    modelValue: '',
    id: '',
    name: '',
    label: '',
    placeholder: '#000000',
    hint: '',
    error: '',
    required: false,
    disabled: false,
    colors: () => [
      '#E53935',
      '#FB8C00',
      '#FDD835',
      '#43A047',
      '#00ACC1',
      '#1E88E5',
      '#3949AB',
      '#8E24AA',
      '#D81B60',
      '#6D4C41',
      '#546E7A',
      '#000000',
    ],
    paletteLabel: 'Палитра цветов',
    previewAriaLabel: 'Предпросмотр выбранного цвета',
  }
);

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void;
}>();

type Rgb = { r: number; g: number; b: number };
type Hsv = { h: number; s: number; v: number };

const HEX_COLOR_RE = /^#(?:[\da-f]{3}|[\da-f]{6})$/iu;

const hue = ref(0);
const saturation = ref(1);
const value = ref(1);
const saturationRef = ref<HTMLElement | null>(null);

const clamp = (num: number, min: number, max: number) => Math.min(max, Math.max(min, num));

const toHex = (num: number): string => {
  return Math.round(clamp(num, 0, 255))
    .toString(16)
    .padStart(2, '0')
    .toUpperCase();
};

const hexToRgb = (hex: string): Rgb | null => {
  const normalized = hex.trim().replace('#', '');
  if (![3, 6].includes(normalized.length)) {
    return null;
  }

  const full =
    normalized.length === 3
      ? normalized
          .split('')
          .map((x) => `${x}${x}`)
          .join('')
      : normalized;
  const parsed = Number.parseInt(full, 16);
  if (Number.isNaN(parsed)) {
    return null;
  }

  return {
    r: (parsed >> 16) & 255,
    g: (parsed >> 8) & 255,
    b: parsed & 255,
  };
};

const rgbToHex = ({ r, g, b }: Rgb): string => `#${toHex(r)}${toHex(g)}${toHex(b)}`;

const rgbToHsv = ({ r, g, b }: Rgb): Hsv => {
  const rn = r / 255;
  const gn = g / 255;
  const bn = b / 255;
  const max = Math.max(rn, gn, bn);
  const min = Math.min(rn, gn, bn);
  const delta = max - min;

  let h = 0;
  if (delta !== 0) {
    if (max === rn) {
      h = 60 * (((gn - bn) / delta) % 6);
    } else if (max === gn) {
      h = 60 * ((bn - rn) / delta + 2);
    } else {
      h = 60 * ((rn - gn) / delta + 4);
    }
  }

  if (h < 0) {
    h += 360;
  }

  const s = max === 0 ? 0 : delta / max;
  const v = max;
  return { h, s, v };
};

const hsvToRgb = ({ h, s, v }: Hsv): Rgb => {
  const c = v * s;
  const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
  const m = v - c;

  let rp = 0;
  let gp = 0;
  let bp = 0;

  if (h >= 0 && h < 60) {
    rp = c;
    gp = x;
  } else if (h < 120) {
    rp = x;
    gp = c;
  } else if (h < 180) {
    gp = c;
    bp = x;
  } else if (h < 240) {
    gp = x;
    bp = c;
  } else if (h < 300) {
    rp = x;
    bp = c;
  } else {
    rp = c;
    bp = x;
  }

  return {
    r: Math.round((rp + m) * 255),
    g: Math.round((gp + m) * 255),
    b: Math.round((bp + m) * 255),
  };
};

const currentRgb = computed(() => hsvToRgb({ h: hue.value, s: saturation.value, v: value.value }));
const currentHex = computed(() => rgbToHex(currentRgb.value));
const currentRgbLabel = computed(() => {
  const { r, g, b } = currentRgb.value;
  return `${r}, ${g}, ${b}`;
});
const saturationBackground = computed(() => `hsl(${Math.round(hue.value)} 100% 50%)`);
const cursorStyle = computed(() => ({
  left: `${saturation.value * 100}%`,
  top: `${(1 - value.value) * 100}%`,
}));
const displayValue = computed(() => String(props.modelValue ?? '').toUpperCase());
const previewColor = computed(() => {
  const valueRaw = displayValue.value.trim();
  if (HEX_COLOR_RE.test(valueRaw)) {
    return valueRaw;
  }
  return currentHex.value;
});

const applyHexToState = (nextHex: string) => {
  const rgb = hexToRgb(nextHex);
  if (!rgb) {
    return;
  }
  const hsv = rgbToHsv(rgb);
  hue.value = clamp(hsv.h, 0, 360);
  saturation.value = clamp(hsv.s, 0, 1);
  value.value = clamp(hsv.v, 0, 1);
};

watch(
  () => props.modelValue,
  (next) => {
    const normalized = String(next ?? '')
      .trim()
      .toUpperCase();
    if (HEX_COLOR_RE.test(normalized)) {
      applyHexToState(normalized);
    }
  },
  { immediate: true }
);

const emitCurrentColor = () => {
  emit('update:modelValue', currentHex.value);
};

const updateSaturationByPointer = (clientX: number, clientY: number) => {
  const el = saturationRef.value;
  if (!el) {
    return;
  }

  const rect = el.getBoundingClientRect();
  const x = clamp((clientX - rect.left) / rect.width, 0, 1);
  const y = clamp((clientY - rect.top) / rect.height, 0, 1);

  saturation.value = x;
  value.value = 1 - y;
  emitCurrentColor();
};

const onSaturationPointerDown = (event: PointerEvent) => {
  if (props.disabled) {
    return;
  }

  const target = event.currentTarget as HTMLElement | null;
  target?.setPointerCapture?.(event.pointerId);
  updateSaturationByPointer(event.clientX, event.clientY);

  const onMove = (moveEvent: PointerEvent) => {
    updateSaturationByPointer(moveEvent.clientX, moveEvent.clientY);
  };

  const onUp = () => {
    window.removeEventListener('pointermove', onMove);
    window.removeEventListener('pointerup', onUp);
  };

  window.addEventListener('pointermove', onMove);
  window.addEventListener('pointerup', onUp);
};

const onHueInput = (event: Event) => {
  const target = event.target as HTMLInputElement | null;
  const nextHue = Number(target?.value ?? 0);
  hue.value = clamp(nextHue, 0, 360);
  emitCurrentColor();
};

const onInput = (valueRaw: string) => {
  const next = valueRaw.toUpperCase().trim();
  emit('update:modelValue', next);
  if (HEX_COLOR_RE.test(next)) {
    applyHexToState(next);
  }
};

const isSelectedColor = (color: string): boolean => {
  return displayValue.value.trim() === color.trim().toUpperCase();
};

const selectColor = (color: string) => {
  const normalized = color.toUpperCase();
  applyHexToState(normalized);
  emit('update:modelValue', normalized);
};
</script>
