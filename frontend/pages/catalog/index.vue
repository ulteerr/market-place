<template>
  <div>
    <PageHero
      :eyebrow="t('app.public.catalog.eyebrow')"
      :title="heroTitle"
      :description="heroDescription"
    />

    <PublicSection
      class="catalog-page__section"
      :title="t('app.public.catalog.sectionTitle')"
      data-test="catalog-activities"
    >
      <UiFilterBarSkeleton v-if="categoriesPending" data-test="catalog-category-browser-loading" />
      <PublicStateMessage
        v-else-if="categoriesError"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.filters.errorTitle')"
        :description="categoriesError"
        data-test="catalog-category-browser-error"
      />
      <PublicStateMessage
        v-else-if="!categoriesTree.length"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.filters.emptyTitle')"
        :description="t('app.public.catalog.filters.emptyDescription')"
        data-test="catalog-category-browser-empty"
      />
      <div v-else class="catalog-browser" data-test="catalog-category-browser">
        <div class="catalog-browser__group" data-test="catalog-root-categories">
          <div class="catalog-browser__label">
            {{ t('app.public.catalog.filters.rootCategoryLabel') }}
          </div>
          <div class="catalog-browser__chips">
            <button
              type="button"
              class="catalog-browser__chip"
              :class="{ 'catalog-browser__chip--active': !filters.root_category_id }"
              data-test="catalog-root-category-all"
              @click="selectRootCategory('')"
            >
              {{ t('app.public.catalog.filters.rootCategoryAny') }}
            </button>
            <button
              v-for="category in categoriesTree"
              :key="category.id"
              type="button"
              class="catalog-browser__chip"
              :class="{ 'catalog-browser__chip--active': filters.root_category_id === category.id }"
              :data-test="`catalog-root-category-${category.id}`"
              @click="selectRootCategory(category.id)"
            >
              {{ category.name }}
            </button>
          </div>
        </div>

        <div
          v-if="selectedRootCategory"
          class="catalog-browser__group"
          data-test="catalog-leaf-categories"
        >
          <div class="catalog-browser__label">
            {{ t('app.public.catalog.filters.categoryLabel') }}
          </div>
          <div class="catalog-browser__chips">
            <button
              type="button"
              class="catalog-browser__chip"
              :class="{ 'catalog-browser__chip--active': !filters.category_id }"
              data-test="catalog-leaf-category-all"
              @click="selectLeafCategory('')"
            >
              {{ t('app.public.catalog.filters.categoryAny') }}
            </button>
            <button
              v-for="category in selectedRootCategory.children ?? []"
              :key="category.id"
              type="button"
              class="catalog-browser__chip"
              :class="{ 'catalog-browser__chip--active': filters.category_id === category.id }"
              :data-test="`catalog-leaf-category-${category.id}`"
              @click="selectLeafCategory(category.id)"
            >
              {{ category.name }}
            </button>
          </div>
        </div>
      </div>

      <div class="catalog-filters" data-test="catalog-filters">
        <UiInput
          v-model="filters.search"
          preset="search"
          :label="t('app.public.catalog.filters.searchLabel')"
          :placeholder="t('app.public.catalog.filters.searchPlaceholder')"
        />

        <UiSelect
          v-model="filters.root_category_id"
          :label="t('app.public.catalog.filters.rootCategoryLabel')"
          :options="rootCategoryOptions"
          :placeholder="t('app.public.catalog.filters.rootCategoryPlaceholder')"
          :disabled="categoriesPending"
          :searchable="false"
        />

        <UiSelect
          v-model="filters.category_id"
          :label="t('app.public.catalog.filters.categoryLabel')"
          :options="leafCategoryOptions"
          :placeholder="t('app.public.catalog.filters.categoryPlaceholder')"
          :disabled="categoriesPending || !filters.root_category_id"
          :searchable="false"
        />

        <UiSelect
          v-model="filters.is_featured"
          :label="t('app.public.catalog.filters.featuredLabel')"
          :options="featuredOptions"
          :placeholder="t('app.public.catalog.filters.featuredPlaceholder')"
          :searchable="false"
        />

        <UiSelect
          v-model="filters.sort_dir"
          :label="t('app.public.catalog.filters.sortLabel')"
          :options="sortOptions"
          :placeholder="t('app.public.catalog.filters.sortPlaceholder')"
          :searchable="false"
        />

        <div class="catalog-filters__actions">
          <button
            type="button"
            class="catalog-filters__reset"
            :disabled="!hasActiveFilters"
            data-test="catalog-filters-reset"
            @click="resetFilters"
          >
            {{ t('app.public.catalog.filters.reset') }}
          </button>
        </div>
      </div>

      <PublicCardGridSkeleton
        v-if="pageState === 'loading'"
        data-test="catalog-activities-loading"
      />
      <PublicStateMessage
        v-else-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.emptyTitle')"
        :description="t('app.public.catalog.emptyDescription')"
        data-test="catalog-activities-empty"
      />
      <PublicStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.public.catalog.errorTitle')"
        :description="t('app.public.catalog.errorDescription')"
        data-test="catalog-activities-error"
      />
      <template v-else>
        <PublicCardGrid
          :items="activityCards"
          show-favorite-button
          data-test="catalog-activities-grid"
          @toggle-favorite="toggleFavorite"
        />
        <div
          v-if="activitiesLoadingMore"
          class="catalog-loading-more"
          data-test="catalog-activities-loading-more"
        >
          {{ t('app.public.catalog.loadingMore') }}
        </div>
        <div
          v-if="hasMoreActivities"
          ref="activitiesSentinel"
          class="catalog-sentinel"
          data-test="catalog-activities-sentinel"
          aria-hidden="true"
        />
      </template>
    </PublicSection>
  </div>
