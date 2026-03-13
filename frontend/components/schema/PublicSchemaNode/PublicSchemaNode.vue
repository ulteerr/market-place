<template />

<script setup lang="ts">
import {
  usePublicSchemaNode,
  type PublicSchemaNode,
} from '~/composables/schema/usePublicSchemaRegistry';

const props = defineProps<{
  schemaKey: string;
  node: PublicSchemaNode | PublicSchemaNode[] | null | undefined;
}>();

usePublicSchemaNode(
  computed(() => props.schemaKey),
  computed(() => props.node)
);
</script>
