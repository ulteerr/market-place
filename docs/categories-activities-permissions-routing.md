# Categories Activities Permissions And Routing

## Scope
Документ фиксирует permission contract и routing contract для:
- `categories`
- `activities`
- `leads`

## Permission Contract

### Admin Categories
Permission keys:
- `admin.categories.read`
- `admin.categories.create`
- `admin.categories.update`
- `admin.categories.delete`

Usage:
- route-level middleware в admin routes;
- `CategoryPolicy` повторно подтверждает доступ на CRUD-операциях.

### Admin Activities
Permission keys:
- `admin.activities.read`
- `admin.activities.create`
- `admin.activities.update`
- `admin.activities.delete`

Usage:
- route-level middleware в admin routes;
- `ActivityPolicy` повторно подтверждает доступ на CRUD-операциях.

### Activity Leads
Admin:
- `admin.activity-leads.read`
- `admin.activity-leads.update`

Organization:
- `org.activity-leads.read`
- `org.activity-leads.update`

Usage:
- admin lead routes режутся через explicit admin permission keys;
- organization access идет через `OrganizationPolicy`:
  - `viewActivityLeads`
  - `reviewActivityLeads`

## Routing Contract

### Categories

#### Admin
- `GET /api/admin/categories/slug-preview`
- `GET /api/admin/categories/tree`
- `GET /api/admin/categories`
- `POST /api/admin/categories`
- `GET /api/admin/categories/{id}`
- `PATCH /api/admin/categories/{id}`
- `DELETE /api/admin/categories/{id}`

#### Public
- `GET /api/categories/roots`
- `GET /api/categories/tree`
- `GET /api/categories/{parentId}/children`

Notes:
- public routes не требуют auth;
- admin routes требуют:
  - `auth:sanctum`
  - `admin.panel.access`
  - соответствующий `admin.categories.*`

### Activities

#### Admin
- `GET /api/admin/activities/slug-preview`
- `GET /api/admin/activities`
- `POST /api/admin/activities`
- `GET /api/admin/activities/{id}`
- `PATCH /api/admin/activities/{id}`
- `DELETE /api/admin/activities/{id}`

#### Public Read
- `GET /api/activities`
- `GET /api/activities/feed`
- `GET /api/activities/featured`
- `GET /api/activities/{publicKey}`

Notes:
- public read routes не требуют auth;
- admin routes требуют:
  - `auth:sanctum`
  - `admin.panel.access`
  - соответствующий `admin.activities.*`

### Leads

#### Public Submit
- `POST /api/activities/{activityId}/leads`

Rules:
- требует `auth:sanctum`;
- пользователь подает заявку за себя или за своего ребенка.

#### Organization Review
- `GET /api/organizations/{organizationId}/activity-leads`
- `PATCH /api/organizations/{organizationId}/activity-leads/{leadId}/status`

Rules:
- требует `auth:sanctum`;
- доступ ограничен organization scope через policy.

#### Admin Review
- `GET /api/admin/activity-leads`
- `PATCH /api/admin/activity-leads/{leadId}/status`

Rules:
- требует:
  - `auth:sanctum`
  - `admin.panel.access`
  - соответствующий `admin.activity-leads.*`

## Public URL Contract

### Category Pages
- `/catalog/{root-category-slug}`
- `/catalog/{root-category-slug}/{leaf-category-slug}`

### Activity Pages
- canonical:
  - `/{root-category-slug}/{leaf-category-slug}/{activity-slug}-{uuid}`
- fallback:
  - `/activities/{activity-slug}-{uuid}`
  - используется только как redirect на canonical nested route

### Why UUID-backed Key
- `activities.slug` не обязан быть глобально уникальным;
- route lookup опирается на `uuid`, а не на один `slug`;
- `slug` остается человекочитаемой частью URL.

## Responsibility Split

### Categories
- navigation tree
- menu/filter/category landing pages
- root/leaf selection for activities

### Activities
- cards
- feed
- detail pages
- schedules
- media

### Leads
- application/contact flow вокруг `activity`
- не отдельный самостоятельный public каталог

## Contract Changelog
- 2026-03-18:
  - зафиксированы отдельные permission keys для `admin.categories.*`;
  - зафиксированы отдельные permission keys для `admin.activities.*`;
  - зафиксированы отдельные permission keys для `admin.activity-leads.*` и `org.activity-leads.*`;
  - подтвержден public route contract для category pages и activity pages.