</template>

<script setup lang="ts">
import type { SortDirection } from '~/composables/useAdminCrudCommon';
import { useDebouncedSearch } from '~/composables/useAsyncSelectOptions';
import { usePublicPreviewState } from '~/composables/layout/usePublicPreviewState';
import { usePublicCategories } from '~/composables/usePublicCategories';
import { buildPublicActivityPath, usePublicActivities } from '~/composables/usePublicActivities';
import { useFavorites } from '~/composables/useFavorites';
import { usePublicPageSeo } from '~/composables/seo/usePublicPageSeo';
import { buildBreadcrumbListSchema } from '~/composables/schema/public-schema-contract';
import { usePublicSchemaNode } from '~/composables/schema/usePublicSchemaRegistry';
import PublicCardGridSkeleton from '~/components/public/PublicCardGridSkeleton/PublicCardGridSkeleton.vue';
import PublicCardGrid from '~/components/public/PublicCardGrid/PublicCardGrid.vue';
import PublicSection from '~/components/public/PublicSection/PublicSection.vue';
import PublicStateMessage from '~/components/public/PublicStateMessage/PublicStateMessage.vue';
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import PageHero from '~/components/ui/PageHero/PageHero.vue';
import UiFilterBarSkeleton from '~/components/ui/Skeleton/UiFilterBarSkeleton.vue';

definePageMeta({
  layout: 'default',
});

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const config = useRuntimeConfig();
const previewState = usePublicPreviewState();
const publicActivitiesApi = usePublicActivities();
const publicCategoriesApi = usePublicCategories();
const { isFavorite, toggleFavorite } = useFavorites();

const activities = ref<Awaited<ReturnType<typeof publicActivitiesApi.feed>>['items']>([]);
const activitiesInitialPending = ref(true);
const activitiesLoadingMore = ref(false);
const activitiesError = ref<unknown>(null);
const activitiesNextCursor = ref<string | null>(null);
const activitiesSentinel = ref<HTMLElement | null>(null);
let activitiesObserver: IntersectionObserver | null = null;

const categoriesTree = ref<Awaited<ReturnType<typeof publicCategoriesApi.tree>>>([]);
const categoriesPending = ref(false);
const categoriesError = ref('');
const isHydratingFilters = ref(true);

