# Public Activity SEO And Schema Contract

## Scope
Документ фиксирует SEO/schema contract для public activity pages.

Целевая страница:
- `/{root-category-slug}/{leaf-category-slug}/{activity-slug}-{uuid}`

## Canonical Routing

### Canonical URL
- canonical route:
  - `/{root-category-slug}/{leaf-category-slug}/{activity-slug}-{uuid}`
- canonical path собирается через `buildPublicActivityPath(...)`

### Fallback Route
- поддерживается fallback:
  - `/activities/{activity-slug}-{uuid}`
- fallback route не считается canonical;
- он используется только для редиректа на nested public path.

### Why
- `slug` не является globally unique;
- canonical route включает `uuid-backed` public key и category context;
- это стабилизирует SEO path и routing resolution одновременно.

## Meta Contract

### H1
- `h1 = activity.name`

### Title
- title собирается из `activity.name`
- pattern:
  - activity-specific SEO title

### Description
Приоритет:
1. `activity.description`
2. `activity.short_description`
3. fallback public text

### Image
Приоритет:
1. `activity.cover.url`
2. первый элемент `activity.gallery`
3. отсутствие image meta

### Output
Через `usePublicPageSeo(...)` / `usePublicSeo(...)` страница публикует:
- `<title>`
- `meta[name="description"]`
- `link[rel="canonical"]`
- `meta[property="og:image"]`
- `meta[name="twitter:image"]`

## JSON-LD Contract

### Schema Strategy
Для activity page публикуются:
- `BreadcrumbList`
- `Service`

### Why `Service`
- секция/кружок/занятие в первом релизе моделируется как услуга;
- это корректнее, чем:
  - `Product`
  - `Event`

### `BreadcrumbList`
Минимальная цепочка:
- `Главная`
- `Каталог`
- `Activity`

### `Service` Node
В schema включаются:
- `name`
- `description`
- `url`
- `image`
- `category`
- `provider`
- `areaServed`
- `audience`
- `offers`

### Field Mapping
- `name` <- `activity.name`
- `description` <- `activity.description || activity.short_description`
- `url` <- canonical public activity URL
- `image` <- `cover + gallery`
- `category` <- `root / leaf`
- `provider.name` <- `activity.organization.name`
- `areaServed.name` <- `activity.location.city.name`
- `audience.suggestedMinAge` <- `activity.min_age`
- `audience.suggestedMaxAge` <- `activity.max_age`
- `offers.price` <- `activity.price_from`
- `offers.priceCurrency` <- `activity.currency || RUB`

## Registry Rules

### Public Schema Registry
- schema nodes публикуются только на public routes;
- private prefixes исключены:
  - `/admin`
  - `/account`
  - `/organizations`

### Activity Page Registration
- activity page регистрирует nodes через `usePublicSchemaNode(...)`
- activity schema nodes строятся через `buildPublicActivitySchemaNodes(...)`

## Runtime Source Of Truth

### Page
- `frontend/pages/[root]/[leaf]/[activity].vue`

### SEO Helpers
- `frontend/composables/seo/usePublicPageSeo.ts`
- `frontend/composables/seo/usePublicSeo.ts`

### Schema Helpers
- `frontend/composables/schema/public-activity-schema.ts`
- `frontend/composables/schema/usePublicSchemaRegistry.ts`

## Test Notes

Контракт уже подтверждается тестами:
- unit:
  - `frontend/tests/unit/composables/schema/public-activity-schema.spec.ts`
  - `frontend/tests/unit/composables/schema/usePublicSchemaRegistry.spec.ts`
- e2e / SEO gate:
  - `frontend/tests/e2e/seo/public-ssr-seo.spec.ts`

## Contract Changelog
- 2026-03-18:
  - canonical route зафиксирован как nested category + uuid-backed activity key;
  - schema strategy закреплена как `BreadcrumbList + Service`;
  - meta image fallback закреплен как `cover -> first gallery image`.
