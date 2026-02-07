interface AuthUser {
  id: string
  email: string
  first_name?: string
  last_name?: string
  roles?: string[]
  is_admin?: boolean
  can_access_admin_panel?: boolean
}

interface LoginResponse {
  token: string
  user: AuthUser
}

interface MeResponse {
  status: string
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

  const setUser = (nextUser: AuthUser | null) => {
    user.value = nextUser
  }

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
    setUser(response.user)
  }

  const refreshUser = async (): Promise<AuthUser | null> => {
    if (!token.value) {
      setUser(null)
      return null
    }

    const api = useApi()
    const response = await api<MeResponse>('/api/me')
    setUser(response.user)

    return response.user
  }

  const updateProfile = async (payload: Record<string, unknown>): Promise<AuthUser> => {
    const api = useApi()
    const response = await api<MeResponse>('/api/me', {
      method: 'PATCH',
      body: payload
    })

    setUser(response.user)
    return response.user
  }

  const updatePassword = async (payload: Record<string, unknown>): Promise<AuthUser> => {
    const api = useApi()
    const response = await api<MeResponse>('/api/me/password', {
      method: 'PATCH',
      body: payload
    })


    setUser(response.user)
    return response.user
  }

  const logout = async (): Promise<void> => {
    try {
      const api = useApi()
      await api('/api/auth/logout', { method: 'POST' })
    } catch {
      // ignore network/API logout failures and clear local auth state anyway
    }

    token.value = null
    setUser(null)
  }

  return {
    token,
    user,
    isAuthenticated,
    canAccessAdminPanel,
    login,
    refreshUser,
    updateProfile,
    updatePassword,
    logout
  }
}