const filters = reactive<{
  search: string;
  root_category_id: string;
  category_id: string;
  organization_id: string;
  is_featured: '' | 'true';
  sort_dir: SortDirection;
}>({
  search: '',
  root_category_id: '',
  category_id: '',
  organization_id: '',
  is_featured: '',
  sort_dir: 'desc',
});

const seo = usePublicPageSeo({
  h1: computed(() => t('app.public.catalog.heroTitle')),
  title: computed(() => t('app.public.catalog.seoTitle')),
  description: computed(() => t('app.public.catalog.heroDescription')),
});
const heroTitle = seo.h1;
const heroDescription = seo.description;

const getSingleQueryValue = (value: unknown): string => {
  if (Array.isArray(value)) {
    return typeof value[0] === 'string' ? value[0] : '';
  }

  return typeof value === 'string' ? value : '';
};

const normalizeSortDir = (value: string): SortDirection => (value === 'asc' ? 'asc' : 'desc');
const normalizeFeaturedValue = (value: string): '' | 'true' => (value === 'true' ? 'true' : '');

const hydrateFiltersFromRoute = () => {
  filters.search =
    getSingleQueryValue(route.query.q).trim() || getSingleQueryValue(route.query.search).trim();
  filters.root_category_id = getSingleQueryValue(route.query.root_category_id).trim();
  filters.category_id = getSingleQueryValue(route.query.category_id).trim();
  filters.organization_id = getSingleQueryValue(route.query.organization_id).trim();
  filters.is_featured = normalizeFeaturedValue(getSingleQueryValue(route.query.is_featured).trim());
  filters.sort_dir = normalizeSortDir(getSingleQueryValue(route.query.sort_dir).trim());
};

hydrateFiltersFromRoute();

const rootCategoryOptions = computed(() => [
  {
    value: '',
    label: t('app.public.catalog.filters.rootCategoryAny'),
  },
  ...categoriesTree.value.map((category) => ({
    value: category.id,
    label: category.name,
  })),
]);

const selectedRootCategory = computed(
  () => categoriesTree.value.find((category) => category.id === filters.root_category_id) ?? null
);

const leafCategoryOptions = computed(() => [
  {
    value: '',
    label: t('app.public.catalog.filters.categoryAny'),
  },
  ...(selectedRootCategory.value?.children ?? []).map((category) => ({
    value: category.id,
    label: category.name,
  })),
]);

const featuredOptions = computed(() => [
  {
    value: '',
    label: t('app.public.catalog.filters.featuredAll'),
  },
  {
    value: 'true',
    label: t('app.public.catalog.filters.featuredOnly'),
  },
]);

const sortOptions = computed(() => [
  {
    value: 'desc',
    label: t('app.public.catalog.filters.sortNewest'),
  },
  {
    value: 'asc',
    label: t('app.public.catalog.filters.sortOldest'),
  },
]);

const hasActiveFilters = computed(() => {
  return Boolean(
    filters.search.trim() ||
    filters.organization_id ||
    filters.category_id ||
    filters.is_featured ||
    filters.sort_dir !== 'desc'
  );
});

const activitiesState = computed<'ready' | 'empty' | 'error'>(() => {
  if (activitiesError.value) {
    return 'error';
  }

  if (!activities.value.length) {
    return 'empty';
  }

  return 'ready';
});
const pageState = computed<'loading' | 'ready' | 'empty' | 'error'>(() => {
  if (previewState.value === 'loading') {
    return 'loading';
  }

  if (previewState.value === 'error') {
    return 'error';
  }

  if (previewState.value === 'empty') {
    return 'empty';
  }

  if (activitiesInitialPending.value) {
    return 'loading';
  }

  return activitiesState.value;
});
const hasMoreActivities = computed(() => Boolean(activitiesNextCursor.value));

