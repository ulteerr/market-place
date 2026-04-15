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
          @submit.prevent="submitDesktopSearch()"
        >
          <div :class="styles.searchBox">
            <input
              ref="searchInput"
              v-model="searchQuery"
              :class="styles.searchInput"
              type="search"
              name="query"
              autocomplete="off"
              :placeholder="t('app.layout.header.searchPlaceholder')"
              data-test="public-header-search"
              :aria-expanded="isSearchDropdownOpen ? 'true' : 'false'"
              :aria-activedescendant="activeSearchOptionId"
              aria-autocomplete="list"
              aria-controls="public-header-search-dropdown"
              @focus="onSearchFocus"
              @keydown="onSearchInputKeydown"
            />

            <button
              v-if="searchQuery.length > 0"
              type="button"
              :class="styles.searchClearButton"
              :aria-label="t('app.layout.header.searchClear')"
              data-test="public-header-search-clear"
              @click="clearDesktopSearch"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  fill="currentColor"
                  d="M6.4 6.4a1 1 0 0 1 1.4 0L12 10.59l4.2-4.2a1 1 0 1 1 1.4 1.42L13.41 12l4.2 4.2a1 1 0 0 1-1.42 1.4L12 13.41l-4.2 4.2a1 1 0 0 1-1.4-1.42l4.19-4.19l-4.2-4.2a1 1 0 0 1 0-1.4"
                />
              </svg>
            </button>

            <div
              v-if="isSearchDropdownOpen"
              id="public-header-search-dropdown"
              :class="styles.searchDropdown"
              data-test="public-header-search-dropdown"
            >
              <div
                v-if="showRecentQueries"
                :class="styles.searchGroup"
                data-test="public-header-search-recent"
              >
                <div :class="styles.searchGroupTitle">
                  {{ t('app.layout.header.searchRecentTitle') }}
                </div>
                <button
                  v-for="item in recentSearchOptions"
                  :id="searchOptionId(item.index)"
                  :key="item.key"
                  type="button"
                  :class="[
                    styles.searchSuggestionButton,
                    item.index === activeSearchOptionIndex ? styles.searchOptionActive : '',
                  ]"
                  @mouseenter="activeSearchOptionIndex = item.index"
                  @click="applyQuerySuggestion(item.label)"
                >
                  <span
                    v-for="(part, partIndex) in getHighlightParts(item.label)"
                    :key="`${item.key}-part-${partIndex}`"
                    :class="part.match ? styles.searchHighlight : ''"
                  >
                    {{ part.value }}
                  </span>
                </button>
              </div>

              <div
                v-if="showQuerySuggestions"
                :class="styles.searchGroup"
                data-test="public-header-search-queries"
              >
                <div :class="styles.searchGroupTitle">
                  {{ t('app.layout.header.searchQueriesTitle') }}
                </div>
                <button
                  v-for="item in querySearchOptions"
                  :id="searchOptionId(item.index)"
                  :key="item.key"
                  type="button"
                  :class="[
                    styles.searchSuggestionButton,
                    item.index === activeSearchOptionIndex ? styles.searchOptionActive : '',
                  ]"
                  @mouseenter="activeSearchOptionIndex = item.index"
                  @click="applyQuerySuggestion(item.label)"
                >
                  <span
                    v-for="(part, partIndex) in getHighlightParts(item.label)"
                    :key="`${item.key}-part-${partIndex}`"
                    :class="part.match ? styles.searchHighlight : ''"
                  >
                    {{ part.value }}
                  </span>
                </button>
              </div>

              <div
                v-if="showEntitySuggestions"
                :class="styles.searchGroup"
                data-test="public-header-search-entities"
              >
                <div :class="styles.searchGroupTitle">
                  {{ t('app.layout.header.searchEntitiesTitle') }}
                </div>
                <NuxtLink
                  v-for="item in entitySearchOptions"
                  :id="searchOptionId(item.index)"
                  :key="item.key"
                  :to="item.entity.url"
                  :class="[
                    styles.searchEntityLink,
                    item.index === activeSearchOptionIndex ? styles.searchOptionActive : '',
                  ]"
                  @mouseenter="activeSearchOptionIndex = item.index"
                  @click="handleEntityClick(item.label)"
                >
                  <span :class="styles.searchEntityType">{{ item.entity.type }}</span>
                  <span :class="styles.searchEntityLabel">
                    <span
                      v-for="(part, partIndex) in getHighlightParts(item.label)"
                      :key="`${item.key}-part-${partIndex}`"
                      :class="part.match ? styles.searchHighlight : ''"
                    >
                      {{ part.value }}
                    </span>
                  </span>
                  <span v-if="item.entity.subtitle" :class="styles.searchEntityMeta">
                    {{ item.entity.subtitle }}
                  </span>
                </NuxtLink>
              </div>
            </div>
          </div>
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
        </nav>

        <button
          type="button"
          :class="[styles.themeButton, styles.themeButtonCompact]"
          data-test="public-header-theme-toggle"
          :title="themeToggleLabel"
          :aria-label="themeToggleLabel"
          @click="toggleTheme"
        >
          <svg
            v-if="!resolvedIsDark"
            :class="styles.themeIcon"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"
            />
          </svg>
          <svg
            v-else
            :class="styles.themeIcon"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"
            />
          </svg>
        </button>

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
        <nav
          v-if="sectionLinks.length"
          :class="styles.sectionsNav"
          :aria-label="t('app.layout.header.sectionsAria')"
        >
          <NuxtLink
            v-for="link in sectionLinks"
            :key="`${link.to}-${link.label}`"
            :to="link.to"
            :class="styles.sectionLink"
          >
            {{ link.label }}
          </NuxtLink>
        </nav>

        <div :class="styles.serviceTools">
          <div :class="styles.serviceZone">
            <span>{{ regionText }}</span>
            <span aria-hidden="true">•</span>
            <span>{{ serviceStatusText }}</span>
          </div>

          <div :class="styles.localeSelect" data-test="public-header-locale-select">
            <UiSelect
              class="public-header-locale-ui-select"
              id="public-header-locale"
              :model-value="locale"
              :options="localeSelectOptions"
              :searchable="false"
              :placeholder="publicLocalePlaceholder"
              @update:model-value="onLocaleChange"
            />
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="isCatalogOpen"
      id="public-catalog-menu"
      :class="styles.catalogMenu"
      data-test="public-header-catalog-menu"
    >
      <div
        v-if="categoriesPending"
        :class="styles.catalogMenuInner"
        data-test="public-header-catalog-loading"
      >
        <div :class="styles.catalogLoadingColumn">
          <UiFilterBarSkeleton :chips="4" />
        </div>
        <div :class="styles.catalogLoadingColumn">
          <UiFilterBarSkeleton :chips="6" />
        </div>
      </div>
      <PublicStateMessage
        v-else-if="categoriesError"
        :title="t('app.layout.header.categoriesErrorTitle')"
        :description="categoriesError"
        data-test="public-header-catalog-error"
      />
      <PublicStateMessage
        v-else-if="!catalogGroups.length"
        :title="t('app.layout.header.categoriesEmptyTitle')"
        :description="t('app.layout.header.categoriesEmptyDescription')"
        data-test="public-header-catalog-empty"
      />
      <div v-else :class="styles.catalogMenuInner">
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
        @submit.prevent="submitMobileSearch()"
      >
        <input
          v-model="mobileSearchQuery"
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

      <div :class="styles.mobileUtilityRow">
        <div :class="styles.localeSelect" data-test="public-header-mobile-locale-select">
          <UiSelect
            class="public-header-locale-ui-select"
            id="public-header-mobile-locale"
            :model-value="locale"
            :options="localeSelectOptions"
            :searchable="false"
            :placeholder="publicLocalePlaceholder"
            @update:model-value="onLocaleChange"
          />
        </div>

        <button
          type="button"
          :class="[styles.themeButton, styles.themeButtonCompact]"
          data-test="public-header-mobile-theme-toggle"
          :title="themeToggleLabel"
          :aria-label="themeToggleLabel"
          @click="toggleTheme"
        >
          <svg
            v-if="!resolvedIsDark"
            :class="styles.themeIcon"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"
            />
          </svg>
          <svg
            v-else
            :class="styles.themeIcon"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"
            />
          </svg>
        </button>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import styles from './AppHeader.module.scss';
