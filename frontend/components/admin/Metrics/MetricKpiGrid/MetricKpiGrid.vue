<template>
  <section
    class="metric-kpi-grid grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
    data-test="metric-kpi-grid"
  >
    <MetricKpiCard
      v-for="item in items"
      :key="item.title"
      :title="item.title"
      :value="item.value"
      :delta-text="item.deltaText"
      :trend="item.trend"
      :trend-type="item.trendType"
      :accent-color="item.accentColor"
      :icon="item.icon"
    />
  </section>
</template>

<script setup lang="ts">
import MetricKpiCard from '../MetricKpiCard/MetricKpiCard.vue';
import type { MetricKpi } from '../Metrics.types';

defineProps<{
  items: MetricKpi[];
}>();
</script>

<style lang="scss" src="./MetricKpiGrid.scss"></style>
