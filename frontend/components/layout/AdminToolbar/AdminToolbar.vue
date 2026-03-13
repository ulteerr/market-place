<template>
  <aside
    v-if="canAccessAdminPanel"
    :class="styles.toolbar"
    data-test="admin-toolbar"
    :aria-label="t('app.layout.adminToolbar.aria')"
  >
    <div :class="styles.container">
      <NuxtLink to="/admin" :class="styles.link" data-test="admin-toolbar-go-admin">
        {{ t('app.layout.adminToolbar.goAdmin') }}
      </NuxtLink>

      <button
        type="button"
        :class="styles.button"
        :disabled="isResetting"
        data-test="admin-toolbar-cache-reset"
        @click="onResetCache"
      >
        {{
          isResetting
            ? t('app.layout.adminToolbar.resetPending')
            : t('app.layout.adminToolbar.resetIdle')
        }}
      </button>

      <span v-if="statusText" :class="styles.status" role="status" aria-live="polite">
        {{ statusText }}
      </span>
    </div>
  </aside>
</template>

<script setup lang="ts">
import styles from './AdminToolbar.module.scss';

const { t } = useI18n();
const { canAccessAdminPanel } = useAuth();
const api = useApi();
const isResetting = ref(false);
const statusText = ref('');

const onResetCache = async () => {
  if (isResetting.value) {
    return;
  }

  isResetting.value = true;
  statusText.value = '';

  try {
    await api('/api/admin/cache/reset', {
      method: 'POST',
      body: {
        scopes: ['frontend-ssr', 'backend'],
      },
    });

    statusText.value = t('app.layout.adminToolbar.resetSuccess');
  } catch {
    statusText.value = t('app.layout.adminToolbar.resetError');
  } finally {
    isResetting.value = false;
  }
};
</script>
