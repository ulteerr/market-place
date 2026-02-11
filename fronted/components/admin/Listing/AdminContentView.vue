<template>
  <div>
    <div v-if="mode === 'table'" class="tablecontainer">
      <slot name="table" />
    </div>

    <template v-else-if="mode === 'table-cards'">
      <div v-if="tableOnDesktop" class="hidden md:block tablecontainer">
        <slot name="table" />
      </div>
      <div v-if="tableOnDesktop" class="md:hidden">
        <slot name="cards" />
      </div>

      <div v-if="!tableOnDesktop" class="tablecontainer md:hidden">
        <slot name="table" />
      </div>
      <div v-if="!tableOnDesktop" class="hidden md:block">
        <slot name="cards" />
      </div>
    </template>

    <div v-else>
      <slot name="cards" />
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    mode: 'table' | 'table-cards' | 'cards';
    tableOnDesktop?: boolean;
  }>(),
  {
    tableOnDesktop: true,
  }
);
</script>

<style lang="scss" scoped src="./AdminContentView.scss"></style>
