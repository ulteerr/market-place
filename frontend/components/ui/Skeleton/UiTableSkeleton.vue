<template>
  <div :class="styles.tableWrap" :data-test="dataTest" aria-hidden="true">
    <table :class="styles.table">
      <thead>
        <tr>
          <th v-for="column in normalizedColumns" :key="`h-${column}`">
            <UiSkeletonLine width="75%" height="0.75rem" />
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in normalizedRows" :key="`r-${row}`">
          <td v-for="column in normalizedColumns" :key="`c-${row}-${column}`">
            <UiSkeletonLine />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import styles from './UiSkeleton.module.scss';
import UiSkeletonLine from './UiSkeletonLine.vue';

const props = withDefaults(
  defineProps<{
    columns?: number;
    rows?: number;
    dataTest?: string;
  }>(),
  {
    columns: 4,
    rows: 5,
    dataTest: 'ui-table-skeleton',
  }
);

const normalizedColumns = computed(() => Math.max(1, props.columns));
const normalizedRows = computed(() => Math.max(1, props.rows));
</script>
