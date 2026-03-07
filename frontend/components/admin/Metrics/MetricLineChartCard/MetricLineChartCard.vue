<template>
  <article class="admin-card metric-line-card rounded-2xl p-5 lg:p-6" data-test="metric-line-card">
    <h3 class="metric-line-card__title text-2xl font-semibold">{{ title }}</h3>

    <div v-if="hasData" class="metric-line-card__chart-wrap">
      <svg
        ref="chartRef"
        class="metric-line-card__chart"
        viewBox="0 0 100 64"
        role="img"
        :aria-label="`${title} chart`"
        @mousemove="onChartMouseMove"
        @mouseleave="clearHover"
      >
        <line
          v-for="line in gridLines"
          :key="`grid-${line.value}`"
          class="metric-line-card__grid-line"
          :x1="padding.left"
          :x2="100 - padding.right"
          :y1="line.y"
          :y2="line.y"
        />

        <line
          v-if="hoveredIndex !== null"
          class="metric-line-card__crosshair"
          :x1="xToCoord(hoveredIndex)"
          :x2="xToCoord(hoveredIndex)"
          :y1="padding.top"
          :y2="64 - padding.bottom"
          data-test="metric-line-crosshair"
        />

        <path
          v-for="seriesPath in seriesPaths"
          :key="seriesPath.name"
          class="metric-line-card__series"
          data-test="metric-line-path"
          :stroke="seriesPath.color"
          :d="seriesPath.path"
        />

        <circle
          v-for="point in hoveredPoints"
          :key="`marker-${point.name}`"
          class="metric-line-card__marker"
          :cx="point.x"
          :cy="point.y"
          :fill="point.color"
          data-test="metric-line-marker"
        />
      </svg>

      <ul class="metric-line-card__y-ticks" data-test="metric-line-y-ticks">
        <li
          v-for="line in gridLines"
          :key="`tick-${line.value}`"
          :style="{ top: `${(line.y / 64) * 100}%` }"
        >
          {{ line.label }}
        </li>
      </ul>

      <div class="metric-line-card__y-label">{{ yLabel }}</div>

      <ul
        class="metric-line-card__x-axis"
        data-test="metric-line-x-labels"
        :style="{ gridTemplateColumns: `repeat(${Math.max(xLabels.length, 1)}, minmax(0, 1fr))` }"
      >
        <li
          v-for="(label, index) in xLabels"
          :key="label"
          tabindex="0"
          :class="{ 'is-active': hoveredIndex === index }"
          @mouseenter="setHoveredIndex(index)"
          @focus="setHoveredIndex(index)"
          @mouseleave="clearHover"
          @blur="clearHover"
        >
          {{ label }}
        </li>
      </ul>

      <div
        v-if="hoveredIndex !== null"
        class="metric-line-card__tooltip"
        data-test="metric-line-tooltip"
        :style="tooltipStyle"
      >
        <p class="metric-line-card__tooltip-title">{{ xLabels[hoveredIndex] }}</p>
        <ul class="metric-line-card__tooltip-list">
          <li v-for="point in hoveredPoints" :key="`tooltip-${point.name}`">
            <span class="metric-line-card__tooltip-dot" :style="{ backgroundColor: point.color }" />
            <span class="metric-line-card__tooltip-name">{{ point.name }}:</span>
            <strong class="metric-line-card__tooltip-value">{{ point.value }}</strong>
          </li>
        </ul>
      </div>
    </div>

    <p v-else class="metric-line-card__empty" data-test="metric-line-empty">Нет данных</p>

    <ul class="metric-line-card__legend" data-test="metric-line-legend">
      <li
        v-for="seriesItem in normalizedSeries"
        :key="seriesItem.name"
        class="metric-line-card__legend-item"
      >
        <span
          class="metric-line-card__legend-color"
          :style="{ backgroundColor: seriesItem.color }"
          aria-hidden="true"
        />
        <span>{{ seriesItem.name }}</span>
      </li>
    </ul>
  </article>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import type { CSSProperties } from 'vue';
import type { LineSeries } from '../Metrics.types';

interface SeriesPath {
  name: string;
  color: string;
  path: string;
}

interface HoveredPoint {
  name: string;
  color: string;
  value: number;
  x: number;
  y: number;
}

const props = withDefaults(
  defineProps<{
    title?: string;
    yLabel?: string;
    xLabels?: string[];
    series?: LineSeries[];
    gridSteps?: number;
  }>(),
  {
    title: 'Заказы',
    yLabel: '',
    xLabels: () => [],
    series: () => [],
    gridSteps: 4,
  }
);