import { usePublicHeaderConfig } from '~/composables/layout/usePublicHeaderConfig';
import {
  usePublicSearch,
  type PublicSearchSuggestEntity,
  type PublicSearchSuggestResponse,
} from '~/composables/usePublicSearch';
import PublicStateMessage from '~/components/public/PublicStateMessage/PublicStateMessage.vue';
import UiFilterBarSkeleton from '~/components/ui/Skeleton/UiFilterBarSkeleton.vue';
import { useDebouncedSearch } from '~/composables/useAsyncSelectOptions';

const { t, locale, setLocale } = useI18n();
const { isAuthenticated } = useAuth();
const { settings, isDark, toggleTheme, updateSettings } = useUserSettings();
const { localeSelectOptions, onLocaleChange } = useAdminLocaleSync({
  locale,
  setLocale,
  isAuthenticated,
  settings,
  updateSettings,
});
const isThemeUiMounted = ref(false);
const resolvedIsDark = computed(() => (isThemeUiMounted.value ? isDark.value : false));
const route = useRoute();
const router = useRouter();
const searchInput = ref<HTMLInputElement | null>(null);
const publicSearchApi = usePublicSearch();
const {
  categoriesError,
  quickActions,
  sectionLinks,
  catalogGroups,
  regionText,
  serviceStatusText,
} = usePublicHeaderConfig();
const headerRoot = ref<HTMLElement | null>(null);
const catalogToggleButton = ref<HTMLButtonElement | null>(null);
const publicLocalePlaceholder = computed(() => locale.value.toUpperCase());
const themeToggleLabel = computed(() =>
  resolvedIsDark.value ? t('app.layout.header.themeLight') : t('app.layout.header.themeDark')
);

