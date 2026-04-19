import {
  buildPublicLeafCategoryPath,
  buildPublicRootCategoryPath,
  usePublicCategories,
} from '~/composables/usePublicCategories';

type HeaderLink = {
  label: string;
  to: string;
};

type CatalogGroup = {
  id: string;
  title: string;
  subcategories: HeaderLink[];
};

export const usePublicHeaderConfig = () => {
  const { t } = useI18n();
  const publicCategoriesApi = usePublicCategories();
  const { favoriteCount } = useFavorites();
  const { selectedCityLabel } = usePublicCity();
  const {
    data: categoriesTreeData,
    pending: categoriesPending,
    error: categoriesError,
  } = useAsyncData('public-header-categories-tree', () => publicCategoriesApi.tree(), {
    default: () => [],
    lazy: false,
  });

  const categoriesTree = computed(() => categoriesTreeData.value ?? []);

  const quickActions = computed<HeaderLink[]>(() => [
    {
      label:
        favoriteCount.value > 0
          ? t('app.layout.header.quickActions.favoritesCount', { count: favoriteCount.value })
          : t('app.layout.header.quickActions.favorites'),
      to: '/favorites',
    },
  ]);

  const sectionLinks = computed<HeaderLink[]>(() => [
    { label: t('app.layout.header.sections.home'), to: '/' },
    ...(categoriesTree.value || []).map((category) => ({
      label: category.name,
      to: buildPublicRootCategoryPath(category),
    })),
  ]);

  const catalogGroups = computed<CatalogGroup[]>(() =>
    (categoriesTree.value || []).map((category) => ({
      id: category.id,
      title: category.name,
      subcategories: (category.children || []).map((child) => ({
        label: child.name,
        to: buildPublicLeafCategoryPath(category, child),
      })),
    }))
  );

  const regionText = computed(() => selectedCityLabel.value || t('app.layout.header.region'));
  return {
    categoriesPending,
    categoriesError: computed(() =>
      categoriesError.value ? t('app.layout.header.categoriesLoadError') : ''
    ),
    quickActions,
    sectionLinks,
    catalogGroups,
    regionText,
  };
};
