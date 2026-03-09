<template>
  <section class="mx-auto w-full max-w-6xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.organizations.show.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.organizations.show.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <template v-else-if="organization">
        <dl class="grid gap-3 sm:grid-cols-2">
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.name') }}</dt>
            <dd>{{ organization.name }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.status') }}</dt>
            <dd>{{ resolveStatusLabel(organization.status) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">
              {{ t('admin.organizations.fields.ownershipStatus') }}
            </dt>
            <dd>{{ resolveOwnershipLabel(organization.ownership_status) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.sourceType') }}</dt>
            <dd>{{ resolveSourceLabel(organization.source_type) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.email') }}</dt>
            <dd>{{ organization.email || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.phone') }}</dt>
            <dd>{{ organization.phone || t('common.dash') }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.address') }}</dt>
            <dd>{{ organization.address || t('common.dash') }}</dd>
          </div>
          <div v-if="organization.locations?.length" class="sm:col-span-2">
            <dt class="admin-muted text-xs">{{ t('admin.organizations.locations.title') }}</dt>
            <dd class="space-y-3">
              <div
                v-for="(location, index) in organization.locations"
                :key="location.id"
                class="rounded-xl border border-[color:var(--admin-border)] p-3"
              >
                <p class="text-sm font-semibold">
                  {{ t('admin.organizations.locations.locationTitle', { index: index + 1 }) }}
                </p>
                <p class="mt-1 text-sm">{{ location.address || t('common.dash') }}</p>
                <p class="admin-muted text-xs">
                  {{ t('admin.organizations.fields.city') }}:
                  {{ location.city_id || t('common.dash') }}
                </p>
                <p class="admin-muted text-xs">
                  {{ t('admin.organizations.fields.district') }}:
                  {{ location.district_id || t('common.dash') }}
                </p>
                <p class="admin-muted text-xs">
                  {{ t('admin.organizations.fields.coordinates') }}:
                  {{ formatCoordinates(location.lat, location.lng) }}
                </p>
                <div v-if="location.metro_connections?.length" class="mt-2 space-y-1">
                  <p class="admin-muted text-xs">
                    {{ t('admin.organizations.locations.metroTitle') }}
                  </p>
                  <p
                    v-for="(connection, connectionIndex) in location.metro_connections"
                    :key="connection.id"
                    class="text-xs"
                  >
                    {{
                      t('admin.organizations.locations.metroSummary', {
                        index: connectionIndex + 1,
                        station:
                          connection.metro_station?.name ||
                          connection.metro_station_id ||
                          t('common.dash'),
                        mode: resolveTravelModeLabel(connection.travel_mode),
                        minutes: connection.duration_minutes,
                      })
                    }}
                  </p>
                </div>
              </div>
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.description') }}</dt>
            <dd>{{ organization.description || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.owner') }}</dt>
            <dd>
              <AdminLink
                v-if="organization.owner?.id"
                :to="`/admin/users/${organization.owner.id}`"
              >
                {{ resolveOwnerLabel(organization) }}
              </AdminLink>
              <span v-else>{{ resolveOwnerLabel(organization) }}</span>
            </dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.ownerUserId') }}</dt>
            <dd>{{ organization.owner_user_id || t('common.dash') }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.createdAt') }}</dt>
            <dd>{{ formatDate(organization.created_at) }}</dd>
          </div>
          <div>
            <dt class="admin-muted text-xs">{{ t('admin.organizations.fields.claimedAt') }}</dt>
            <dd>{{ formatDate(organization.claimed_at) }}</dd>
          </div>
        </dl>

        <div class="mt-5 flex gap-2">
          <NuxtLink
            v-if="canWriteOrganizations"
            :to="`/admin/organizations/${organization.id}/edit`"
            class="admin-button rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.edit') }}
          </NuxtLink>
          <NuxtLink
            to="/admin/organizations"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.backToList') }}
          </NuxtLink>
        </div>
      </template>
    </article>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <h3 class="text-lg font-semibold">{{ t('admin.organizations.show.members.title') }}</h3>
      <p class="admin-muted mt-1 text-sm">{{ t('admin.organizations.show.members.subtitle') }}</p>

      <div class="mt-4 space-y-4">
        <AdminListToolbar
          :search-value="membersSearchInput"
          :search-placeholder="t('admin.organizations.show.members.searchPlaceholder')"
          :per-page="membersPerPage"
          :per-page-options="listPerPageOptions"
          :total-count="membersPagination.total"
          :loading="membersLoading"
          @update:search-value="(value) => (membersSearchInput = value)"
          @update:per-page="onMembersPerPageChange"
          @apply="onMembersApplySearch"
          @reset="onMembersReset"
        />

        <div class="space-y-3">
          <p class="admin-muted text-xs">
            {{ t('admin.organizations.show.members.filterStatus') }}
          </p>
          <AdminTagFilter
            v-model="membersStatusFilters"
            :options="memberStatusFilterOptions"
            mode="single"
            @update:model-value="onMembersStatusFilterChange"
          />
        </div>

        <div
          class="flex w-full min-w-0 flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:justify-end"
        >
          <div class="mode-select-wrap">
            <UiSelect
              :model-value="membersViewMode"
              :options="modeOptions"
              :placeholder="t('admin.entity.modePlaceholder')"
              :searchable="false"
              @update:model-value="onMembersModeChange"
            />
          </div>

          <button
            v-if="membersViewMode === 'table-cards'"
            type="button"
            class="admin-button-secondary w-full rounded-md px-2 py-1.5 text-xs sm:w-auto"
            @click="membersTableOnDesktop = !membersTableOnDesktop"
          >
            {{
              membersTableOnDesktop
                ? t('admin.entity.desktopTable')
                : t('admin.entity.desktopCards')
            }}
          </button>
        </div>

        <p v-if="membersError" class="admin-error text-sm">{{ membersError }}</p>
        <p v-if="membersLoading" class="admin-muted text-sm">{{ t('common.loading') }}</p>

        <template v-else>
          <p v-if="!members.length" class="admin-muted text-sm">
            {{ t('admin.organizations.show.members.empty') }}
          </p>

          <AdminContentView
            v-else
            :mode="membersViewMode"
            :table-on-desktop="membersTableOnDesktop"
          >
            <template #table>
              <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
                <table class="admin-table min-w-[760px]">
                  <thead>
                    <tr>
                      <th>
                        <button type="button" class="sort-btn" @click="onMembersSort('id')">
                          {{ t('admin.organizations.show.members.headers.id') }}
                          {{ membersSortMark('id') }}
                        </button>
                      </th>
                      <th>
                        <button type="button" class="sort-btn" @click="onMembersSort('created_at')">
                          {{ t('admin.organizations.show.members.headers.user') }}
                          {{ membersSortMark('created_at') }}
                        </button>
                      </th>
                      <th>
                        <button type="button" class="sort-btn" @click="onMembersSort('position')">
                          {{ t('admin.organizations.show.members.headers.position') }}
                          {{ membersSortMark('position') }}
                        </button>
                      </th>
                      <th>
                        <button type="button" class="sort-btn" @click="onMembersSort('status')">
                          {{ t('admin.organizations.show.members.headers.status') }}
                          {{ membersSortMark('status') }}
                        </button>
                      </th>
                      <th>
                        <button type="button" class="sort-btn" @click="onMembersSort('joined_at')">
                          {{ t('admin.organizations.show.members.headers.joinedAt') }}
                          {{ membersSortMark('joined_at') }}
                        </button>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="member in members" :key="member.id">
                      <td class="font-mono text-xs">{{ member.id }}</td>
                      <td>
                        <AdminLink v-if="member.user_id" :to="`/admin/users/${member.user_id}`">
                          {{ resolveMemberLabel(member) }}
                        </AdminLink>
                        <span v-else>{{ resolveMemberLabel(member) }}</span>
                      </td>
                      <td>{{ member.position || t('common.dash') }}</td>
                      <td>{{ resolveMemberStatusLabel(member.status) }}</td>
                      <td>{{ formatDate(member.joined_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>

            <template #cards>
              <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <article
                  v-for="member in members"
                  :key="member.id"
                  class="rounded-xl border border-[color:var(--admin-border)] p-4"
                >
                  <p class="font-medium">
                    <AdminLink v-if="member.user_id" :to="`/admin/users/${member.user_id}`">
                      {{ resolveMemberLabel(member) }}
                    </AdminLink>
                    <span v-else>{{ resolveMemberLabel(member) }}</span>
                  </p>
                  <p class="admin-muted mt-2 text-xs">
                    {{ t('admin.organizations.show.members.card.id', { value: member.id }) }}
                  </p>
                  <p class="admin-muted text-xs">
                    {{
                      t('admin.organizations.show.members.card.position', {
                        value: member.position || t('common.dash'),
                      })
                    }}
                  </p>
                  <p class="admin-muted text-xs">
                    {{
                      t('admin.organizations.show.members.card.status', {
                        value: resolveMemberStatusLabel(member.status),
                      })
                    }}
                  </p>
                  <p class="admin-muted text-xs">
                    {{
                      t('admin.organizations.show.members.card.joinedAt', {
                        value: formatDate(member.joined_at),
                      })
                    }}
                  </p>
                </article>
              </div>
            </template>
          </AdminContentView>

          <AdminPagination
            :visible="membersPagination.total > membersPagination.per_page"
            :current-page="membersPagination.current_page"
            :last-page="membersPagination.last_page"
            :per-page="membersPagination.per_page"
            :items="membersPaginationItems"
            :loading="membersLoading"
            @page="fetchMembers"
          />
        </template>
      </div>
    </article>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <h3 class="text-lg font-semibold">{{ t('admin.organizations.show.clients.title') }}</h3>
      <p class="admin-muted mt-1 text-sm">{{ t('admin.organizations.show.clients.subtitle') }}</p>

      <div class="mt-4 space-y-4">
        <AdminListToolbar
          :search-value="clientsSearchInput"
          :search-placeholder="t('admin.organizations.show.clients.searchPlaceholder')"
          :per-page="clientsPerPage"
          :per-page-options="listPerPageOptions"
          :total-count="clientsPagination.total"
          :loading="clientsLoading"
          @update:search-value="(value) => (clientsSearchInput = value)"
          @update:per-page="onClientsPerPageChange"
          @apply="onClientsApplySearch"
          @reset="onClientsReset"
        />

        <div class="space-y-3">
          <p class="admin-muted text-xs">
            {{ t('admin.organizations.show.clients.filterStatus') }}
          </p>
          <AdminTagFilter
            v-model="clientsStatusFilters"
            :options="clientStatusFilterOptions"
            mode="single"
            @update:model-value="onClientsFiltersChange"
          />
        </div>

        <div class="space-y-3">
          <p class="admin-muted text-xs">{{ t('admin.organizations.show.clients.filterType') }}</p>
          <AdminTagFilter
            v-model="clientsTypeFilters"
            :options="clientTypeFilterOptions"
            mode="single"
            @update:model-value="onClientsFiltersChange"
          />
        </div>

        <div
          class="flex w-full min-w-0 flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:justify-end"
        >
          <div class="mode-select-wrap">
            <UiSelect
              :model-value="clientsViewMode"
              :options="modeOptions"
              :placeholder="t('admin.entity.modePlaceholder')"
              :searchable="false"
              @update:model-value="onClientsModeChange"
            />
          </div>

          <button
            v-if="clientsViewMode === 'table-cards'"
            type="button"
            class="admin-button-secondary w-full rounded-md px-2 py-1.5 text-xs sm:w-auto"
            @click="clientsTableOnDesktop = !clientsTableOnDesktop"
          >
            {{
              clientsTableOnDesktop
                ? t('admin.entity.desktopTable')
                : t('admin.entity.desktopCards')
            }}
          </button>
        </div>

        <p v-if="clientsError" class="admin-error text-sm">{{ clientsError }}</p>
        <p v-if="clientsLoading" class="admin-muted text-sm">{{ t('common.loading') }}</p>

        <template v-else>
          <p v-if="!clients.length" class="admin-muted text-sm">
            {{ t('admin.organizations.show.clients.empty') }}
          </p>

          <AdminContentView
            v-else
            :mode="clientsViewMode"
            :table-on-desktop="clientsTableOnDesktop"
          >
            <template #table>
              <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
                <table class="admin-table min-w-[860px]">
                  <thead>
                    <tr>
                      <th>
                        <button type="button" class="sort-btn" @click="onClientsSort('id')">
                          {{ t('admin.organizations.show.clients.headers.id') }}
                          {{ clientsSortMark('id') }}
                        </button>
                      </th>
                      <th>
                        <button
                          type="button"
                          class="sort-btn"
                          @click="onClientsSort('subject_type')"
                        >
                          {{ t('admin.organizations.show.clients.headers.type') }}
                          {{ clientsSortMark('subject_type') }}
                        </button>
                      </th>
                      <th>
                        <button type="button" class="sort-btn" @click="onClientsSort('created_at')">
                          {{ t('admin.organizations.show.clients.headers.subject') }}
                          {{ clientsSortMark('created_at') }}
                        </button>
                      </th>
                      <th>
                        <button type="button" class="sort-btn" @click="onClientsSort('status')">
                          {{ t('admin.organizations.show.clients.headers.status') }}
                          {{ clientsSortMark('status') }}
                        </button>
                      </th>
                      <th>
                        <button type="button" class="sort-btn" @click="onClientsSort('joined_at')">
                          {{ t('admin.organizations.show.clients.headers.joinedAt') }}
                          {{ clientsSortMark('joined_at') }}
                        </button>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="client in clients" :key="client.id">
                      <td class="font-mono text-xs">{{ client.id }}</td>
                      <td>{{ resolveJoinRequestTypeLabel(client.subject_type) }}</td>
                      <td>
                        <AdminLink
                          v-if="client.subject_type === 'user'"
                          :to="`/admin/users/${client.subject_id}`"
                        >
                          {{ resolveClientLabel(client) }}
                        </AdminLink>
                        <AdminLink
                          v-else-if="client.subject_type === 'child'"
                          :to="`/admin/children/${client.subject_id}`"
                        >
                          {{ resolveClientLabel(client) }}
                        </AdminLink>
                        <span v-else>{{ resolveClientLabel(client) }}</span>
                      </td>
                      <td>{{ resolveClientStatusLabel(client.status) }}</td>
                      <td>{{ formatDate(client.joined_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>

            <template #cards>
              <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <article
                  v-for="client in clients"
                  :key="client.id"
                  class="rounded-xl border border-[color:var(--admin-border)] p-4"
                >
                  <p class="font-medium">
                    <AdminLink
                      v-if="client.subject_type === 'user'"
                      :to="`/admin/users/${client.subject_id}`"
                    >
                      {{ resolveClientLabel(client) }}
                    </AdminLink>
                    <AdminLink
                      v-else-if="client.subject_type === 'child'"
                      :to="`/admin/children/${client.subject_id}`"
                    >
                      {{ resolveClientLabel(client) }}
                    </AdminLink>
                    <span v-else>{{ resolveClientLabel(client) }}</span>
                  </p>
                  <p class="admin-muted mt-2 text-xs">
                    {{ t('admin.organizations.show.clients.card.id', { value: client.id }) }}
                  </p>
                  <p class="admin-muted text-xs">
                    {{
                      t('admin.organizations.show.clients.card.type', {
                        value: resolveJoinRequestTypeLabel(client.subject_type),
                      })
                    }}
                  </p>
                  <p class="admin-muted text-xs">
                    {{
                      t('admin.organizations.show.clients.card.status', {
                        value: resolveClientStatusLabel(client.status),
                      })
                    }}
                  </p>
                  <p class="admin-muted text-xs">
                    {{
                      t('admin.organizations.show.clients.card.joinedAt', {
                        value: formatDate(client.joined_at),
                      })
                    }}
                  </p>
                </article>
              </div>
            </template>
          </AdminContentView>

          <AdminPagination
            :visible="clientsPagination.total > clientsPagination.per_page"
            :current-page="clientsPagination.current_page"
            :last-page="clientsPagination.last_page"
            :per-page="clientsPagination.per_page"
            :items="clientsPaginationItems"
            :loading="clientsLoading"
            @page="fetchClients"
          />
        </template>
      </div>
    </article>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <h3 class="text-lg font-semibold">{{ t('admin.organizations.show.joinRequests.title') }}</h3>
      <p class="admin-muted mt-1 text-sm">
        {{ t('admin.organizations.show.joinRequests.subtitle') }}
      </p>

      <div class="mt-4 space-y-4">
        <AdminListToolbar
          :search-value="joinRequestsSearchInput"
          :search-placeholder="t('admin.organizations.show.joinRequests.searchPlaceholder')"
          :per-page="joinRequestsPerPage"
          :per-page-options="listPerPageOptions"
          :total-count="joinRequestsPagination.total"
          :loading="joinRequestsLoading"
          @update:search-value="(value) => (joinRequestsSearchInput = value)"
          @update:per-page="onJoinRequestsPerPageChange"
          @apply="onJoinRequestsApplySearch"
          @reset="onJoinRequestsReset"
        />

        <div class="space-y-3">
          <p class="admin-muted text-xs">
            {{ t('admin.organizations.show.joinRequests.filterStatus') }}
          </p>
          <AdminTagFilter
            v-model="joinRequestsStatusFilters"
            :options="joinRequestStatusFilterOptions"
            mode="single"
            @update:model-value="onJoinRequestsFiltersChange"
          />
        </div>

        <div class="space-y-3">
          <p class="admin-muted text-xs">
            {{ t('admin.organizations.show.joinRequests.filterType') }}
          </p>
          <AdminTagFilter
            v-model="joinRequestsTypeFilters"
            :options="joinRequestTypeFilterOptions"
            mode="single"
            @update:model-value="onJoinRequestsFiltersChange"
          />
        </div>

        <div
          class="flex w-full min-w-0 flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:justify-end"
        >
          <div class="mode-select-wrap">
            <UiSelect
              :model-value="joinRequestsViewMode"
              :options="modeOptions"
              :placeholder="t('admin.entity.modePlaceholder')"
              :searchable="false"
              @update:model-value="onJoinRequestsModeChange"
            />
          </div>

          <button
            v-if="joinRequestsViewMode === 'table-cards'"
            type="button"
            class="admin-button-secondary w-full rounded-md px-2 py-1.5 text-xs sm:w-auto"
            @click="joinRequestsTableOnDesktop = !joinRequestsTableOnDesktop"
          >
            {{
              joinRequestsTableOnDesktop
                ? t('admin.entity.desktopTable')
                : t('admin.entity.desktopCards')
            }}
          </button>
        </div>

        <p v-if="joinRequestsError" class="admin-error text-sm">{{ joinRequestsError }}</p>
        <p v-if="joinRequestsLoading" class="admin-muted text-sm">{{ t('common.loading') }}</p>

        <template v-else>
          <p v-if="!joinRequests.length" class="admin-muted text-sm">
            {{ t('admin.organizations.show.joinRequests.empty') }}
          </p>

          <AdminContentView
            v-else
            :mode="joinRequestsViewMode"
            :table-on-desktop="joinRequestsTableOnDesktop"
          >
            <template #table>
              <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
                <table class="admin-table min-w-[980px]">
                  <thead>
                    <tr>
                      <th>
                        <button type="button" class="sort-btn" @click="onJoinRequestsSort('id')">
                          {{ t('admin.organizations.show.joinRequests.headers.id') }}
                          {{ joinRequestsSortMark('id') }}
                        </button>
                      </th>
                      <th>
                        <button
                          type="button"
                          class="sort-btn"
                          @click="onJoinRequestsSort('subject_type')"
                        >
                          {{ t('admin.organizations.show.joinRequests.headers.type') }}
                          {{ joinRequestsSortMark('subject_type') }}
                        </button>
                      </th>
                      <th>{{ t('admin.organizations.show.joinRequests.headers.subject') }}</th>
                      <th>{{ t('admin.organizations.show.joinRequests.headers.requestedBy') }}</th>
                      <th>
                        <button
                          type="button"
                          class="sort-btn"
                          @click="onJoinRequestsSort('status')"
                        >
                          {{ t('admin.organizations.show.joinRequests.headers.status') }}
                          {{ joinRequestsSortMark('status') }}
                        </button>
                      </th>
                      <th>
                        <button
                          type="button"
                          class="sort-btn"
                          @click="onJoinRequestsSort('created_at')"
                        >
                          {{ t('admin.organizations.show.joinRequests.headers.createdAt') }}
                          {{ joinRequestsSortMark('created_at') }}
                        </button>
                      </th>
                      <th class="text-right">
                        {{ t('admin.organizations.show.joinRequests.headers.actions') }}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="request in joinRequests" :key="request.id">
                      <td class="font-mono text-xs">{{ request.id }}</td>
                      <td>{{ resolveJoinRequestTypeLabel(request.subject_type) }}</td>
                      <td>
                        <AdminLink
                          v-if="request.subject_type === 'user'"
                          :to="`/admin/users/${request.subject_id}`"
                        >
                          {{ resolveJoinRequestSubjectLabel(request) }}
                        </AdminLink>
                        <AdminLink
                          v-else-if="request.subject_type === 'child'"
                          :to="`/admin/children/${request.subject_id}`"
                        >
                          {{ resolveJoinRequestSubjectLabel(request) }}
                        </AdminLink>
                        <span v-else>{{ resolveJoinRequestSubjectLabel(request) }}</span>
                      </td>
                      <td>
                        <AdminLink
                          v-if="request.requested_by?.id"
                          :to="`/admin/users/${request.requested_by.id}`"
                        >
                          {{
                            request.requested_by?.label ||
                            request.requested_by?.email ||
                            request.requested_by?.id
                          }}
                        </AdminLink>
                        <span v-else>{{ t('common.dash') }}</span>
                      </td>
                      <td>
                        <div class="space-y-1">
                          <div>{{ resolveJoinRequestStatusLabel(request.status) }}</div>
                          <p v-if="request.review_note" class="admin-muted text-xs">
                            {{
                              t('admin.organizations.show.joinRequests.reviewNote', {
                                value: request.review_note,
                              })
                            }}
                          </p>
                        </div>
                      </td>
                      <td>{{ formatDate(request.created_at) }}</td>
                      <td>
                        <div class="flex justify-end gap-2" v-if="canWriteOrganizations">
                          <button
                            v-if="canApproveJoinRequest(request)"
                            type="button"
                            class="admin-button rounded-lg px-3 py-1.5 text-xs"
                            :disabled="reviewingRequestId === request.id"
                            @click="approveJoinRequestDirect(request.id)"
                          >
                            {{ t('admin.organizations.show.joinRequests.actions.approve') }}
                          </button>
                          <button
                            v-if="canRejectJoinRequest(request)"
                            type="button"
                            class="admin-button-secondary rounded-lg px-3 py-1.5 text-xs"
                            :disabled="reviewingRequestId === request.id"
                            @click="openJoinRequestReviewModal(request.id)"
                          >
                            {{ t('admin.organizations.show.joinRequests.actions.reject') }}
                          </button>
                        </div>
                        <span v-else class="admin-muted text-xs">{{ t('common.dash') }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>

            <template #cards>
              <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <article
                  v-for="request in joinRequests"
                  :key="request.id"
                  class="rounded-xl border border-[color:var(--admin-border)] p-4"
                >
                  <p class="font-medium">{{ resolveJoinRequestSubjectLabel(request) }}</p>
                  <p class="admin-muted mt-2 text-xs">
                    {{ t('admin.organizations.show.joinRequests.card.id', { value: request.id }) }}
                  </p>
                  <p class="admin-muted text-xs">
                    {{
                      t('admin.organizations.show.joinRequests.card.type', {
                        value: resolveJoinRequestTypeLabel(request.subject_type),
                      })
                    }}
                  </p>
                  <p class="admin-muted text-xs">
                    {{
                      t('admin.organizations.show.joinRequests.card.status', {
                        value: resolveJoinRequestStatusLabel(request.status),
                      })
                    }}
                  </p>
                  <p v-if="request.review_note" class="admin-muted text-xs">
                    {{
                      t('admin.organizations.show.joinRequests.reviewNote', {
                        value: request.review_note,
                      })
                    }}
                  </p>
                  <p class="admin-muted text-xs">
                    {{
                      t('admin.organizations.show.joinRequests.card.createdAt', {
                        value: formatDate(request.created_at),
                      })
                    }}
                  </p>
                  <p class="admin-muted text-xs">
                    {{
                      t('admin.organizations.show.joinRequests.card.requestedBy', {
                        value:
                          request.requested_by?.label ||
                          request.requested_by?.email ||
                          request.requested_by?.id ||
                          t('common.dash'),
                      })
                    }}
                  </p>

                  <div v-if="canWriteOrganizations" class="mt-3 flex gap-2">
                    <button
                      v-if="canApproveJoinRequest(request)"
                      type="button"
                      class="admin-button rounded-lg px-3 py-1.5 text-xs"
                      :disabled="reviewingRequestId === request.id"
                      @click="approveJoinRequestDirect(request.id)"
                    >
                      {{ t('admin.organizations.show.joinRequests.actions.approve') }}
                    </button>
                    <button
                      v-if="canRejectJoinRequest(request)"
                      type="button"
                      class="admin-button-secondary rounded-lg px-3 py-1.5 text-xs"
                      :disabled="reviewingRequestId === request.id"
                      @click="openJoinRequestReviewModal(request.id)"
                    >
                      {{ t('admin.organizations.show.joinRequests.actions.reject') }}
                    </button>
                  </div>
                </article>
              </div>
            </template>
          </AdminContentView>

          <AdminPagination
            :visible="joinRequestsPagination.total > joinRequestsPagination.per_page"
            :current-page="joinRequestsPagination.current_page"
            :last-page="joinRequestsPagination.last_page"
            :per-page="joinRequestsPagination.per_page"
            :items="joinRequestsPaginationItems"
            :loading="joinRequestsLoading"
            @page="fetchJoinRequests"
          />
        </template>
      </div>
    </article>

    <UiModal
      v-model="joinRequestReviewModalOpen"
      mode="confirm"
      :title="t('admin.organizations.show.joinRequests.reviewModal.title')"
      :confirm-label="t('admin.organizations.show.joinRequests.actions.reject')"
      :cancel-label="t('common.cancel')"
      :loading-label="t('common.loading')"
      :confirm-loading="Boolean(reviewingRequestId)"
      :confirm-disabled="joinRequestReviewConfirmDisabled"
      :destructive="true"
      @confirm="confirmJoinRequestReview"
      @cancel="closeJoinRequestReviewModal"
      @close="closeJoinRequestReviewModal"
    >
      <div class="space-y-4">
        <p class="admin-muted text-sm">
          {{
            t('admin.organizations.show.joinRequests.reviewModal.requestLabel', {
              value: joinRequestReviewTarget?.id || t('common.dash'),
            })
          }}
        </p>

        <UiTextarea
          v-model="joinRequestReviewNote"
          :label="t('admin.organizations.show.joinRequests.reviewModal.noteLabel')"
          :placeholder="t('admin.organizations.show.joinRequests.reviewModal.notePlaceholder')"
          :rows="3"
          :disabled="Boolean(reviewingRequestId)"
          :error="joinRequestReviewNoteError"
        />
      </div>
    </UiModal>

    <AdminChangeLogPanel
      v-if="canReadChangeLog"
      model="organization"
      :entity-id="organization?.id || String(route.params.id || '')"
      @rolled-back="onRolledBack"
    />

    <AdminActionLogPanel model="organization" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import AdminActionLogPanel from '~/components/admin/ActionLog/AdminActionLogPanel.vue';
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import AdminLink from '~/components/admin/AdminLink/AdminLink.vue';
import AdminContentView from '~/components/admin/Listing/AdminContentView/AdminContentView.vue';
import AdminListToolbar from '~/components/admin/Listing/AdminListToolbar/AdminListToolbar.vue';
import AdminPagination from '~/components/admin/Listing/AdminPagination/AdminPagination.vue';
import AdminTagFilter from '~/components/admin/Listing/AdminTagFilter/AdminTagFilter.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import UiTextarea from '~/components/ui/FormControls/UiTextarea/UiTextarea.vue';
import UiModal from '~/components/ui/Modal/UiModal.vue';
import type { PaginationPayload, SortDirection } from '~/composables/useAdminCrudCommon';
import { buildPaginationItems, getApiErrorMessage } from '~/composables/useAdminCrudCommon';
import type {
  AdminOrganization,
  OrganizationOwnershipStatus,
  OrganizationSourceType,
  OrganizationStatus,
} from '~/composables/useAdminOrganizations';
import { getAdminOrganizationOwnerName } from '~/composables/useAdminOrganizations';
import type {
  OrganizationMember,
  OrganizationMemberStatus,
} from '~/composables/useOrganizationMembers';
import {
  resolveOrganizationMemberLabel,
  useOrganizationMembers,
} from '~/composables/useOrganizationMembers';
import type {
  OrganizationClient,
  OrganizationClientStatus,
} from '~/composables/useOrganizationClients';
import {
  resolveOrganizationClientLabel,
  useOrganizationClients,
} from '~/composables/useOrganizationClients';
import type {
  OrganizationJoinRequest,
  OrganizationJoinRequestStatus,
  OrganizationJoinRequestSubjectType,
} from '~/composables/useOrganizationJoinRequests';
import {
  resolveOrganizationJoinRequestSubjectLabel,
  useOrganizationJoinRequests,
} from '~/composables/useOrganizationJoinRequests';

const { t, locale } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'org.company.profile.read',
});

const route = useRoute();
const organizationsApi = useAdminOrganizations();
const organizationMembersApi = useOrganizationMembers();
const organizationClientsApi = useOrganizationClients();
const organizationJoinRequestsApi = useOrganizationJoinRequests();
const { hasPermission } = usePermissions();

const canWriteOrganizations = computed(() => hasPermission('org.company.profile.update'));
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));

const listPerPageOptions = [10, 20, 50];
type ContentMode = 'table' | 'table-cards' | 'cards';

const modeOptions = computed(() => [
  { value: 'table', label: t('admin.entity.modes.table') },
  { value: 'table-cards', label: t('admin.entity.modes.tableCards') },
  { value: 'cards', label: t('admin.entity.modes.cards') },
]);

const resolveNextMode = (value: string | number | (string | number)[]): ContentMode | null => {
  const nextValue = Array.isArray(value) ? value[0] : value;
  if (nextValue === 'table' || nextValue === 'table-cards' || nextValue === 'cards') {
    return nextValue;
  }

  return null;
};

const onMembersModeChange = (value: string | number | (string | number)[]) => {
  const nextMode = resolveNextMode(value);
  if (nextMode) {
    membersViewMode.value = nextMode;
  }
};

const onClientsModeChange = (value: string | number | (string | number)[]) => {
  const nextMode = resolveNextMode(value);
  if (nextMode) {
    clientsViewMode.value = nextMode;
  }
};

const onJoinRequestsModeChange = (value: string | number | (string | number)[]) => {
  const nextMode = resolveNextMode(value);
  if (nextMode) {
    joinRequestsViewMode.value = nextMode;
  }
};

const organization = ref<AdminOrganization | null>(null);
const loading = ref(false);
const loadError = ref('');

const members = ref<OrganizationMember[]>([]);
const membersLoading = ref(false);
const membersError = ref('');
const membersSearchInput = ref('');
const membersSearchApplied = ref('');
const membersPerPage = ref(10);
const membersPage = ref(1);
const membersSortBy = ref<'created_at' | 'joined_at' | 'position' | 'status' | 'id'>('created_at');
const membersSortDir = ref<SortDirection>('desc');
const membersStatusFilters = ref<string[]>([]);
const membersViewMode = ref<ContentMode>('table');
const membersTableOnDesktop = ref(true);
const membersPagination = reactive<PaginationPayload<OrganizationMember>>({
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 0,
  total: 0,
});

const clients = ref<OrganizationClient[]>([]);
const clientsLoading = ref(false);
const clientsError = ref('');
const clientsSearchInput = ref('');
const clientsSearchApplied = ref('');
const clientsPerPage = ref(10);
const clientsPage = ref(1);
const clientsSortBy = ref<'created_at' | 'joined_at' | 'status' | 'id' | 'subject_type'>(
  'created_at'
);
const clientsSortDir = ref<SortDirection>('desc');
const clientsStatusFilters = ref<string[]>([]);
const clientsTypeFilters = ref<string[]>([]);
const clientsViewMode = ref<ContentMode>('table');
const clientsTableOnDesktop = ref(true);
const clientsPagination = reactive<PaginationPayload<OrganizationClient>>({
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 0,
  total: 0,
});

const joinRequests = ref<OrganizationJoinRequest[]>([]);
const joinRequestsLoading = ref(false);
const joinRequestsError = ref('');
const reviewingRequestId = ref<string | null>(null);
const joinRequestReviewModalOpen = ref(false);
const joinRequestReviewRequestId = ref<string | null>(null);
const joinRequestReviewNote = ref('');
const joinRequestReviewNoteError = ref('');
const joinRequestsSearchInput = ref('');
const joinRequestsSearchApplied = ref('');
const joinRequestsPerPage = ref(10);
const joinRequestsPage = ref(1);
const joinRequestsSortBy = ref<'created_at' | 'reviewed_at' | 'status' | 'id' | 'subject_type'>(
  'created_at'
);
const joinRequestsSortDir = ref<SortDirection>('desc');
const joinRequestsStatusFilters = ref<string[]>([]);
const joinRequestsTypeFilters = ref<string[]>([]);
const joinRequestsViewMode = ref<ContentMode>('table');
const joinRequestsTableOnDesktop = ref(true);
const joinRequestsPagination = reactive<PaginationPayload<OrganizationJoinRequest>>({
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 0,
  total: 0,
});

const memberStatusFilterOptions = computed(() => [
  { value: 'active', label: t('admin.organizations.show.members.statuses.active') },
  { value: 'invited', label: t('admin.organizations.show.members.statuses.invited') },
  { value: 'blocked', label: t('admin.organizations.show.members.statuses.blocked') },
]);

const clientStatusFilterOptions = computed(() => [
  { value: 'active', label: t('admin.organizations.show.clients.statuses.active') },
  { value: 'left', label: t('admin.organizations.show.clients.statuses.left') },
  { value: 'blocked', label: t('admin.organizations.show.clients.statuses.blocked') },
]);

const clientTypeFilterOptions = computed(() => [
  { value: 'user', label: t('admin.organizations.show.joinRequests.types.user') },
  { value: 'child', label: t('admin.organizations.show.joinRequests.types.child') },
]);

const joinRequestStatusFilterOptions = computed(() => [
  { value: 'pending', label: t('admin.organizations.show.joinRequests.statuses.pending') },
  { value: 'approved', label: t('admin.organizations.show.joinRequests.statuses.approved') },
  { value: 'rejected', label: t('admin.organizations.show.joinRequests.statuses.rejected') },
]);

const joinRequestTypeFilterOptions = computed(() => [
  { value: 'user', label: t('admin.organizations.show.joinRequests.types.user') },
  { value: 'child', label: t('admin.organizations.show.joinRequests.types.child') },
]);

const membersPaginationItems = computed(() =>
  buildPaginationItems(membersPagination.current_page, membersPagination.last_page, 5)
);
const clientsPaginationItems = computed(() =>
  buildPaginationItems(clientsPagination.current_page, clientsPagination.last_page, 5)
);
const joinRequestsPaginationItems = computed(() =>
  buildPaginationItems(joinRequestsPagination.current_page, joinRequestsPagination.last_page, 5)
);
const joinRequestReviewTarget = computed(() => {
  const targetId = joinRequestReviewRequestId.value;
  if (!targetId) {
    return null;
  }

  return joinRequests.value.find((item) => item.id === targetId) || null;
});
const joinRequestReviewConfirmDisabled = computed(() => {
  if (!joinRequestReviewRequestId.value) {
    return true;
  }
  return false;
});

const formatDate = (date: string | null | undefined): string => {
  if (!date) {
    return t('common.dash');
  }

  const parsed = new Date(date);
  if (Number.isNaN(parsed.getTime())) {
    return date;
  }

  return new Intl.DateTimeFormat(locale.value, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(parsed);
};

const formatCoordinates = (
  lat: number | null | undefined,
  lng: number | null | undefined
): string => {
  if (typeof lat !== 'number' || typeof lng !== 'number') {
    return t('common.dash');
  }

  return `${lat}, ${lng}`;
};

const resolveStatusLabel = (status: OrganizationStatus | null | undefined): string => {
  if (!status) {
    return t('common.dash');
  }

  return t(`admin.organizations.status.${status}`);
};

const resolveOwnershipLabel = (status: OrganizationOwnershipStatus | null | undefined): string => {
  if (!status) {
    return t('common.dash');
  }

  return t(`admin.organizations.ownership.${status}`);
};

const resolveSourceLabel = (source: OrganizationSourceType | null | undefined): string => {
  if (!source) {
    return t('common.dash');
  }

  const sourceMap: Record<OrganizationSourceType, string> = {
    manual: 'admin.organizations.source.manual',
    import: 'admin.organizations.source.import',
    parsed: 'admin.organizations.source.parsed',
    self_registered: 'admin.organizations.source.selfRegistered',
  };

  return t(sourceMap[source]);
};

const resolveTravelModeLabel = (mode: 'walk' | 'drive' | null | undefined): string => {
  if (!mode) {
    return t('common.dash');
  }

  return t(`admin.organizations.travelMode.${mode}`);
};

const resolveOwnerLabel = (item: AdminOrganization): string =>
  getAdminOrganizationOwnerName(item) || t('common.dash');
const resolveMemberLabel = (member: OrganizationMember): string =>
  resolveOrganizationMemberLabel(member);
const resolveMemberStatusLabel = (status: OrganizationMemberStatus): string =>
  t(`admin.organizations.show.members.statuses.${status}`);
const resolveClientLabel = (client: OrganizationClient): string =>
  resolveOrganizationClientLabel(client);
const resolveClientStatusLabel = (status: OrganizationClientStatus): string =>
  t(`admin.organizations.show.clients.statuses.${status}`);
const resolveJoinRequestSubjectLabel = (request: OrganizationJoinRequest): string =>
  resolveOrganizationJoinRequestSubjectLabel(request);
const resolveJoinRequestTypeLabel = (type: OrganizationJoinRequestSubjectType): string =>
  t(`admin.organizations.show.joinRequests.types.${type}`);
const resolveJoinRequestStatusLabel = (status: OrganizationJoinRequestStatus): string =>
  t(`admin.organizations.show.joinRequests.statuses.${status}`);
const canApproveJoinRequest = (request: OrganizationJoinRequest): boolean =>
  request.status !== 'approved';
const canRejectJoinRequest = (request: OrganizationJoinRequest): boolean =>
  request.status !== 'rejected';

const createSortMark = (currentBy: string, currentDir: SortDirection, column: string): string => {
  if (currentBy !== column) {
    return '';
  }

  return currentDir === 'asc' ? '▲' : '▼';
};

const membersSortMark = (column: string): string =>
  createSortMark(membersSortBy.value, membersSortDir.value, column);
const clientsSortMark = (column: string): string =>
  createSortMark(clientsSortBy.value, clientsSortDir.value, column);
const joinRequestsSortMark = (column: string): string =>
  createSortMark(joinRequestsSortBy.value, joinRequestsSortDir.value, column);

const onMembersSort = (column: 'created_at' | 'joined_at' | 'position' | 'status' | 'id') => {
  if (membersSortBy.value === column) {
    membersSortDir.value = membersSortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    membersSortBy.value = column;
    membersSortDir.value = 'asc';
  }
  void fetchMembers(1);
};

const onClientsSort = (column: 'created_at' | 'joined_at' | 'status' | 'id' | 'subject_type') => {
  if (clientsSortBy.value === column) {
    clientsSortDir.value = clientsSortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    clientsSortBy.value = column;
    clientsSortDir.value = 'asc';
  }
  void fetchClients(1);
};

const onJoinRequestsSort = (
  column: 'created_at' | 'reviewed_at' | 'status' | 'id' | 'subject_type'
) => {
  if (joinRequestsSortBy.value === column) {
    joinRequestsSortDir.value = joinRequestsSortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    joinRequestsSortBy.value = column;
    joinRequestsSortDir.value = 'asc';
  }
  void fetchJoinRequests(1);
};

const fetchMembers = async (page = membersPage.value) => {
  const id = String(route.params.id || '');
  if (!id) return;

  membersLoading.value = true;
  membersError.value = '';

  try {
    const response = await organizationMembersApi.list(id, {
      page,
      per_page: membersPerPage.value,
      search: membersSearchApplied.value || undefined,
      status: membersStatusFilters.value[0] as OrganizationMemberStatus | undefined,
      sort_by: membersSortBy.value,
      sort_dir: membersSortDir.value,
    });

    members.value = response.data;
    membersPage.value = response.current_page;
    membersPagination.current_page = response.current_page;
    membersPagination.last_page = response.last_page;
    membersPagination.per_page = response.per_page;
    membersPagination.total = response.total;
  } catch (error) {
    membersError.value = getApiErrorMessage(
      error,
      t('admin.organizations.show.members.errors.load')
    );
  } finally {
    membersLoading.value = false;
  }
};

const fetchClients = async (page = clientsPage.value) => {
  const id = String(route.params.id || '');
  if (!id) return;

  clientsLoading.value = true;
  clientsError.value = '';

  try {
    const response = await organizationClientsApi.list(id, {
      page,
      per_page: clientsPerPage.value,
      search: clientsSearchApplied.value || undefined,
      status: clientsStatusFilters.value[0] as OrganizationClientStatus | undefined,
      subject_type: clientsTypeFilters.value[0] as OrganizationJoinRequestSubjectType | undefined,
      sort_by: clientsSortBy.value,
      sort_dir: clientsSortDir.value,
    });

    clients.value = response.data;
    clientsPage.value = response.current_page;
    clientsPagination.current_page = response.current_page;
    clientsPagination.last_page = response.last_page;
    clientsPagination.per_page = response.per_page;
    clientsPagination.total = response.total;
  } catch (error) {
    clientsError.value = getApiErrorMessage(
      error,
      t('admin.organizations.show.clients.errors.load')
    );
  } finally {
    clientsLoading.value = false;
  }
};

const fetchJoinRequests = async (page = joinRequestsPage.value) => {
  const id = String(route.params.id || '');
  if (!id) return;

  joinRequestsLoading.value = true;
  joinRequestsError.value = '';

  try {
    const response = await organizationJoinRequestsApi.list(id, {
      page,
      per_page: joinRequestsPerPage.value,
      status: joinRequestsStatusFilters.value[0] as OrganizationJoinRequestStatus | undefined,
      subject_type: joinRequestsTypeFilters.value[0] as
        | OrganizationJoinRequestSubjectType
        | undefined,
      search: joinRequestsSearchApplied.value || undefined,
      sort_by: joinRequestsSortBy.value,
      sort_dir: joinRequestsSortDir.value,
    });

    joinRequests.value = response.data;
    joinRequestsPage.value = response.current_page;
    joinRequestsPagination.current_page = response.current_page;
    joinRequestsPagination.last_page = response.last_page;
    joinRequestsPagination.per_page = response.per_page;
    joinRequestsPagination.total = response.total;
  } catch (error) {
    joinRequestsError.value = getApiErrorMessage(
      error,
      t('admin.organizations.show.joinRequests.errors.load')
    );
  } finally {
    joinRequestsLoading.value = false;
  }
};

const onMembersApplySearch = () => {
  membersSearchApplied.value = membersSearchInput.value.trim();
  void fetchMembers(1);
};
const onMembersPerPageChange = (value: number) => {
  membersPerPage.value = value;
  void fetchMembers(1);
};
const onMembersStatusFilterChange = () => void fetchMembers(1);
const onMembersReset = () => {
  membersSearchInput.value = '';
  membersSearchApplied.value = '';
  membersPerPage.value = 10;
  membersSortBy.value = 'created_at';
  membersSortDir.value = 'desc';
  membersStatusFilters.value = [];
  void fetchMembers(1);
};

const onClientsApplySearch = () => {
  clientsSearchApplied.value = clientsSearchInput.value.trim();
  void fetchClients(1);
};
const onClientsPerPageChange = (value: number) => {
  clientsPerPage.value = value;
  void fetchClients(1);
};
const onClientsFiltersChange = () => void fetchClients(1);
const onClientsReset = () => {
  clientsSearchInput.value = '';
  clientsSearchApplied.value = '';
  clientsPerPage.value = 10;
  clientsSortBy.value = 'created_at';
  clientsSortDir.value = 'desc';
  clientsStatusFilters.value = [];
  clientsTypeFilters.value = [];
  void fetchClients(1);
};

const onJoinRequestsApplySearch = () => {
  joinRequestsSearchApplied.value = joinRequestsSearchInput.value.trim();
  void fetchJoinRequests(1);
};
const onJoinRequestsPerPageChange = (value: number) => {
  joinRequestsPerPage.value = value;
  void fetchJoinRequests(1);
};
const onJoinRequestsFiltersChange = () => void fetchJoinRequests(1);
const onJoinRequestsReset = () => {
  joinRequestsSearchInput.value = '';
  joinRequestsSearchApplied.value = '';
  joinRequestsPerPage.value = 10;
  joinRequestsSortBy.value = 'created_at';
  joinRequestsSortDir.value = 'desc';
  joinRequestsStatusFilters.value = [];
  joinRequestsTypeFilters.value = [];
  void fetchJoinRequests(1);
};

const approveJoinRequestDirect = async (requestId: string) => {
  const id = String(route.params.id || '');
  if (!id || !requestId) return;

  reviewingRequestId.value = requestId;
  try {
    await organizationJoinRequestsApi.approve(id, requestId, {
      review_note: null,
    });
    await Promise.all([fetchJoinRequests(joinRequestsPage.value), fetchClients(clientsPage.value)]);
  } catch (error) {
    joinRequestsError.value = getApiErrorMessage(
      error,
      t('admin.organizations.show.joinRequests.errors.review')
    );
  } finally {
    reviewingRequestId.value = null;
  }
};

const openJoinRequestReviewModal = (requestId: string) => {
  joinRequestReviewRequestId.value = requestId;
  joinRequestReviewNote.value = '';
  joinRequestReviewNoteError.value = '';
  joinRequestReviewModalOpen.value = true;
};

const closeJoinRequestReviewModal = () => {
  joinRequestReviewModalOpen.value = false;
  joinRequestReviewRequestId.value = null;
  joinRequestReviewNote.value = '';
  joinRequestReviewNoteError.value = '';
};

const confirmJoinRequestReview = async () => {
  const id = String(route.params.id || '');
  const requestId = joinRequestReviewRequestId.value;
  if (!id || !requestId) return;

  joinRequestReviewNoteError.value = '';

  const reviewNote = joinRequestReviewNote.value.trim();
  reviewingRequestId.value = requestId;
  try {
    await organizationJoinRequestsApi.reject(id, requestId, {
      review_note: reviewNote || null,
    });

    await Promise.all([fetchJoinRequests(joinRequestsPage.value), fetchClients(clientsPage.value)]);
    closeJoinRequestReviewModal();
  } catch (error) {
    joinRequestsError.value = getApiErrorMessage(
      error,
      t('admin.organizations.show.joinRequests.errors.review')
    );
  } finally {
    reviewingRequestId.value = null;
  }
};

const fetchOrganization = async () => {
  const id = String(route.params.id || '');
  if (!id) {
    loadError.value = t('admin.organizations.show.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    organization.value = await organizationsApi.show(id);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.organizations.show.errors.load'));
  } finally {
    loading.value = false;
  }
};

const onRolledBack = async () => {
  await fetchOrganization();
};

onMounted(async () => {
  await Promise.all([fetchOrganization(), fetchMembers(), fetchClients(), fetchJoinRequests()]);
});
</script>