const selectedCatalogGroupId = ref(catalogGroups.value[0]?.id ?? '');
const selectedCatalogGroup = computed(
  () =>
    catalogGroups.value.find((group) => group.id === selectedCatalogGroupId.value) ??
    catalogGroups.value[0]
);
const isCatalogOpen = ref(false);
const isMobileMenuOpen = ref(false);
const searchQuery = ref('');
const mobileSearchQuery = ref('');
const searchSuggestions = ref<PublicSearchSuggestResponse>({
  queries: [],
  entities: [],
  recent: [],
});
const isSearchFocused = ref(false);
const recentQueries = ref<string[]>([]);
const searchLoading = ref(false);
const searchDebounce = useDebouncedSearch(160);
const RECENT_SEARCHES_KEY = 'marketplace-public-search-recent';
let activeSuggestRequestId = 0;
const trimmedSearchQuery = computed(() => searchQuery.value.trim());
const showRecentQueries = computed(
  () => trimmedSearchQuery.value === '' && recentQueries.value.length > 0
);
const showQuerySuggestions = computed(
  () => trimmedSearchQuery.value.length >= 2 && searchSuggestions.value.queries.length > 0
);
const showEntitySuggestions = computed(
  () => trimmedSearchQuery.value.length >= 2 && searchSuggestions.value.entities.length > 0
);
const activeSearchOptionIndex = ref(-1);

type SearchDropdownOption =
  | {
      index: number;
      key: string;
      kind: 'recent' | 'query';
      label: string;
    }
  | {
      index: number;
      key: string;
      kind: 'entity';
      label: string;
      entity: PublicSearchSuggestEntity;
    };

const recentSearchOptions = computed<SearchDropdownOption[]>(() =>
  showRecentQueries.value
    ? recentQueries.value.map((item, index) => ({
        index,
        key: `recent-${item}`,
        kind: 'recent',
        label: item,
      }))
    : []
);

