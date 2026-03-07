<template>
  <article
    class="admin-card metric-kpi-card rounded-2xl p-5"
    :class="`is-${trendType}`"
    :style="cssVars"
    data-test="metric-kpi-card"
  >
    <header class="metric-kpi-card__header">
      <p class="metric-kpi-card__title">
        <span v-if="icon" class="metric-kpi-card__icon" aria-hidden="true">{{ icon }}</span>
        {{ title }}
      </p>
      <p class="metric-kpi-card__value" data-test="metric-kpi-value">{{ value }}</p>
      <p class="metric-kpi-card__delta" data-test="metric-kpi-delta">{{ deltaText }}</p>
    </header>

    <div class="metric-kpi-card__chart-wrap">
      <svg
        v-if="hasTrend"
        class="metric-kpi-card__chart"
        viewBox="0 0 100 32"
        role="img"
        :aria-label="`${title} trend`"
      >
        <defs>
          <linearGradient :id="gradientId" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" :stop-color="accentColor" stop-opacity="0.38" />
            <stop offset="100%" :stop-color="accentColor" stop-opacity="0" />
          </linearGradient>
        </defs>
        <path :d="areaPath" :fill="`url(#${gradientId})`" data-test="metric-kpi-area" />
        <path :d="linePath" class="metric-kpi-card__line" data-test="metric-kpi-line" />
      </svg>
      <p v-else class="metric-kpi-card__empty" data-test="metric-kpi-empty">Нет данных</p>
    </div>
  </article>
</template>

<script setup lang="ts">
import type { CSSProperties } from 'vue';
import type { MetricTrendPoint, MetricTrendType } from '../Metrics.types';

const props = withDefaults(
  defineProps<{
    title: string;
    value: string | number;
    deltaText: string;
    trend: MetricTrendPoint[];
    trendType?: MetricTrendType;
    accentColor?: string;
    icon?: string;
  }>(),
  {
    trendType: 'neutral',
    accentColor: '#1f90ea',
    icon: '',
  }
);

const gradientId = `metric-kpi-gradient-${useId()}`;

const hasTrend = computed(() => props.trend.length > 1);

const minMaxY = computed(() => {
  const values = props.trend.map((item) => item.y);
  const min = Math.min(...values, 0);
  const max = Math.max(...values, 1);
  const range = max - min || 1;

  return { min, max, range };
});

const pointToCoords = (index: number, value: number): { x: number; y: number } => {
  const denominator = Math.max(props.trend.length - 1, 1);
  const x = (index / denominator) * 100;
  const normalized = (value - minMaxY.value.min) / minMaxY.value.range;
  const y = 30 - normalized * 24;

  return { x, y };
};

const linePath = computed(() => {
  if (!hasTrend.value) {
    return '';
  }

  return props.trend
    .map((point, index) => {
      const coords = pointToCoords(index, point.y);
      return `${index === 0 ? 'M' : 'L'} ${coords.x} ${coords.y}`;
    })
    .join(' ');
});

const areaPath = computed(() => {
  if (!hasTrend.value) {
    return '';
  }

  const first = pointToCoords(0, props.trend[0]!.y);
  const last = pointToCoords(props.trend.length - 1, props.trend.at(-1)!.y);

  return `${linePath.value} L ${last.x} 32 L ${first.x} 32 Z`;
});

const cssVars = computed<CSSProperties>(() => ({
  '--metric-kpi-accent': props.accentColor,
}));
</script>

<style lang="scss" src="./MetricKpiCard.scss"></style>