const activityCards = computed(() =>
  activities.value.map((activity) => ({
    to: buildPublicActivityPath(activity),
    title: activity.name,
    description: activity.short_description,
    imageUrl: activity.cover?.url || null,
    imageAlt: activity.name,
    eyebrow: [activity.primary_category?.parent?.name, activity.primary_category?.name]
      .filter(Boolean)
      .join(' / '),
    price:
      activity.price_from != null
        ? `${activity.price_from} ${activity.currency || ''}`.trim()
        : undefined,
    meta: [activity.organization?.name, activity.location?.city?.name].filter(Boolean).join(' · '),
    dataTest: `catalog-activity-${activity.id}`,
    favoriteKey: activity.public_key,
    isFavorite: isFavorite(activity.public_key),
  }))
);

const activeFeedFilters = computed(() => ({
  search: filters.search.trim(),
  root_category_id: filters.root_category_id || undefined,
  category_id: filters.category_id || undefined,
  organization_id: filters.organization_id || undefined,
  is_featured: filters.is_featured === 'true' ? true : '',
  sort_by: 'created_at' as const,
  sort_dir: filters.sort_dir,
}));

const syncRootSelectionFromLeaf = () => {
  if (!filters.category_id) {
    return;
  }

  const matchedRoot = categoriesTree.value.find((rootCategory) =>
    (rootCategory.children ?? []).some((child) => child.id === filters.category_id)
  );

  filters.root_category_id = matchedRoot?.id ?? '';
};

const {
  data: initialCatalogData,
  pending: initialCatalogPending,
  error: initialCatalogError,
} = await useAsyncData(
  () =>
    `public-catalog-initial:${JSON.stringify({
      search: filters.search.trim(),
      root_category_id: filters.root_category_id || '',
      category_id: filters.category_id || '',
      organization_id: filters.organization_id || '',
      is_featured: filters.is_featured || '',
      sort_dir: filters.sort_dir,
    })}`,
  async () => {
    if (previewState.value !== 'ready') {
      return {
        categories: [],
        feedItems: [],
        nextCursor: null,
      };
    }

    const [initialCategoriesData, initialActivitiesData] = await Promise.all([
      publicCategoriesApi.tree(true),
      publicActivitiesApi.feed({
        limit: 12,
        cursor: null,
        ...activeFeedFilters.value,
      }),
    ]);

    return {
      categories: initialCategoriesData,
      feedItems: initialActivitiesData.items,
      nextCursor: initialActivitiesData.next_cursor,
    };
  },
  {
    server: previewState.value === 'ready',
    lazy: false,
    default: () => ({
      categories: [],
      feedItems: [],
      nextCursor: null,
    }),
  }
);

if (previewState.value === 'ready') {
  categoriesTree.value = initialCatalogData.value?.categories ?? [];
  syncRootSelectionFromLeaf();
  activities.value = initialCatalogData.value?.feedItems ?? [];
  activitiesNextCursor.value = initialCatalogData.value?.nextCursor ?? null;
  categoriesPending.value = initialCatalogPending.value;
  activitiesInitialPending.value = initialCatalogPending.value;
  categoriesError.value = initialCatalogError.value
    ? t('app.public.catalog.filters.loadError')
    : '';
  activitiesError.value = initialCatalogError.value;
} else {
  activitiesInitialPending.value = false;
  categoriesPending.value = false;
  isHydratingFilters.value = false;
}

const disconnectActivitiesObserver = () => {
  activitiesObserver?.disconnect();
  activitiesObserver = null;
};

const loadActivities = async (options: { append?: boolean } = {}) => {
  const append = options.append === true;

  if (append) {
    if (
      !activitiesNextCursor.value ||
      activitiesLoadingMore.value ||
      activitiesInitialPending.value
    ) {
      return;
    }
  } else {
    activitiesInitialPending.value = true;
    activitiesError.value = null;
  }

  if (append) {
    activitiesLoadingMore.value = true;
  }

  try {
    const response = await publicActivitiesApi.feed({
      limit: 12,
      cursor: append ? activitiesNextCursor.value : null,
      ...activeFeedFilters.value,
    });

    activities.value = append ? [...activities.value, ...response.items] : response.items;
    activitiesNextCursor.value = response.next_cursor;
  } catch (error) {
    if (!append) {
      activities.value = [];
      activitiesError.value = error;
      activitiesNextCursor.value = null;
    }
  } finally {
    if (append) {
      activitiesLoadingMore.value = false;
    } else {
      activitiesInitialPending.value = false;
    }
  }
};

