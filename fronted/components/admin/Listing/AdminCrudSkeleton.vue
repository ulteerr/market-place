<template>
  <div class="crud-skeleton">
    <template v-if="mode === 'table'">
      <div class="tablecontainer">
        <table class="skeleton-table">
          <thead>
            <tr>
              <th v-for="column in normalizedTableColumns" :key="`head-${column}`">
                <span class="skeleton-line is-header" />
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in tableRows" :key="`row-${row}`">
              <td v-for="column in normalizedTableColumns" :key="`cell-${row}-${column}`">
                <span class="skeleton-line" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <template v-else-if="mode === 'table-cards'">
      <div v-if="tableOnDesktop" class="hidden md:block tablecontainer">
        <table class="skeleton-table">
          <thead>
            <tr>
              <th v-for="column in normalizedTableColumns" :key="`head-desktop-${column}`">
                <span class="skeleton-line is-header" />
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in tableRows" :key="`row-desktop-${row}`">
              <td v-for="column in normalizedTableColumns" :key="`cell-desktop-${row}-${column}`">
                <span class="skeleton-line" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="tableOnDesktop" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 md:hidden">
        <article v-for="card in cardItems" :key="`card-mobile-${card}`" class="skeleton-card rounded-xl p-4">
          <span class="skeleton-line is-title" />
          <span class="skeleton-line" />
          <span class="skeleton-line" />
          <span class="skeleton-line is-chip" />
          <div class="skeleton-actions">
            <span class="skeleton-line is-action" />
            <span class="skeleton-line is-action" />
          </div>
        </article>
      </div>

      <div v-if="!tableOnDesktop" class="tablecontainer md:hidden">
        <table class="skeleton-table">
          <thead>
            <tr>
              <th v-for="column in normalizedTableColumns" :key="`head-mobile-${column}`">
                <span class="skeleton-line is-header" />
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in tableRows" :key="`row-mobile-${row}`">
              <td v-for="column in normalizedTableColumns" :key="`cell-mobile-${row}-${column}`">
                <span class="skeleton-line" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="!tableOnDesktop" class="hidden md:grid md:grid-cols-3 gap-3">
        <article v-for="card in cardItems" :key="`card-desktop-${card}`" class="skeleton-card rounded-xl p-4">
          <span class="skeleton-line is-title" />
          <span class="skeleton-line" />
          <span class="skeleton-line" />
          <span class="skeleton-line is-chip" />
          <div class="skeleton-actions">
            <span class="skeleton-line is-action" />
            <span class="skeleton-line is-action" />
          </div>
        </article>
      </div>
    </template>

    <template v-else>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article v-for="card in cardItems" :key="`card-${card}`" class="skeleton-card rounded-xl p-4">
          <span class="skeleton-line is-title" />
          <span class="skeleton-line" />
          <span class="skeleton-line" />
          <span class="skeleton-line is-chip" />
          <div class="skeleton-actions">
            <span class="skeleton-line is-action" />
            <span class="skeleton-line is-action" />
          </div>
        </article>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
type ContentMode = 'table' | 'table-cards' | 'cards'

const props = withDefaults(
  defineProps<{
    mode: ContentMode
    tableOnDesktop?: boolean
    tableColumns?: number
    tableRows?: number
    cardItems?: number
  }>(),
  {
    tableOnDesktop: true,
    tableColumns: 4,
    tableRows: 5,
    cardItems: 6
  }
)

const normalizedTableColumns = computed(() => Math.max(1, props.tableColumns))
</script>

<style lang="scss" scoped src="./AdminCrudSkeleton.scss"></style>
