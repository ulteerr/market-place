export const useApi = () => {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('auth_token')

  return $fetch.create({
    baseURL: config.public.apiBase,
    credentials: 'include',
    headers: token.value
      ? {
          Authorization: `Bearer ${token.value}`
        }
      : undefined
  })
}
