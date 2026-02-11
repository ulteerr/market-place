<template>
  <div v-if="visible" class="mt-2 flex flex-wrap items-center justify-between gap-3">
    <p class="admin-muted text-sm">
      {{ t('admin.pagination.summary', { current: currentPage, last: lastPage, perPage }) }}
    </p>

    <div class="flex items-center gap-2">
      <button
        type="button"
        class="admin-button-secondary rounded-md px-3 py-1.5 text-xs"
        :disabled="loading || currentPage <= 1"
        @click="$emit('page', currentPage - 1)"
      >
        {{ t('admin.pagination.back') }}
      </button>

      <template v-for="(item, index) in items" :key="`page-${item}-${index}`">
        <span v-if="item === '...'" class="admin-muted px-1 text-xs">...</span>
        <button
          v-else
          type="button"
          class="page-button rounded-md px-3 py-1.5 text-xs"
          :class="item === currentPage ? 'is-active' : ''"
          :disabled="loading"
          @click="$emit('page', item)"
        >
          {{ item }}
        </button>
      </template>

      <button
        type="button"
        class="admin-button-secondary rounded-md px-3 py-1.5 text-xs"
        :disabled="loading || currentPage >= lastPage"
        @click="$emit('page', currentPage + 1)"
      >
        {{ t('admin.pagination.forward') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { PaginationItem } from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

defineProps<{
  visible: boolean;
  currentPage: number;
  lastPage: number;
  perPage: number;
  items: PaginationItem[];
  loading?: boolean;
}>();

defineEmits<{
  (event: 'page', value: number): void;
}>();
</script>

<style lang="scss" scoped src="./AdminPagination.scss"></style>
