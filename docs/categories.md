# Categories Module

## Scope
Документ фиксирует текущий контракт модуля `Categories` в первом релизе marketplace.

`categories` отвечают за:
- рубрикатор;
- public navigation;
- category landing pages;
- выбор `root -> leaf` пары для `activities`.

`categories` не отвечают за карточки витрины. Карточки и public feed строятся на сущности `activities`.

## Final Data Model

### `categories`
- `id` (`uuid`)
- `name`
- `slug`
- `parent_id` (`uuid`, nullable)
- `sort_order`
- `is_active`
- `created_at`
- `updated_at`

### Slug Rules
- `slug` формируется из `name` через транслитерацию.
- Для root-категорий `slug` уникален среди `parent_id = null`.
- Для child-категорий `slug` уникален внутри своего `parent_id`.

## Tree Structure

### Supported Shape In Release 1
- `root -> leaf`
- минимум 2 уровня для сценария привязки `activity`
- более глубокая вложенность в UX первого релиза запрещена

### Semantics
- root category:
  - верхний раздел навигации;
  - может иметь дочерние категории;
  - не назначается напрямую на `activity`
- leaf category:
  - конечная категория выбора;
  - назначается на `activity`

## Business Constraints

### Parent Rules
- категория не может быть своим собственным родителем;
- категорию нельзя переместить под собственного потомка;
- вложенность глубже двух уровней запрещена;
- child-категория может иметь только root-родителя.

### Activity Assignment Rules
- `activity` обязана иметь одну leaf-category;
- root category напрямую к `activity` не привязывается;
- связь хранится через `activity_categories`;
- в первом релизе у `activity` только одна обязательная primary leaf-category.

### Delete Rules
- категорию нельзя удалить, если у нее есть дочерние категории;
- категорию нельзя удалить, если она уже используется в `activity_categories`.

### Active State Rules
- деактивация родителя запрещена, если у него есть активные дочерние категории;
- public navigation по умолчанию использует только `is_active = true`.

## API Surface

### Admin
- `GET /api/admin/categories/slug-preview`
- `GET /api/admin/categories/tree`
- `GET /api/admin/categories`
- `POST /api/admin/categories`
- `GET /api/admin/categories/{id}`
- `PATCH /api/admin/categories/{id}`
- `DELETE /api/admin/categories/{id}`

### Public
- `GET /api/categories/roots`
- `GET /api/categories/tree`
- `GET /api/categories/{parentId}/children`

## Relation With Activities

### Runtime Contract
- `categories` дают:
  - navigation tree;
  - category filters;
  - SEO-ready category pages;
  - admin category selection
- `activities` дают:
  - public cards;
  - public feed;
  - detail pages;
  - lead/application flow

### URL Contract
- public activity route:
  - `/{root-category-slug}/{leaf-category-slug}/{activity-slug}-{uuid}`
- root и leaf сегменты восстанавливаются через assigned leaf-category и ее `parent`.

## Operational Notes
- миграция `categories` остается в `backend/database/migrations` как часть общего доменного слоя;
- backend module живет в `backend/app/Modules/Categories`;
- `swagger` source of truth:
  - `docker/swagger/modules/categories.yaml`
  - `docker/swagger/schemas/category.yaml`

## Contract Changelog
- 2026-03-17:
  - зафиксирован контракт `root -> leaf`;
  - подтверждено, что `activity` хранит одну primary leaf-category;
  - подтверждено разделение ответственности: `categories = navigation`, `activities = cards/feed`.
