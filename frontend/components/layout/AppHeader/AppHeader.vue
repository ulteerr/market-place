<template>
  <header ref="headerRoot" :class="styles.header" data-test="public-header">
    <div :class="styles.container">
      <div :class="styles.topRow">
        <NuxtLink to="/" :class="styles.logo" data-test="public-header-logo">{{
          t('app.layout.header.logo')
        }}</NuxtLink>

        <button
          ref="catalogToggleButton"
          type="button"
          :class="[styles.catalogButton, isCatalogOpen ? styles.catalogButtonActive : '']"
          data-test="public-header-catalog-toggle"
          :aria-expanded="isCatalogOpen ? 'true' : 'false'"
          aria-haspopup="menu"
          :aria-label="t('app.layout.header.catalogAria')"
          aria-controls="public-catalog-menu"
          @click="toggleCatalog"
        >
          <span :class="styles.catalogButtonIcon" aria-hidden="true">
            <svg
              v-if="isCatalogOpen"
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
            >
              <path
                fill="currentColor"
                d="M5.44 5.44a1.5 1.5 0 0 1 2.12 0L12 9.878l4.44-4.44a1.5 1.5 0 0 1 2.12 2.122L14.122 12l4.44 4.44a1.5 1.5 0 0 1-2.122 2.12L12 14.122l-4.44 4.44a1.5 1.5 0 0 1-2.12-2.122L9.878 12l-4.44-4.44a1.5 1.5 0 0 1 0-2.12"
              />
            </svg>
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
            >
              <path
                fill="currentColor"
                d="M4 7.556C4 4.628 4.628 4 7.556 4s3.555.628 3.555 3.556-.627 3.555-3.555 3.555S4 10.484 4 7.556m0 8.888c0-2.928.628-3.555 3.556-3.555s3.555.627 3.555 3.555S10.484 20 7.556 20 4 19.372 4 16.444M16.444 4c-2.928 0-3.555.628-3.555 3.556s.627 3.555 3.555 3.555S20 10.484 20 7.556 19.372 4 16.444 4m-3.555 12.444c0-2.928.627-3.555 3.555-3.555S20 13.516 20 16.444 19.372 20 16.444 20s-3.555-.628-3.555-3.556"
              />
            </svg>
          </span>
          <span>{{ t('app.layout.header.catalogButton') }}</span>
        </button>

        <form
          :class="styles.searchForm"
          role="search"
          :aria-label="t('app.layout.header.searchAria')"
        >
          <input
            :class="styles.searchInput"
            type="search"
            name="query"
            :placeholder="t('app.layout.header.searchPlaceholder')"
            data-test="public-header-search"
          />
        </form>

        <nav :class="styles.quickActions" :aria-label="t('app.layout.header.quickActionsAria')">
          <NuxtLink
            v-for="action in quickActions"
            :key="`${action.to}-${action.label}`"
            :to="action.to"
            :class="styles.quickActionLink"
          >
            {{ action.label }}
          </NuxtLink>

          <button
            type="button"
            :class="styles.themeButton"
            :aria-label="t('app.layout.header.themeToggleAria')"
            @click="toggleTheme"
          >
            {{
              resolvedIsDark ? t('app.layout.header.themeLight') : t('app.layout.header.themeDark')
            }}
          </button>
        </nav>

        <button
          type="button"
          :class="styles.mobileMenuButton"
          data-test="public-header-mobile-menu-toggle"
          :aria-expanded="isMobileMenuOpen ? 'true' : 'false'"
          :aria-label="t('app.layout.header.mobileMenuAria')"
          aria-controls="public-header-mobile-menu"
          @click="isMobileMenuOpen = !isMobileMenuOpen"
        >
          {{ t('app.layout.header.mobileMenuButton') }}
        </button>
      </div>

      <div :class="styles.bottomRow" data-test="public-header-bottom-row">
        <nav :class="styles.sectionsNav" :aria-label="t('app.layout.header.sectionsAria')">
          <NuxtLink
            v-for="link in sectionLinks"
            :key="`${link.to}-${link.label}`"
            :to="link.to"
            :class="styles.sectionLink"
          >
            {{ link.label }}
          </NuxtLink>
        </nav>

        <div :class="styles.serviceZone">{{ serviceZoneText }}</div>
      </div>
    </div>

    <div
      v-if="isCatalogOpen"
      id="public-catalog-menu"
      :class="styles.catalogMenu"
      data-test="public-header-catalog-menu"
    >
      <div :class="styles.catalogMenuInner">
        <div
          :class="styles.catalogCategories"
          role="menu"
          :aria-label="t('app.layout.header.catalogCategoriesAria')"
        >
          <button
            v-for="group in catalogGroups"
            :key="group.id"
            type="button"
            :class="[
              styles.catalogCategory,
              selectedCatalogGroupId === group.id ? styles.catalogCategoryActive : '',
            ]"
            role="menuitemradio"
            :aria-checked="selectedCatalogGroupId === group.id ? 'true' : 'false'"
            data-catalog-category="true"
            @click="selectCatalogGroup(group.id)"
            @keydown="onCatalogCategoryKeydown($event, group.id)"
          >
            {{ group.title }}
          </button>
        </div>

        <div :class="styles.catalogSubcategories">
          <NuxtLink
            v-for="subcategory in selectedCatalogGroup?.subcategories ?? []"
            :key="subcategory.to"
            :to="subcategory.to"
            :class="styles.catalogSubcategoryLink"
          >
            {{ subcategory.label }}
          </NuxtLink>
        </div>
      </div>
    </div>

    <div
      v-if="isMobileMenuOpen"
      id="public-header-mobile-menu"
      :class="styles.mobileMenuPanel"
      data-test="public-header-mobile-menu"
    >
      <form
        :class="styles.mobileSearchForm"
        role="search"
        :aria-label="t('app.layout.header.searchAria')"
      >
        <input
          :class="styles.searchInput"
          type="search"
          name="mobile-query"
          :placeholder="t('app.layout.header.mobileSearchPlaceholder')"
          data-test="public-header-mobile-search"
        />
      </form>

      <nav
        :class="styles.mobileSectionsNav"
        :aria-label="t('app.layout.header.mobileSectionsAria')"
      >
        <NuxtLink
          v-for="link in sectionLinks"
          :key="`mobile-${link.to}-${link.label}`"
          :to="link.to"
          :class="styles.mobileSectionLink"
        >
          {{ link.label }}
        </NuxtLink>
      </nav>
    </div>
  </header>
