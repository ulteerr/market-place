interface AuthUser {
  id: string
  email: string
  first_name?: string
  last_name?: string
  can_access_admin_panel?: boolean
}

interface LoginResponse {
  token: string
  user: AuthUser
}

export const useAuth = () => {
  const token = useCookie<string | null>('auth_token', {
    sameSite: 'lax'
  })

  const user = useCookie<AuthUser | null>('auth_user', {
    sameSite: 'lax'
  })

  const isAuthenticated = computed(() => Boolean(token.value))
  const canAccessAdminPanel = computed(() => Boolean(user.value?.can_access_admin_panel))

  const login = async (email: string, password: string): Promise<void> => {
    const api = useApi()

    const response = await api<LoginResponse>('/api/auth/login', {
      method: 'POST',
      body: {
        email,
        password
      }
    })

    token.value = response.token
    user.value = response.user
  }

  const logout = async (): Promise<void> => {
    try {
      const api = useApi()
      await api('/api/auth/logout', { method: 'POST' })
    } catch {
      // ignore network/API logout failures and clear local auth state anyway
    }

    token.value = null
    user.value = null
  }

  return {
    token,
    user,
    isAuthenticated,
    canAccessAdminPanel,
    login,
    logout
  }
}
