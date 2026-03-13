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

  const quickActions = computed<HeaderLink[]>(() => [
    { label: t('app.layout.header.quickActions.orders'), to: '/login' },
    { label: t('app.layout.header.quickActions.favorites'), to: '/login' },
    { label: t('app.layout.header.quickActions.admin'), to: '/admin' },
  ]);

  const sectionLinks = computed<HeaderLink[]>(() => [
    { label: t('app.layout.header.sections.home'), to: '/' },
    { label: t('app.layout.header.sections.football'), to: '/' },
    { label: t('app.layout.header.sections.floorball'), to: '/' },
    { label: t('app.layout.header.sections.volleyball'), to: '/' },
    { label: t('app.layout.header.sections.drawing'), to: '/' },
  ]);

  const catalogGroups = computed<CatalogGroup[]>(() => [
    {
      id: 'sports',
      title: t('app.layout.header.catalog.sports'),
      subcategories: [
        { label: t('app.layout.header.sections.football'), to: '/' },
        { label: t('app.layout.header.sections.floorball'), to: '/' },
        { label: t('app.layout.header.sections.volleyball'), to: '/' },
        { label: t('app.layout.header.catalog.basketball'), to: '/' },
      ],
    },
    {
      id: 'creative',
      title: t('app.layout.header.catalog.creative'),
      subcategories: [
        { label: t('app.layout.header.sections.drawing'), to: '/' },
        { label: t('app.layout.header.catalog.sculpture'), to: '/' },
        { label: t('app.layout.header.catalog.theater'), to: '/' },
        { label: t('app.layout.header.catalog.music'), to: '/' },
      ],
    },
    {
      id: 'development',
      title: t('app.layout.header.catalog.development'),
      subcategories: [
        { label: t('app.layout.header.catalog.chess'), to: '/' },
        { label: t('app.layout.header.catalog.robotics'), to: '/' },
        { label: t('app.layout.header.catalog.schoolPrep'), to: '/' },
        { label: t('app.layout.header.catalog.english'), to: '/' },
      ],
    },
  ]);

  const regionText = computed(() => t('app.layout.header.region'));
  const serviceStatusText = computed(() => t('app.layout.header.serviceStatus'));

  return {
    quickActions,
    sectionLinks,
    catalogGroups,
    regionText,
    serviceStatusText,
  };
};
