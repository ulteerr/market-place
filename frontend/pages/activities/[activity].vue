<template>
  <PublicSection data-test="public-activity-redirect">
    <UiCardSkeleton data-test="public-activity-redirect-loading" />
  </PublicSection>
</template>

<script setup lang="ts">
import { buildPublicActivityPath, usePublicActivities } from '~/composables/usePublicActivities';
import PublicSection from '~/components/public/PublicSection/PublicSection.vue';
import UiCardSkeleton from '~/components/ui/Skeleton/UiCardSkeleton.vue';

definePageMeta({
  layout: 'default',
});

const route = useRoute();
const publicActivitiesApi = usePublicActivities();

onMounted(async () => {
  const publicKey = String(route.params.activity || '');

  if (!publicKey) {
    await navigateTo('/catalog', { replace: true });

    return;
  }

  try {
    const activity = await publicActivitiesApi.show(publicKey);

    await navigateTo(
      buildPublicActivityPath({
        public_key: publicKey,
        primary_category: activity.primary_category,
      }),
      { replace: true }
    );
  } catch {
    await navigateTo('/catalog', { replace: true });
  }
});
</script>
