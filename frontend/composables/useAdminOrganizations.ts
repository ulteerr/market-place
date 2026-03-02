import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export type OrganizationStatus = 'draft' | 'active' | 'suspended' | 'archived';
export type OrganizationSourceType = 'manual' | 'import' | 'parsed' | 'self_registered';
export type OrganizationOwnershipStatus = 'unclaimed' | 'pending_claim' | 'claimed';
export type OrganizationLocationTravelMode = 'walk' | 'drive';

export interface AdminOrganizationLocationMetroConnection {
  id: string;
  metro_station_id: string;
  travel_mode: OrganizationLocationTravelMode;
  duration_minutes: number;
  metro_station?: {
    id: string;
    name?: string | null;
    city_id?: string | null;
    metro_line_id?: string | null;
    metro_line?: {
      name?: string | null;
      color?: string | null;
    } | null;
  } | null;
}

export interface AdminOrganizationLocation {
  id: string;
  country_id?: string | null;
  region_id?: string | null;
  city_id?: string | null;
  district_id?: string | null;
  address?: string | null;
  lat?: number | null;
  lng?: number | null;
  metro_connections?: AdminOrganizationLocationMetroConnection[];
}

export interface OrganizationLocationMetroConnectionPayload {
  metro_station_id: string;
  travel_mode: OrganizationLocationTravelMode;
  duration_minutes: number;
}

export interface OrganizationLocationPayload {
  country_id?: string | null;
  region_id?: string | null;
  city_id?: string | null;
  district_id?: string | null;
  address?: string | null;
  lat?: number | null;
  lng?: number | null;
  metro_connections?: OrganizationLocationMetroConnectionPayload[];
}

export interface AdminOrganization {
  id: string;
  name: string;
  description?: string | null;
  address?: string | null;
  phone?: string | null;
  email?: string | null;
  status?: OrganizationStatus | null;
  source_type?: OrganizationSourceType | null;
  ownership_status?: OrganizationOwnershipStatus | null;
  owner_user_id?: string | null;
  created_by_user_id?: string | null;
  claimed_at?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  owner?: {
    id: string;
    first_name?: string | null;
    last_name?: string | null;
    middle_name?: string | null;
    email?: string | null;
  } | null;
  locations?: AdminOrganizationLocation[];
}

export interface OrganizationLocationMetroConnectionFormValue {
  metro_station_id: string;
  travel_mode: OrganizationLocationTravelMode;
  duration_minutes: number | null | '';
}

export interface OrganizationLocationFormValue {
  id?: string | null;
  country_id?: string | null;
  region_id?: string | null;
  city_id?: string | null;
  district_id?: string | null;
  address?: string | null;
  lat?: number | null;
  lng?: number | null;
  metro_connections?: OrganizationLocationMetroConnectionFormValue[];
}

export interface OrganizationFormValue {
  name: string;
  description: string;
  locations: OrganizationLocationFormValue[];
  phone: string;
  email: string;
  status: OrganizationStatus | '';
  source_type: OrganizationSourceType | '';
  ownership_status: OrganizationOwnershipStatus | '';
  owner_user_id: string;
}

interface OrganizationMutationResponse {
  status: string;
  message?: string;
  data?: AdminOrganization;
}

interface OrganizationShowResponse {
  status: string;
  data: AdminOrganization;
}

export interface AdminOrganizationsListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
}

export interface CreateOrganizationPayload {
  name: string;
  description?: string | null;
  address?: string | null;
  locations?: OrganizationLocationPayload[] | null;
  phone?: string | null;
  email?: string | null;
  status?: OrganizationStatus | null;
  source_type?: OrganizationSourceType | null;
  ownership_status?: OrganizationOwnershipStatus | null;
  owner_user_id?: string | null;
}

export interface UpdateOrganizationPayload {
  name?: string;
  description?: string | null;
  address?: string | null;
  locations?: OrganizationLocationPayload[] | null;
  phone?: string | null;
  email?: string | null;
  status?: OrganizationStatus | null;
  source_type?: OrganizationSourceType | null;
  ownership_status?: OrganizationOwnershipStatus | null;
  owner_user_id?: string | null;
}

const normalizeDurationMinutes = (value: number | null | '' | undefined): number | null => {
  if (value === '' || value === null || value === undefined) {
    return null;
  }

  const parsed = Number(value);
  if (!Number.isFinite(parsed)) {
    return null;
  }

  return parsed > 0 ? Math.trunc(parsed) : null;
};

export const createEmptyOrganizationLocationForm = (): OrganizationLocationFormValue => ({
  country_id: null,
  region_id: null,
  city_id: null,
  district_id: null,
  address: '',
  lat: null,
  lng: null,
  metro_connections: [],
});