const padding = {
  top: 6,
  right: 4,
  bottom: 10,
  left: 10,
};

const chartRef = ref<SVGSVGElement | null>(null);
const hoveredIndex = ref<number | null>(null);

const normalizedSeries = computed<LineSeries[]>(() => {
  return props.series.filter((item) => item.points.length > 0);
});

const allValues = computed<number[]>(() => {
  return normalizedSeries.value.flatMap((item) => item.points.map((point) => point.y));
});

const maxY = computed<number>(() => {
  const value = Math.max(...allValues.value, 0);
  if (value <= 0) {
    return 1;
  }

  return value;
});

const hasData = computed<boolean>(
  () => normalizedSeries.value.length > 0 && props.xLabels.length > 0
);

const xToCoord = (index: number): number => {
  const usableWidth = 100 - padding.left - padding.right;
  const denominator = Math.max(props.xLabels.length - 1, 1);
  return padding.left + (index / denominator) * usableWidth;
};

const yToCoord = (value: number): number => {
  const usableHeight = 64 - padding.top - padding.bottom;
  const normalized = value / maxY.value;
  return 64 - padding.bottom - normalized * usableHeight;
};

const gridLines = computed(() => {
  const steps = Math.max(props.gridSteps, 2);
  return Array.from({ length: steps + 1 }, (_, index) => {
    const value = (maxY.value / steps) * (steps - index);
    return {
      value,
      label: Math.round(value),
      y: yToCoord(value),
    };
  });
});

const toSmoothPath = (coords: Array<{ x: number; y: number }>): string => {
  if (coords.length === 0) {
    return '';
  }

  if (coords.length < 3) {
    return coords
      .map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`)
      .join(' ');
  }

  let path = `M ${coords[0]!.x} ${coords[0]!.y}`;

  for (let index = 0; index < coords.length - 1; index += 1) {
    const prev = coords[index - 1] ?? coords[index]!;
    const current = coords[index]!;
    const next = coords[index + 1]!;
    const nextNext = coords[index + 2] ?? next;

    const cp1x = current.x + (next.x - prev.x) / 6;
    const cp1y = current.y + (next.y - prev.y) / 6;
    const cp2x = next.x - (nextNext.x - current.x) / 6;
    const cp2y = next.y - (nextNext.y - current.y) / 6;

    path += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${next.x} ${next.y}`;
  }

  return path;
};

const seriesPaths = computed<SeriesPath[]>(() => {
  return normalizedSeries.value.map((seriesItem) => {
    const coords = seriesItem.points.map((point, index) => ({
      x: xToCoord(index),
      y: yToCoord(point.y),
    }));

    return {
      name: seriesItem.name,
      color: seriesItem.color,
      path: toSmoothPath(coords),
    };
  });
});

const hoveredPoints = computed<HoveredPoint[]>(() => {
  if (hoveredIndex.value === null) {
    return [];
  }

  return normalizedSeries.value
    .map((seriesItem) => {
      const point = seriesItem.points[hoveredIndex.value!];
      if (!point) {
        return null;
      }

      return {
        name: seriesItem.name,
        color: seriesItem.color,
        value: point.y,
        x: xToCoord(hoveredIndex.value!),
        y: yToCoord(point.y),
      };
    })
    .filter((value): value is HoveredPoint => Boolean(value));
});

const tooltipStyle = computed<CSSProperties>(() => {
  if (hoveredIndex.value === null) {
    return {};
  }

  const ratio = props.xLabels.length > 1 ? hoveredIndex.value / (props.xLabels.length - 1) : 0;
  const x = xToCoord(hoveredIndex.value);

  return {
    left: `${x}%`,
    top: '44%',
    transform:
      ratio > 0.7
        ? 'translate(-100%, -50%)'
        : ratio > 0.35
          ? 'translate(-50%, -50%)'
          : 'translate(0, -50%)',
  };
});

const setHoveredIndex = (index: number): void => {
  hoveredIndex.value = index;
};

const clearHover = (): void => {
  hoveredIndex.value = null;
};

const onChartMouseMove = (event: MouseEvent): void => {
  if (!chartRef.value || props.xLabels.length === 0) {
    return;
  }

  const bounds = chartRef.value.getBoundingClientRect();
  if (bounds.width === 0) {
    return;
  }

  const ratio = (event.clientX - bounds.left) / bounds.width;
  const index = Math.round(ratio * (props.xLabels.length - 1));
  hoveredIndex.value = Math.max(0, Math.min(props.xLabels.length - 1, index));
};
</script>

<style lang="scss" src="./MetricLineChartCard.scss"></style>