const querySearchOptions = computed<SearchDropdownOption[]>(() => {
  if (!showQuerySuggestions.value) {
    return [];
  }

  const startIndex = recentSearchOptions.value.length;

  return searchSuggestions.value.queries.map((item, index) => ({
    index: startIndex + index,
    key: `query-${item}`,
    kind: 'query',
    label: item,
  }));
});

const entitySearchOptions = computed<SearchDropdownOption[]>(() => {
  if (!showEntitySuggestions.value) {
    return [];
  }

  const startIndex = recentSearchOptions.value.length + querySearchOptions.value.length;

  return searchSuggestions.value.entities.map((entity, index) => ({
    index: startIndex + index,
    key: `${entity.type}-${entity.id}`,
    kind: 'entity',
    label: entity.label,
    entity,
  }));
});

const searchDropdownOptions = computed<SearchDropdownOption[]>(() => [
  ...recentSearchOptions.value,
  ...querySearchOptions.value,
  ...entitySearchOptions.value,
]);

const activeSearchOptionId = computed(() =>
  activeSearchOptionIndex.value >= 0 ? searchOptionId(activeSearchOptionIndex.value) : undefined
);

const isSearchDropdownOpen = computed(() => {
  if (!isSearchFocused.value) {
    return false;
  }

  if (trimmedSearchQuery.value === '') {
    return showRecentQueries.value;
  }

  if (trimmedSearchQuery.value.length < 2) {
    return false;
  }

  return showQuerySuggestions.value || showEntitySuggestions.value;
});

const searchOptionId = (index: number) => `public-header-search-option-${index}`;

const loadRecentQueries = () => {
  if (!import.meta.client) {
    return;
  }

  try {
    const payload = window.localStorage.getItem(RECENT_SEARCHES_KEY);
    const parsed = payload ? JSON.parse(payload) : [];

    recentQueries.value = Array.isArray(parsed)
      ? parsed.filter((item): item is string => typeof item === 'string' && item.trim().length > 0)
      : [];
  } catch {
    recentQueries.value = [];
  }
};

const persistRecentQuery = (value: string) => {
  const normalized = value.trim();
  if (!normalized || !import.meta.client) {
    return;
  }

  recentQueries.value = [
    normalized,
    ...recentQueries.value.filter((item) => item !== normalized),
  ].slice(0, 5);

  window.localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(recentQueries.value));
};

const clearSearchSuggestions = () => {
  activeSearchOptionIndex.value = -1;
  searchSuggestions.value = {
    queries: [],
    entities: [],
    recent: [],
  };
};

const fetchSearchSuggestions = () => {
  const query = searchQuery.value.trim();
  const requestId = ++activeSuggestRequestId;

  if (query === '' || query.length < 2) {
    searchLoading.value = false;
    clearSearchSuggestions();
    return;
  }

  searchLoading.value = true;

  searchDebounce.schedule(() => {
    void publicSearchApi
      .suggest(query, {
        limit: 8,
      })
      .then((response) => {
        if (requestId !== activeSuggestRequestId || query !== searchQuery.value.trim()) {
          return;
        }

        searchSuggestions.value = response;
      })
      .catch(() => {
        if (requestId !== activeSuggestRequestId) {
          return;
        }

        clearSearchSuggestions();
      })
      .finally(() => {
        if (requestId !== activeSuggestRequestId) {
          return;
        }

        searchLoading.value = false;
      });
  });
};

const closeSearch = () => {
  isSearchFocused.value = false;
  searchLoading.value = false;
  activeSearchOptionIndex.value = -1;
  activeSuggestRequestId += 1;
};

const runCatalogSearch = async (query: string) => {
  const normalized = query.trim();
  if (!normalized) {
    return;
  }

  persistRecentQuery(normalized);
  closeMenus();
  closeSearch();
  mobileSearchQuery.value = normalized;

  await router.push({
    path: '/catalog',
    query: {
      q: normalized,
    },
  });
};

const applyQuerySuggestion = (value: string) => {
  searchQuery.value = value;
  void runCatalogSearch(value);
};

const handleEntityClick = (label: string) => {
  persistRecentQuery(label);
  closeMenus();
  closeSearch();
};