export const sanitizeOrganizationFormLocations = (
  locations: OrganizationLocationFormValue[]
): OrganizationLocationPayload[] => {
  return locations
    .map((location) => {
      const metroConnections = (location.metro_connections || [])
        .map((connection) => ({
          metro_station_id: String(connection.metro_station_id || '').trim(),
          travel_mode: connection.travel_mode,
          duration_minutes: normalizeDurationMinutes(connection.duration_minutes),
        }))
        .filter(
          (connection): connection is OrganizationLocationMetroConnectionPayload =>
            connection.metro_station_id !== '' &&
            ['walk', 'drive'].includes(connection.travel_mode) &&
            connection.duration_minutes !== null
        );

      const normalized: OrganizationLocationPayload = {
        country_id: location.country_id || null,
        region_id: location.region_id || null,
        city_id: location.city_id || null,
        district_id: location.district_id || null,
        address: location.address?.trim() || null,
        lat:
          typeof location.lat === 'number' && Number.isFinite(location.lat) ? location.lat : null,
        lng:
          typeof location.lng === 'number' && Number.isFinite(location.lng) ? location.lng : null,
        metro_connections: metroConnections,
      };

      const hasValue = Boolean(
        normalized.country_id ||
        normalized.region_id ||
        normalized.city_id ||
        normalized.district_id ||
        normalized.address ||
        normalized.lat !== null ||
        normalized.lng !== null ||
        metroConnections.length
      );

      return hasValue ? normalized : null;
    })
    .filter((location): location is OrganizationLocationPayload => Boolean(location));
};

export const mapOrganizationApiToForm = (
  organization: AdminOrganization
): OrganizationFormValue => {
  const locations = organization.locations?.length
    ? organization.locations.map(
        (location): OrganizationLocationFormValue => ({
          id: location.id,
          country_id: location.country_id || null,
          region_id: location.region_id || null,
          city_id: location.city_id || null,
          district_id: location.district_id || null,
          address: location.address || '',
          lat: typeof location.lat === 'number' ? location.lat : null,
          lng: typeof location.lng === 'number' ? location.lng : null,
          metro_connections: (location.metro_connections || []).map((connection) => ({
            metro_station_id: connection.metro_station_id,
            travel_mode: connection.travel_mode,
            duration_minutes: normalizeDurationMinutes(connection.duration_minutes),
          })),
        })
      )
    : organization.address
      ? [
          {
            ...createEmptyOrganizationLocationForm(),
            address: organization.address,
          },
        ]
      : [createEmptyOrganizationLocationForm()];

  return {
    name: organization.name || '',
    description: organization.description || '',
    locations,
    phone: organization.phone || '',
    email: organization.email || '',
    status: (organization.status as OrganizationStatus | null) || '',
    source_type: (organization.source_type as OrganizationSourceType | null) || '',
    ownership_status: (organization.ownership_status as OrganizationOwnershipStatus | null) || '',
    owner_user_id: organization.owner_user_id || '',
  };
};

export const buildCreateOrganizationPayloadFromForm = (
  form: OrganizationFormValue
): CreateOrganizationPayload => ({
  name: form.name.trim(),
  description: form.description.trim() || null,
  locations: sanitizeOrganizationFormLocations(form.locations),
  phone: form.phone.trim() || null,
  email: form.email.trim() || null,
  status: form.status || null,
  source_type: form.source_type || null,
  ownership_status: form.ownership_status || null,
  owner_user_id: form.owner_user_id.trim() || null,
});

export const buildUpdateOrganizationPayloadFromForm = (
  form: OrganizationFormValue
): UpdateOrganizationPayload => ({
  name: form.name.trim(),
  description: form.description.trim() || null,
  locations: sanitizeOrganizationFormLocations(form.locations),
  phone: form.phone.trim() || null,
  email: form.email.trim() || null,
  status: form.status || null,
  source_type: form.source_type || null,
  ownership_status: form.ownership_status || null,
  owner_user_id: form.owner_user_id.trim() || null,
});

export const getAdminOrganizationOwnerName = (organization: AdminOrganization): string => {
  const owner = organization.owner;

  if (!owner) {
    return organization.owner_user_id || '';
  }

  const fullName = [owner.last_name, owner.first_name, owner.middle_name]
    .filter((part): part is string => typeof part === 'string' && part.trim().length > 0)
    .join(' ');

  return fullName || owner.email || owner.id;
};

export const useAdminOrganizations = () => {
  const api = useApi();

  const list = async (
    params: AdminOrganizationsListParams = {}
  ): Promise<PaginationPayload<AdminOrganization>> => {
    const response = await api<IndexResponse<AdminOrganization>>('/api/admin/organizations', {
      query: params,
    });

    return response.data;
  };

  const show = async (id: string): Promise<AdminOrganization> => {
    const response = await api<OrganizationShowResponse>(`/api/admin/organizations/${id}`);

    return response.data;
  };

  const create = async (payload: CreateOrganizationPayload): Promise<void> => {
    await api<OrganizationMutationResponse>('/api/admin/organizations', {
      method: 'POST',
      body: payload,
    });
  };

  const update = async (id: string, payload: UpdateOrganizationPayload): Promise<void> => {
    await api<OrganizationMutationResponse>(`/api/admin/organizations/${id}`, {
      method: 'PATCH',
      body: payload,
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/organizations/${id}`, {
      method: 'DELETE',
    });
  };

  return {
    list,
    show,
    create,
    update,
    remove,
  };
};
