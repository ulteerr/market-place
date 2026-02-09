export const useApi = () => {
  const config = useRuntimeConfig();
  const token = useCookie<string | null>('auth_token');

  return $fetch.create({
    baseURL: config.public.apiBase,
    timeout: 8000,
    credentials: 'include',
    headers: token.value
      ? {
          Authorization: `Bearer ${token.value}`,
        }
      : undefined,
  });
};
