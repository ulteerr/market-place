<template>
  <article
    class="admin-card metric-donut-card rounded-2xl p-5 lg:p-6"
    :style="cardStyle"
    :aria-label="`${title}: ${centerValue}`"
    data-test="metric-donut-card"
  >
    <h3 class="metric-donut-card__title text-2xl font-semibold">{{ title }}</h3>

    <div class="metric-donut-card__body">
      <div class="metric-donut-card__chart-wrap">
        <svg
          class="metric-donut-card__chart"
          viewBox="0 0 120 120"
          role="img"
          aria-label="Donut chart"
        >
          <circle
            cx="60"
            cy="60"
            :r="radius"
            class="metric-donut-card__track"
            :stroke-width="strokeWidth"
          />

          <circle
            v-for="segment in arcSegments"
            :key="segment.label"
            cx="60"
            cy="60"
            :r="radius"
            :class="[
              'metric-donut-card__segment',
              { 'is-active': activeSegment?.label === segment.label },
            ]"
            :stroke="segment.color"
            :stroke-width="strokeWidth"
            :stroke-dasharray="segment.dashArray"
            :stroke-dashoffset="segment.dashOffset"
            tabindex="0"
            @mouseenter="setActive(segment.label)"
            @focus="setActive(segment.label)"
            @mouseleave="clearActive"
            @blur="clearActive"
          />
        </svg>

        <div class="metric-donut-card__center">
          <p class="metric-donut-card__total-label" :style="centerLabelStyle">{{ centerLabel }}</p>
          <p
            class="metric-donut-card__total-value"
            data-test="metric-donut-total"
            :style="centerLabelStyle"
          >
            {{ centerValue }}
          </p>
        </div>

        <div
          v-if="activeSegment"
          class="metric-donut-card__tooltip"
          data-test="metric-donut-tooltip"
          :style="tooltipStyle"
        >
          <span>{{ activeSegment.label }}:</span>
          <strong>{{ formatNumber(activeSegment.value) }}</strong>
        </div>
      </div>

      <ul
        class="metric-donut-card__legend"
        data-test="metric-donut-legend"
        :aria-label="`${title} legend`"
      >
        <li
          v-for="segment in normalizedSegments"
          :key="segment.label"
          class="metric-donut-card__legend-item"
          :class="{ 'is-active': activeSegment?.label === segment.label }"
          data-test="metric-donut-legend-item"
          tabindex="0"
          @mouseenter="setActive(segment.label)"
          @focus="setActive(segment.label)"
          @mouseleave="clearActive"
          @blur="clearActive"
        >
          <span
            class="metric-donut-card__legend-color"
            :style="{ backgroundColor: segment.color }"
            aria-hidden="true"
          />
          <span>{{ segment.label }}</span>
        </li>

        <li
          v-if="!normalizedSegments.length"
          class="metric-donut-card__legend-empty"
          data-test="metric-donut-legend-empty"
        >
          Нет данных
        </li>
      </ul>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import type { CSSProperties } from 'vue';
import type { DonutSegment } from '../Metrics.types';

interface DonutArcSegment extends DonutSegment {
  dashArray: string;
  dashOffset: number;
  tooltipX: number;
  tooltipY: number;
}

const props = withDefaults(
  defineProps<{
    title?: string;
    totalLabel?: string;
    totalValue?: number | string | null;
    segments?: DonutSegment[];
    height?: number;
  }>(),
  {
    title: 'Подписчики',
    totalLabel: 'Total',
    totalValue: null,
    segments: () => [],
    height: 520,
  }
);

const radius = 42;
const strokeWidth = 16;
const circumference = 2 * Math.PI * radius;

const activeLabel = ref<string | null>(null);

const cardStyle = computed<CSSProperties>(() => ({
  minHeight: `${props.height}px`,
}));

const normalizedSegments = computed<DonutSegment[]>(() => {
  return props.segments
    .filter((segment) => segment.value > 0)
    .map((segment) => ({
      ...segment,
      value: Number(segment.value),
    }));
});

const calculatedTotal = computed<number>(() => {
  return normalizedSegments.value.reduce((total, segment) => total + segment.value, 0);
});

const resolvedTotal = computed<number | string>(() => {
  if (typeof props.totalValue === 'number' && Number.isFinite(props.totalValue)) {
    return props.totalValue;
  }

  if (typeof props.totalValue === 'string' && props.totalValue.trim()) {
    return props.totalValue;
  }

  return calculatedTotal.value;
});

const formatNumber = (value: number | string): string => {
  if (typeof value === 'number') {
    return new Intl.NumberFormat('en-US').format(value);
  }

  return value;
};

const formattedTotalValue = computed<string>(() => formatNumber(resolvedTotal.value));

const arcSegments = computed<DonutArcSegment[]>(() => {
  const total = calculatedTotal.value;
  if (total <= 0) {
    return [];
  }

  let offsetRatio = 0;

  return normalizedSegments.value.map((segment) => {
    const ratio = segment.value / total;
    const arcLength = ratio * circumference;
    const dashArray = `${arcLength} ${Math.max(circumference - arcLength, 0)}`;
    const dashOffset = -offsetRatio * circumference;

    const midRatio = offsetRatio + ratio / 2;
    const angle = -Math.PI / 2 + midRatio * Math.PI * 2;
    const tooltipRadius = 33;
    const tooltipX = 50 + Math.cos(angle) * tooltipRadius;
    const tooltipY = 50 + Math.sin(angle) * tooltipRadius;

    offsetRatio += ratio;

    return {
      ...segment,
      dashArray,
      dashOffset,
      tooltipX,
      tooltipY,
    };
  });
});

const activeSegment = computed(() => {
  if (!activeLabel.value) {
    return null;
  }

  return arcSegments.value.find((item) => item.label === activeLabel.value) ?? null;
});

const centerLabel = computed(() => activeSegment.value?.label ?? props.totalLabel);
const centerValue = computed(() =>
  activeSegment.value ? formatNumber(activeSegment.value.value) : formattedTotalValue.value
);
const centerLabelStyle = computed<CSSProperties>(() => ({
  color: activeSegment.value?.color || '',
}));

const tooltipStyle = computed<CSSProperties>(() => {
  if (!activeSegment.value) {
    return {};
  }

  return {
    left: `${activeSegment.value.tooltipX}%`,
    top: `${activeSegment.value.tooltipY}%`,
    background: activeSegment.value.color,
  };
});

const setActive = (label: string): void => {
  activeLabel.value = label;
};

const clearActive = (): void => {
  activeLabel.value = null;
};
</script>

<style lang="scss" src="./MetricDonutCard.scss"></style>
