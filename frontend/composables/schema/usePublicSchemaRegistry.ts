import { onBeforeUnmount, toValue, watch } from 'vue';
import type { MaybeRefOrGetter } from 'vue';

export type PublicSchemaNode = Record<string, unknown>;

type PublicSchemaEntry = {
  key: string;
  nodes: PublicSchemaNode[];
};

const serializeNodes = (nodes: PublicSchemaNode[]): string => JSON.stringify(nodes);

export const isPublicSchemaRoute = (path: string): boolean =>
  path === '/' || path.startsWith('/catalog') || path.startsWith('/content');

export const usePublicSchemaRegistry = () => {
  const route = useRoute();
  const entries = useState<PublicSchemaEntry[]>('public_schema_registry', () => []);
  const isPublicRoute = computed(() => isPublicSchemaRoute(route.path));

  const upsertEntry = (key: string, nodes: PublicSchemaNode[]) => {
    if (!isPublicRoute.value || nodes.length === 0) {
      removeEntry(key);
      return;
    }

    const nextEntry = { key, nodes };
    const index = entries.value.findIndex((entry) => entry.key === key);

    if (index === -1) {
      entries.value = [...entries.value, nextEntry];
      return;
    }

    const currentEntry = entries.value[index];
    if (serializeNodes(currentEntry.nodes) === serializeNodes(nodes)) {
      return;
    }

    const nextEntries = [...entries.value];
    nextEntries[index] = nextEntry;
    entries.value = nextEntries;
  };

  const removeEntry = (key: string) => {
    entries.value = entries.value.filter((entry) => entry.key !== key);
  };

  const aggregatedNodes = computed(() => entries.value.flatMap((entry) => entry.nodes));
  const canRenderSchema = computed(() => isPublicRoute.value && aggregatedNodes.value.length > 0);

  return {
    aggregatedNodes,
    canRenderSchema,
    isPublicRoute,
    upsertEntry,
    removeEntry,
  };
};

export const usePublicSchemaNode = (
  key: MaybeRefOrGetter<string>,
  nodes: MaybeRefOrGetter<PublicSchemaNode | PublicSchemaNode[] | null | undefined>
) => {
  const registry = usePublicSchemaRegistry();

  watch(
    [
      () => toValue(key),
      () => {
        const resolved = toValue(nodes);
        const normalized = (Array.isArray(resolved) ? resolved : resolved ? [resolved] : []).filter(
          (node): node is PublicSchemaNode => Boolean(node)
        );

        return {
          normalized,
          signature: serializeNodes(normalized),
        };
      },
      () => registry.isPublicRoute.value,
    ],
    ([resolvedKey, resolvedNodes]) => {
      registry.upsertEntry(resolvedKey, resolvedNodes.normalized);
    },
    {
      immediate: true,
    }
  );

  if (import.meta.client) {
    onBeforeUnmount(() => {
      registry.removeEntry(toValue(key));
    });
  }
};
