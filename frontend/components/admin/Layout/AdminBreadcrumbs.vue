<template>
  <nav
    v-if="breadcrumbs.length"
    class="admin-breadcrumbs"
    :aria-label="t('admin.layout.breadcrumbsAria')"
  >
    <ol class="admin-breadcrumbs-list">
      <li v-for="(crumb, index) in breadcrumbs" :key="crumb.key" class="admin-breadcrumbs-item">
        <span v-if="index > 0" class="admin-breadcrumbs-separator" aria-hidden="true">/</span>
        <NuxtLink v-if="crumb.to && !crumb.current" :to="crumb.to" class="admin-breadcrumbs-link">
          {{ crumb.label }}
        </NuxtLink>
        <span
          v-else
          class="admin-breadcrumbs-current"
          :aria-current="crumb.current ? 'page' : undefined"
        >
          {{ crumb.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<script setup lang="ts">
import {
  adminDashboardItemDefinition,
  adminNavigationSectionDefinitions,
  type AdminNavigationItemDefinition,
} from '~/config/admin-navigation';

interface Breadcrumb {
  key: string;
  label: string;
  to?: string;
  current: boolean;
}

const { t } = useI18n();
const route = useRoute();

const formatSegmentLabel = (segment: string): string => {
  if (segment === 'new') {
    return t('common.create');
  }

  if (segment === 'edit') {
    return t('common.edit');
  }

  const decoded = decodeURIComponent(segment);

  return /^\d+$/.test(decoded) ? `#${decoded}` : decoded;
};

const normalizePath = (path: string): string => {
  if (!path || path === '/') {
    return '/';
  }

  return path.replace(/\/+$/, '');
};

const trimLeadingSlash = (value: string): string => value.replace(/^\/+/, '');

const findSectionForItem = (
  item: AdminNavigationItemDefinition
): { label: string; key: string } | null => {
  const section = adminNavigationSectionDefinitions.find((entry) =>
    entry.items.some((sectionItem) => sectionItem.key === item.key)
  );

  if (!section) {
    return null;
  }

  return {
    key: section.key,
    label: t(section.labelKey),
  };
};

const findNavigationItemByPath = (path: string): AdminNavigationItemDefinition | null => {
  const candidates = adminNavigationSectionDefinitions
    .flatMap((section) => section.items)
    .filter((item) => path === item.to || path.startsWith(`${item.to}/`));

  if (!candidates.length) {
    return null;
  }

  return candidates.sort((left, right) => right.to.length - left.to.length)[0] ?? null;
};

const breadcrumbs = computed<Breadcrumb[]>(() => {
  const currentPath = normalizePath(route.path);
  if (!currentPath.startsWith('/admin')) {
    return [];
  }

  const homeLabel = t(adminDashboardItemDefinition.labelKey);
  if (currentPath === '/admin') {
    return [
      {
        key: 'admin-home',
        label: homeLabel,
        current: true,
      },
    ];
  }

  const crumbs: Breadcrumb[] = [
    {
      key: 'admin-home',
      label: homeLabel,
      to: '/admin',
      current: false,
    },
  ];

  if (currentPath === '/admin/profile') {
    crumbs.push({
      key: 'profile',
      label: t('admin.userMenu.profile'),
      current: true,
    });

    return crumbs;
  }

  if (currentPath === '/admin/settings') {
    crumbs.push({
      key: 'settings',
      label: t('admin.userMenu.settings'),
      current: true,
    });

    return crumbs;
  }

  const navItem = findNavigationItemByPath(currentPath);
  if (!navItem) {
    const segments = trimLeadingSlash(currentPath).split('/').filter(Boolean).slice(1);

    let segmentPath = '/admin';
    segments.forEach((segment, index) => {
      segmentPath += `/${segment}`;
      crumbs.push({
        key: `fallback-${segmentPath}`,
        label: formatSegmentLabel(segment),
        to: index === segments.length - 1 ? undefined : segmentPath,
        current: index === segments.length - 1,
      });
    });

    return crumbs;
  }

  const section = findSectionForItem(navItem);
  if (section) {
    crumbs.push({
      key: `section-${section.key}`,
      label: section.label,
      current: false,
    });
  }

  const isListPage = currentPath === navItem.to;
  crumbs.push({
    key: `item-${navItem.key}`,
    label: t(navItem.labelKey),
    to: isListPage ? undefined : navItem.to,
    current: isListPage,
  });

  if (isListPage) {
    return crumbs;
  }

  const tail = trimLeadingSlash(currentPath.slice(navItem.to.length));
  if (!tail) {
    crumbs[crumbs.length - 1].current = true;
    crumbs[crumbs.length - 1].to = undefined;
    return crumbs;
  }

  const segments = tail.split('/').filter(Boolean);
  let segmentPath = navItem.to;

  segments.forEach((segment, index) => {
    segmentPath += `/${segment}`;
    const isLast = index === segments.length - 1;
    crumbs.push({
      key: `tail-${segmentPath}`,
      label: formatSegmentLabel(segment),
      to: isLast ? undefined : segmentPath,
      current: isLast,
    });
  });

  return crumbs;
});
</script>

<style lang="scss" scoped src="./AdminBreadcrumbs.scss"></style>
