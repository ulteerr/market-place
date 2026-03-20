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
  const categoriesTree = ref<Awaited<ReturnType<typeof publicCategoriesApi.tree>>>([]);
  const categoriesPending = ref(true);
  const categoriesError = ref('');

  const quickActions = computed<HeaderLink[]>(() => [
    { label: t('app.layout.header.quickActions.orders'), to: '/login' },
    { label: t('app.layout.header.quickActions.favorites'), to: '/login' },
    { label: t('app.layout.header.quickActions.admin'), to: '/admin' },
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

  const regionText = computed(() => t('app.layout.header.region'));
  const serviceStatusText = computed(() => t('app.layout.header.serviceStatus'));

  onMounted(async () => {
    categoriesPending.value = true;
    categoriesError.value = '';

    try {
      categoriesTree.value = await publicCategoriesApi.tree();
    } catch {
      categoriesTree.value = [];
      categoriesError.value = t('app.layout.header.categoriesLoadError');
    } finally {
      categoriesPending.value = false;
    }
  });

  return {
    categoriesPending,
    categoriesError,
    quickActions,
    sectionLinks,
    catalogGroups,
    regionText,
    serviceStatusText,
  };
};