const setupActivitiesObserver = () => {
  if (!import.meta.client || !activitiesSentinel.value) {
    return;
  }

  disconnectActivitiesObserver();

  activitiesObserver = new IntersectionObserver(
    (entries) => {
      const [entry] = entries;

      if (entry?.isIntersecting) {
        void loadActivities({ append: true });
      }
    },
    {
      rootMargin: '1400px 0px',
      threshold: 0.01,
    }
  );

  activitiesObserver.observe(activitiesSentinel.value);
};

isHydratingFilters.value = false;

const syncCatalogQuery = async () => {
  const nextQuery: Record<string, string> = {};

  if (filters.search.trim()) {
    nextQuery.q = filters.search.trim();
  }

  if (filters.root_category_id) {
    nextQuery.root_category_id = filters.root_category_id;
  }

  if (filters.category_id) {
    nextQuery.category_id = filters.category_id;
  }

  if (filters.organization_id) {
    nextQuery.organization_id = filters.organization_id;
  }

  if (filters.is_featured) {
    nextQuery.is_featured = filters.is_featured;
  }

  if (filters.sort_dir !== 'desc') {
    nextQuery.sort_dir = filters.sort_dir;
  }

  await router.replace({ query: nextQuery });
};

const refreshActivities = async () => {
  disconnectActivitiesObserver();
  activities.value = [];
  activitiesNextCursor.value = null;
  activitiesError.value = null;

  await syncCatalogQuery();
  await loadActivities();
  await nextTick();
  setupActivitiesObserver();
};

const resetFilters = async () => {
  filters.search = '';
  filters.root_category_id = '';
  filters.category_id = '';
  filters.organization_id = '';
  filters.is_featured = '';
  filters.sort_dir = 'desc';

  await refreshActivities();
};

const selectRootCategory = async (rootCategoryId: string) => {
  if (filters.root_category_id === rootCategoryId) {
    return;
  }

  filters.root_category_id = rootCategoryId;
  filters.category_id = '';
  await refreshActivities();
};

const selectLeafCategory = async (categoryId: string) => {
  if (filters.category_id === categoryId) {
    return;
  }

  filters.category_id = categoryId;
  await refreshActivities();
};

const debouncedSearch = useDebouncedSearch(350);

watch(
  () => filters.search,
  () => {
    if (isHydratingFilters.value) {
      return;
    }

    debouncedSearch.schedule(() => {
      void refreshActivities();
    });
  }
);

watch(
  () => [filters.category_id, filters.is_featured, filters.sort_dir],
  () => {
    if (isHydratingFilters.value) {
      return;
    }

    void refreshActivities();
  }
);

watch(
  () => filters.root_category_id,
  (nextRootId, previousRootId) => {
    if (nextRootId === previousRootId) {
      return;
    }

    const nextRoot = categoriesTree.value.find((category) => category.id === nextRootId);
    const leafStillAllowed = (nextRoot?.children ?? []).some(
      (child) => child.id === filters.category_id
    );

    if (!leafStillAllowed && filters.category_id) {
      filters.category_id = '';
    }
  }
);

const siteUrl = config.public.siteUrl;
const pageSchemaNode = computed(() =>
  buildBreadcrumbListSchema(siteUrl, [
    { name: t('app.public.catalog.breadcrumbs.home'), path: '/' },
    { name: t('app.public.catalog.breadcrumbs.current'), path: '/catalog' },
  ])
);
const sectionSchemaNode = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'ItemList',
  name: t('app.public.catalog.sectionTitle'),
  itemListElement: activityCards.value.map((item, index) => ({
    '@type': 'ListItem',
    position: index + 1,
    name: item.title,
    url: `${siteUrl}${item.to}`,
  })),
}));

