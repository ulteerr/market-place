<template>
  <section :class="styles.page" data-test="account-profile-page">
    <AccountPageSkeleton
      v-if="pageState === 'loading'"
      :cards="2"
      :list-items="3"
      data-test="account-profile-loading"
    />

    <template v-else>
      <PageHero
        :eyebrow="t('app.account.profile.eyebrow')"
        :title="t('app.account.profile.title')"
        :description="t('app.account.profile.description')"
      />

      <PrivateStateMessage
        v-if="pageState === 'empty'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.account.profile.emptyTitle')"
        :description="t('app.account.profile.emptyDescription')"
        data-test="account-profile-empty"
      />

      <PrivateStateMessage
        v-else-if="pageState === 'error'"
        :eyebrow="t('app.defaults.stateEyebrow')"
        :title="t('app.account.profile.errorTitle')"
        :description="t('app.account.profile.errorDescription')"
        data-test="account-profile-error"
      />

      <template v-else>
        <div :class="styles.grid">
          <AccountDashboardSection
            :eyebrow="t('app.account.profile.sections.summaryEyebrow')"
            :title="t('app.account.profile.sections.summaryTitle')"
            data-test="account-profile-summary"
          >
            <dl :class="styles.metaList">
              <div v-for="item in profileSummary" :key="item.label" :class="styles.metaRow">
                <dt :class="styles.metaLabel">{{ item.label }}</dt>
                <dd :class="styles.metaValue">{{ item.value }}</dd>
              </div>
            </dl>
          </AccountDashboardSection>

          <AccountDashboardSection
            :eyebrow="t('app.account.profile.sections.preferencesEyebrow')"
            :title="t('app.account.profile.sections.preferencesTitle')"
            data-test="account-profile-preferences"
          >
            <div :class="styles.preferenceStack">
              <div :class="styles.preferenceRow">
                <div>
                  <p :class="styles.itemTitle">
                    {{ t('app.account.profile.preferences.themeTitle') }}
                  </p>
                  <p :class="styles.itemText">
                    {{ t('app.account.profile.preferences.themeDescription') }}
                  </p>
                </div>
                <div :class="styles.actions">
                  <button
                    type="button"
                    :class="[styles.chip, !isDark ? styles.chipActive : '']"
                    data-test="account-profile-theme-light"
                    @click="setTheme('light')"
                  >
                    {{ t('app.account.profile.preferences.light') }}
                  </button>
                  <button
                    type="button"
                    :class="[styles.chip, isDark ? styles.chipActive : '']"
                    data-test="account-profile-theme-dark"
                    @click="setTheme('dark')"
                  >
                    {{ t('app.account.profile.preferences.dark') }}
                  </button>
                </div>
              </div>

              <div :class="styles.preferenceRow">
                <div>
                  <p :class="styles.itemTitle">
                    {{ t('app.account.profile.preferences.collapseTitle') }}
                  </p>
                  <p :class="styles.itemText">
                    {{ t('app.account.profile.preferences.collapseDescription') }}
                  </p>
                </div>
                <button
                  type="button"
                  :class="[styles.toggle, settings.collapse_menu ? styles.toggleActive : '']"
                  data-test="account-profile-collapse-menu"
                  :aria-pressed="settings.collapse_menu ? 'true' : 'false'"
                  @click="setCollapseMenu(!settings.collapse_menu)"
                >
                  {{
                    settings.collapse_menu
                      ? t('app.account.profile.preferences.enabled')
                      : t('app.account.profile.preferences.disabled')
                  }}
                </button>
              </div>

              <div :class="styles.preferenceRow">
                <div>
                  <p :class="styles.itemTitle">
                    {{ t('app.account.profile.preferences.localeTitle') }}
                  </p>
                  <p :class="styles.itemText">
                    {{ t('app.account.profile.preferences.localeDescription') }}
                  </p>
                </div>
                <span :class="styles.metaBadge">{{ settings.locale.toUpperCase() }}</span>
              </div>
            </div>
          </AccountDashboardSection>
        </div>

        <AccountDashboardSection
          :eyebrow="t('app.account.profile.sections.accessEyebrow')"
          :title="t('app.account.profile.sections.accessTitle')"
          data-test="account-profile-access"
        >
          <ul :class="styles.statusList">
            <li :class="styles.statusItem">
              <div>
                <p :class="styles.itemTitle">{{ t('app.account.profile.access.authTitle') }}</p>
                <p :class="styles.itemText">
                  {{ t('app.account.profile.access.authDescription') }}
                </p>
              </div>
              <span :class="styles.metaBadge">{{
                isAuthenticated
                  ? t('app.account.profile.access.authActive')
                  : t('app.account.profile.access.authGuest')
              }}</span>
            </li>
            <li :class="styles.statusItem">
              <div>
                <p :class="styles.itemTitle">{{ t('app.account.profile.access.debugTitle') }}</p>
                <p :class="styles.itemText">
                  {{ t('app.account.profile.access.debugDescription') }}
                </p>
              </div>
              <span :class="styles.metaBadge">{{
                t('app.account.profile.access.debugReady')
              }}</span>
            </li>
            <li :class="styles.statusItem">
              <div>
                <p :class="styles.itemTitle">
                  {{ t('app.account.profile.access.organizationsTitle') }}
                </p>
                <p :class="styles.itemText">
                  {{ t('app.account.profile.access.organizationsDescription') }}
                </p>
              </div>
              <NuxtLink to="/organizations" :class="styles.inlineLink">{{
                t('app.account.profile.access.organizationsOpen')
              }}</NuxtLink>
            </li>
          </ul>
        </AccountDashboardSection>
      </template>
    </template>
  </section>
</template>

<script setup lang="ts">
import { usePrivatePreviewState } from '~/composables/layout/usePrivatePreviewState';
import AccountPageSkeleton from '~/components/account/AccountPageSkeleton/AccountPageSkeleton.vue';
import AccountDashboardSection from '~/components/account/AccountDashboardSection/AccountDashboardSection.vue';
import PrivateStateMessage from '~/components/private/PrivateStateMessage/PrivateStateMessage.vue';
import PageHero from '~/components/ui/PageHero/PageHero.vue';
import styles from './profile.module.scss';

definePageMeta({
  layout: 'account',
});

const { t } = useI18n();
const pageState = usePrivatePreviewState();
const { user, isAuthenticated } = useAuth();
const { isDark, settings, setTheme, setCollapseMenu } = useUserSettings();

const profileSummary = computed(() => [
  {
    label: t('app.account.profile.summary.name'),
    value: user.value?.name || t('app.account.profile.summary.nameEmpty'),
  },
  {
    label: t('app.account.profile.summary.email'),
    value: user.value?.email || t('app.account.profile.summary.emailEmpty'),
  },
  {
    label: t('app.account.profile.summary.id'),
    value: user.value?.id ? String(user.value.id) : t('app.account.profile.summary.idEmpty'),
  },
]);
</script>
