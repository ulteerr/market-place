export type PublicPreviewState = 'ready' | 'loading' | 'empty' | 'error';

const allowedStates: PublicPreviewState[] = ['ready', 'loading', 'empty', 'error'];

export const usePublicPreviewState = () => {
  const route = useRoute();

  return computed<PublicPreviewState>(() => {
    const value = String(route.query.state ?? 'ready').toLowerCase() as PublicPreviewState;

    return allowedStates.includes(value) ? value : 'ready';
  });
};
