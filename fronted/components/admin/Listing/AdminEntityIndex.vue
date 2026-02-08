<template>
  <section :class="['admin-entity-page mx-auto w-full min-w-0 space-y-6', maxWidthClass, pageClass]">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-2xl font-semibold">{{ title }}</h2>
          <p class="admin-muted mt-2 text-sm">{{ subtitle }}</p>
        </div>

        <NuxtLink :to="createTo" class="admin-button rounded-lg px-4 py-2 text-sm">{{ createLabel }}</NuxtLink>
      </div>
    </div>

    <article class="admin-card min-w-0 rounded-2xl p-5 lg:p-6 space-y-4">
      <AdminListToolbar
        :search-value="searchValue"
        :search-placeholder="searchPlaceholder"
        :per-page="perPage"
        :per-page-options="perPageOptions"
        :loading="loading"
        @update:search-value="(value) => $emit('update:searchValue', value)"
        @update:per-page="(value) => $emit('update:perPage', value)"
        @apply="$emit('apply')"
        @reset="$emit('reset')"
      />

      <div class="flex items-center justify-between gap-3">
        <p class="admin-muted text-sm">Показано {{ shownCount }} из {{ totalCount }}.</p>

        <div class="flex items-center gap-2">
          <div class="mode-select-wrap">
            <UiSelect
              :model-value="mode"
              :options="modeOptions"
              placeholder="Таблица"
              :searchable="false"
              @update:model-value="onModeChange"
            />
          </div>

          <button
            v-if="mode === 'table-cards'"
            type="button"
            class="admin-button-secondary rounded-md px-2 py-1.5 text-xs"
            @click="$emit('toggleDesktop')"
          >
            {{ tableOnDesktop ? 'Desktop: таблица' : 'Desktop: карточки' }}
          </button>
        </div>
      </div>

      <p v-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <AdminCrudSkeleton
        v-if="loading"
        :mode="mode"
        :table-on-desktop="tableOnDesktop"
        :table-columns="tableSkeletonColumns"
      />

      <AdminContentView v-else :mode="mode" :table-on-desktop="tableOnDesktop">
        <template #table>
          <slot name="table" />
        </template>

        <template #cards>
          <div class="sort-tags mb-3">
            <button
              v-for="sortField in cardSortFields"
              :key="sortField.value"
              type="button"
              :class="['sort-tag', { 'is-active': activeSortBy === sortField.value }]"
              @click="$emit('sort', sortField.value)"
            >
              {{ sortField.label }} {{ sortMark(sortField.value) }}
            </button>
          </div>

          <slot name="cards" />
        </template>
      </AdminContentView>

      <AdminPagination
        :visible="showPagination"
        :current-page="currentPage"
        :last-page="lastPage"
        :per-page="paginationPerPage"
        :items="paginationItems"
        :loading="loading"
        @page="(page) => $emit('page', page)"
      />
    </article>
  </section>
</template>

<script setup lang="ts">
import type { PaginationItem } from '~/composables/useAdminCrudCommon'
import UiSelect from '~/components/ui/FormControls/UiSelect.vue'
import AdminListToolbar from '~/components/admin/Listing/AdminListToolbar.vue'
import AdminPagination from '~/components/admin/Listing/AdminPagination.vue'
import AdminContentView from '~/components/admin/Listing/AdminContentView.vue'
import AdminCrudSkeleton from '~/components/admin/Listing/AdminCrudSkeleton.vue'

type ContentMode = 'table' | 'table-cards' | 'cards'

interface CardSortField {
  value: string
  label: string
}

const emit = defineEmits<{
  (e: 'update:searchValue', value: string): void
  (e: 'update:perPage', value: number): void
  (e: 'update:mode', value: ContentMode): void
  (e: 'toggleDesktop'): void
  (e: 'apply'): void
  (e: 'reset'): void
  (e: 'sort', field: string): void
  (e: 'page', value: number): void
}>()

defineProps<{
  pageClass?: string
  maxWidthClass?: string
  title: string
  subtitle: string
  createTo: string
  createLabel: string
  searchValue: string
  searchPlaceholder: string
  perPage: number
  perPageOptions: number[]
  loading: boolean
  shownCount: number
  totalCount: number
  loadError: string
  mode: ContentMode
  tableOnDesktop: boolean
  cardSortFields: CardSortField[]
  activeSortBy: string
  sortMark: (field: string) => string
  showPagination: boolean
  currentPage: number
  lastPage: number
  paginationPerPage: number
  paginationItems: PaginationItem[]
  tableSkeletonColumns?: number
}>()

const modeOptions = [
  { value: 'table', label: 'Таблица' },
  { value: 'table-cards', label: 'Таблица + карточки' },
  { value: 'cards', label: 'Карточки' }
] as const

const onModeChange = (value: string | number | (string | number)[]) => {
  const nextValue = Array.isArray(value) ? value[0] : value

  if (nextValue === 'table' || nextValue === 'table-cards' || nextValue === 'cards') {
    emit('update:mode', nextValue)
  }
}
</script>

<style lang="scss" src="./AdminEntityIndex.scss"></style>
