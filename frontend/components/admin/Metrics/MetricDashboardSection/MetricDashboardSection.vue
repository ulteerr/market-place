<template>
  <section class="metric-dashboard-section space-y-4" data-test="metric-dashboard-section">
    <div class="grid gap-4 xl:grid-cols-2" data-test="metric-dashboard-row-top">
      <MetricDonutCard
        :title="donutData.title"
        :total-label="donutData.totalLabel"
        :total-value="donutData.totalValue"
        :segments="donutData.segments"
        :height="donutData.height"
      />

      <MetricLineChartCard
        :title="lineData.title"
        :y-label="lineData.yLabel"
        :x-labels="lineData.xLabels"
        :series="lineData.series"
        :grid-steps="lineData.gridSteps"
      />
    </div>

    <MetricKpiGrid :items="kpiItems" />
  </section>
</template>

<script setup lang="ts">
import MetricDonutCard from '../MetricDonutCard/MetricDonutCard.vue';
import MetricKpiGrid from '../MetricKpiGrid/MetricKpiGrid.vue';
import MetricLineChartCard from '../MetricLineChartCard/MetricLineChartCard.vue';
import type { MetricDonutData, MetricKpi, MetricLineChartData } from '../Metrics.types';

defineProps<{
  donutData: MetricDonutData;
  lineData: MetricLineChartData;
  kpiItems: MetricKpi[];
}>();
</script>