const onSearchFocus = () => {
  isSearchFocused.value = true;
  loadRecentQueries();

  if (searchQuery.value.trim().length >= 2) {
    fetchSearchSuggestions();
  } else {
    clearSearchSuggestions();
  }
};

const clearDesktopSearch = async () => {
  searchQuery.value = '';
  clearSearchSuggestions();
  isSearchFocused.value = true;
  await nextTick();
  searchInput.value?.focus();
};

const moveActiveSearchOption = (direction: 1 | -1) => {
  if (!searchDropdownOptions.value.length) {
    activeSearchOptionIndex.value = -1;
    return;
  }

  if (activeSearchOptionIndex.value < 0) {
    activeSearchOptionIndex.value = direction > 0 ? 0 : searchDropdownOptions.value.length - 1;
    return;
  }

  activeSearchOptionIndex.value =
    (activeSearchOptionIndex.value + direction + searchDropdownOptions.value.length) %
    searchDropdownOptions.value.length;
};

const selectSearchOption = async (option: SearchDropdownOption) => {
  if (option.kind === 'entity') {
    persistRecentQuery(option.label);
    closeMenus();
    closeSearch();
    await router.push(option.entity.url);
    return;
  }

  await runCatalogSearch(option.label);
};

const onSearchInputKeydown = (event: KeyboardEvent) => {
  switch (event.key) {
    case 'ArrowDown':
      if (!searchDropdownOptions.value.length) {
        return;
      }
      event.preventDefault();
      moveActiveSearchOption(1);
      break;
    case 'ArrowUp':
      if (!searchDropdownOptions.value.length) {
        return;
      }
      event.preventDefault();
      moveActiveSearchOption(-1);
      break;
    case 'Enter':
      if (activeSearchOptionIndex.value < 0) {
        return;
      }
      event.preventDefault();
      void selectSearchOption(searchDropdownOptions.value[activeSearchOptionIndex.value]);
      break;
    case 'Escape':
      closeSearch();
      break;
    default:
      break;
  }
};

const escapeRegExp = (value: string) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const getHighlightParts = (value: string) => {
  const query = trimmedSearchQuery.value;

  if (!query || query.length < 2) {
    return [{ value, match: false }];
  }

  const matcher = new RegExp(`(${escapeRegExp(query)})`, 'ig');
  const parts = value.split(matcher).filter((part) => part.length > 0);

  if (!parts.length) {
    return [{ value, match: false }];
  }

  return parts.map((part) => ({
    value: part,
    match: part.toLocaleLowerCase() === query.toLocaleLowerCase(),
  }));
};

const submitDesktopSearch = () => {
  void runCatalogSearch(searchQuery.value);
};

const submitMobileSearch = () => {
  void runCatalogSearch(mobileSearchQuery.value);
};

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
  closeSearch();
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
    searchQuery.value =
      typeof route.query.q === 'string'
        ? route.query.q
        : typeof route.query.search === 'string'
          ? route.query.search
          : '';
    mobileSearchQuery.value = searchQuery.value;
    closeMenus();
  }
);

watch(searchQuery, () => {
  activeSearchOptionIndex.value = -1;

  if (!isSearchFocused.value) {
    return;
  }

  fetchSearchSuggestions();
});

watch(searchDropdownOptions, (options) => {
  if (!options.length) {
    activeSearchOptionIndex.value = -1;
    return;
  }

  if (activeSearchOptionIndex.value >= options.length) {
    activeSearchOptionIndex.value = options.length - 1;
  }
});

onMounted(() => {
  isThemeUiMounted.value = true;
  searchQuery.value =
    typeof route.query.q === 'string'
      ? route.query.q
      : typeof route.query.search === 'string'
        ? route.query.search
        : '';
  mobileSearchQuery.value = searchQuery.value;
  loadRecentQueries();
  window.addEventListener('keydown', onWindowKeydown);
  document.addEventListener('pointerdown', onDocumentPointerDown);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onWindowKeydown);
  document.removeEventListener('pointerdown', onDocumentPointerDown);
  searchDebounce.clear();
});
</script>