</template>

<script setup lang="ts">
import styles from './AppHeader.module.scss';
import { usePublicHeaderConfig } from '~/composables/layout/usePublicHeaderConfig';

const { t } = useI18n();
const { isDark, toggleTheme } = useUserSettings();
const isThemeUiMounted = ref(false);
const resolvedIsDark = computed(() => (isThemeUiMounted.value ? isDark.value : false));
const route = useRoute();
const { quickActions, sectionLinks, catalogGroups, serviceZoneText } = usePublicHeaderConfig();
const headerRoot = ref<HTMLElement | null>(null);
const catalogToggleButton = ref<HTMLButtonElement | null>(null);

const selectedCatalogGroupId = ref(catalogGroups.value[0]?.id ?? '');
const selectedCatalogGroup = computed(
  () =>
    catalogGroups.value.find((group) => group.id === selectedCatalogGroupId.value) ??
    catalogGroups.value[0]
);
const isCatalogOpen = ref(false);
const isMobileMenuOpen = ref(false);

watch(
  catalogGroups,
  (groups) => {
    if (!groups.some((group) => group.id === selectedCatalogGroupId.value)) {
      selectedCatalogGroupId.value = groups[0]?.id ?? '';
    }
  },
  { immediate: true }
);

const closeMenus = () => {
  isCatalogOpen.value = false;
  isMobileMenuOpen.value = false;
};

const focusCatalogCategoryByIndex = (index: number) => {
  const buttons = headerRoot.value?.querySelectorAll<HTMLButtonElement>(
    '[data-catalog-category="true"]'
  );
  if (!buttons?.length) {
    return;
  }

  const normalized = Math.max(0, Math.min(index, buttons.length - 1));
  buttons[normalized]?.focus();
};

const focusSelectedCatalogCategory = async () => {
  await nextTick();

  const index = catalogGroups.value.findIndex((group) => group.id === selectedCatalogGroupId.value);
  focusCatalogCategoryByIndex(index >= 0 ? index : 0);
};

const toggleCatalog = () => {
  if (!isCatalogOpen.value) {
    isMobileMenuOpen.value = false;
  }
  isCatalogOpen.value = !isCatalogOpen.value;
  if (isCatalogOpen.value) {
    void focusSelectedCatalogCategory();
  }
};

const selectCatalogGroup = (groupId: string) => {
  selectedCatalogGroupId.value = groupId;
};

const onCatalogCategoryKeydown = (event: KeyboardEvent, groupId: string) => {
  const currentIndex = catalogGroups.value.findIndex((group) => group.id === groupId);

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault();
      focusCatalogCategoryByIndex(currentIndex + 1);
      break;
    case 'ArrowUp':
      event.preventDefault();
      focusCatalogCategoryByIndex(currentIndex - 1);
      break;
    case 'Home':
      event.preventDefault();
      focusCatalogCategoryByIndex(0);
      break;
    case 'End':
      event.preventDefault();
      focusCatalogCategoryByIndex(catalogGroups.value.length - 1);
      break;
    case 'Escape':
      event.preventDefault();
      isCatalogOpen.value = false;
      catalogToggleButton.value?.focus();
      break;
    default:
      break;
  }
};

const onWindowKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    closeMenus();
  }
};

const onDocumentPointerDown = (event: PointerEvent) => {
  const target = event.target as Node | null;

  if (target && headerRoot.value?.contains(target)) {
    return;
  }

  closeMenus();
};

watch(
  () => route.fullPath,
  () => {
    closeMenus();
  }
);

onMounted(() => {
  isThemeUiMounted.value = true;
  window.addEventListener('keydown', onWindowKeydown);
  document.addEventListener('pointerdown', onDocumentPointerDown);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onWindowKeydown);
  document.removeEventListener('pointerdown', onDocumentPointerDown);
});
</script>