usePublicSchemaNode('page:catalog-index', pageSchemaNode);
usePublicSchemaNode('section:catalog-categories', sectionSchemaNode);

useHead({
  title: computed(() => `${t('app.public.catalog.seoTitle')} | Marketplace`),
});

onMounted(async () => {
  if (previewState.value !== 'ready') {
    return;
  }

  categoriesPending.value = false;
  activitiesInitialPending.value = false;
  await nextTick();
  setupActivitiesObserver();
});

watch(hasMoreActivities, () => {
  nextTick(() => {
    setupActivitiesObserver();
  });
});

onBeforeUnmount(() => {
  disconnectActivitiesObserver();
  debouncedSearch.clear();
});
</script>

<style scoped lang="scss">
.catalog-page__section {
  max-width: 88rem;
  padding-inline: 1.25rem;
}

.catalog-filters {
  display: grid;
  gap: 1rem;
  margin-bottom: 1.25rem;
  padding: 1rem;
  border: 1px solid color-mix(in srgb, var(--border) 88%, transparent);
  border-radius: 1.5rem;
  background: color-mix(in srgb, var(--surface-elevated) 92%, transparent);
  backdrop-filter: blur(10px);
}

.catalog-browser {
  display: grid;
  gap: 1rem;
  margin-bottom: 1rem;
  padding: 1rem;
  border: 1px solid color-mix(in srgb, var(--border) 88%, transparent);
  border-radius: 1.5rem;
  background: color-mix(in srgb, var(--surface-elevated) 92%, transparent);
}

.catalog-browser__group {
  display: grid;
  gap: 0.65rem;
}

.catalog-browser__label {
  color: var(--muted);
  font-size: 0.85rem;
  font-weight: 700;
}

.catalog-browser__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
}

.catalog-browser__chip {
  border: 1px solid color-mix(in srgb, var(--border) 88%, transparent);
  border-radius: 999px;
  background: var(--surface);
  color: var(--text);
  padding: 0.6rem 0.95rem;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease;
}

.catalog-browser__chip:hover,
.catalog-browser__chip:focus-visible {
  outline: none;
  border-color: color-mix(in srgb, var(--accent) 35%, var(--border));
  color: var(--accent);
  transform: translateY(-1px);
}

.catalog-browser__chip--active {
  border-color: color-mix(in srgb, var(--accent) 50%, var(--border));
  background: color-mix(in srgb, var(--accent) 14%, var(--surface));
  color: var(--accent);
}

.catalog-filters__actions {
  display: flex;
  align-items: end;
}

.catalog-filters__reset {
  min-height: 3rem;
  width: 100%;
  border: 1px solid color-mix(in srgb, var(--border) 88%, transparent);
  border-radius: 1rem;
  background: var(--surface-elevated);
  color: var(--text);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    background-color 0.2s ease;
}

.catalog-filters__reset:hover:enabled {
  border-color: color-mix(in srgb, var(--accent) 35%, var(--border));
  color: var(--accent);
}

.catalog-filters__reset:disabled {
  opacity: 0.5;
  cursor: default;
}

.catalog-filters__error {
  margin: 0 0 1rem;
  color: #ef4444;
}

.catalog-loading-more {
  margin-top: 1rem;
  color: var(--color-text-secondary, #7f8ca3);
  text-align: center;
}

.catalog-sentinel {
  width: 100%;
  height: 2px;
}

@media (min-width: 900px) {
  .catalog-filters {
    grid-template-columns: minmax(0, 1.35fr) repeat(4, minmax(0, 1fr)) 11rem;
    align-items: end;
  }
}

@media (min-width: 960px) {
  .catalog-page__section {
    padding-inline: 1.5rem;
  }
}
</style>
