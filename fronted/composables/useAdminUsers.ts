import type { IndexResponse, PaginationPayload, SortDirection } from '~/composables/useAdminCrudCommon'

export interface AdminUser {
  id: string
  email: string
  first_name: string
  last_name: string
  middle_name?: string | null
  phone?: string | null
  roles?: string[]
  can_access_admin_panel?: boolean
}

interface UserShowResponse {
  status: string
  user: AdminUser
}

interface UserMutationResponse {
  status: string
  message?: string
  data?: AdminUser
}

export interface CreateUserPayload {
  email: string
  password: string
  password_confirmation: string
  first_name: string
  last_name: string
  middle_name?: string | null
  phone?: string | null
  roles?: string[]
}

export interface UpdateUserPayload {
  email?: string
  password?: string
  password_confirmation?: string
  first_name?: string
  last_name?: string
  middle_name?: string | null
  phone?: string | null
  roles?: string[]
}

export interface AdminUsersListParams {
  page?: number
  per_page?: number
  search?: string
  sort_by?: string
  sort_dir?: SortDirection
}

export const getAdminUserFullName = (user: AdminUser): string => {
  return [user.first_name, user.last_name].filter(Boolean).join(' ') || user.email
}

export const resolveAdminUserPanelAccess = (user: AdminUser): boolean | null => {
  if (typeof user.can_access_admin_panel === 'boolean') {
    return user.can_access_admin_panel
  }

  if (Array.isArray(user.roles)) {
    return user.roles.some((role) => role !== 'participant')
  }

  return null
}

export const useAdminUsers = () => {
  const api = useApi()

  const list = async (params: AdminUsersListParams = {}): Promise<PaginationPayload<AdminUser>> => {
    const response = await api<IndexResponse<AdminUser>>('/api/admin/users', {
      query: params
    })

    return response.data
  }

  const show = async (id: string): Promise<AdminUser> => {
    const response = await api<UserShowResponse>(`/api/admin/users/${id}`)

    return response.user
  }

  const create = async (payload: CreateUserPayload): Promise<void> => {
    await api<UserMutationResponse>('/api/admin/users', {
      method: 'POST',
      body: payload
    })
  }

  const update = async (id: string, payload: UpdateUserPayload): Promise<void> => {
    await api<UserMutationResponse>(`/api/admin/users/${id}`, {
      method: 'PATCH',
      body: payload
    })
  }

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/users/${id}`, {
      method: 'DELETE'
    })
  }

  return {
    list,
    show,
    create,
    update,
    remove
  }
}
