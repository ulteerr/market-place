export type PrivatePreviewState = 'ready' | 'loading' | 'empty' | 'error';

const allowedStates: PrivatePreviewState[] = ['ready', 'loading', 'empty', 'error'];

export const usePrivatePreviewState = () => {
  const route = useRoute();

  return computed<PrivatePreviewState>(() => {
    const value = String(route.query.state ?? 'ready').toLowerCase() as PrivatePreviewState;

    return allowedStates.includes(value) ? value : 'ready';
  });
};
