export const useApi = () => {
  const config = useRuntimeConfig();
  const token = useCookie<string | null>('auth_token');
  const baseURL = import.meta.server ? config.apiInternalBase : config.public.apiBase;

  return $fetch.create({
    baseURL,
    timeout: 8000,
    credentials: 'include',
    headers: token.value
      ? {
          Authorization: `Bearer ${token.value}`,
        }
      : undefined,
  });
};
