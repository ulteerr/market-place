# Activities Module

## Scope
Документ фиксирует текущий контракт модуля `Activities` в первом релизе marketplace.

`activities` отвечают за:
- public cards;
- catalog/feed;
- detail pages;
- schedules;
- media;
- lead/application flow.

## Status Model

### `activities.status`
- `draft`
- `pending_review`
- `published`
- `archived`

### Semantics
- `draft`: черновик, не показывается в public.
- `pending_review`: подготовлен к модерации.
- `published`: доступен в public read endpoints.
- `archived`: снят с витрины и не участвует в public feed.

## Relation With Categories

### Runtime Contract
- у `activity` одна обязательная primary leaf-category;
- root category напрямую не назначается;
- связь хранится через `activity_categories`;
- `root -> leaf` для URL/UI восстанавливается через `parent` leaf-категории.

### Public Route Contract
- `/{root-category-slug}/{leaf-category-slug}/{activity-slug}-{uuid}`

## Schedules

### `activity_schedules`
- `activity_id`
- `day_of_week`
- `start_time`
- `end_time`

### Rules
- `day_of_week` в диапазоне `1..7`;
- `end_time > start_time`;
- duplicate slot для одной `activity` запрещен;
- на update расписание синхронизируется полным набором.

## Media Contract

### Storage Ownership
- модуль `Activities` не хранит отдельную media-таблицу;
- media идет через существующий `Files` module;
- базовая таблица: `files`;
- ordering/reference layer: `file_references`.

### Collections
- `cover`
  - single-file collection;
  - attach/replace/remove через `FilesService`
- `gallery`
  - ordered multi-file collection;
  - attach/remove/reorder через `ActivityMediaService`

### Runtime Binding
- `cover`
  - хранится как morph attachment в `files.fileable_*`
  - collection = `cover`
- `gallery`
  - file entries создаются в `files`
  - live ownership/order хранится через `file_references`
  - collection = `gallery`
  - порядок хранится в `file_references.meta.order`

### Cleanup Rules
- удаление `cover` отвязывает файл от `activity`;
- удаление gallery item:
  - пересобирает references;
  - обновляет order;
  - удаляет физический файл только если он больше нигде не используется;
- удаление `activity` вызывает purge media lifecycle.

## Public Feed Contract

### Read Endpoints
- `GET /api/activities`
- `GET /api/activities/feed`
- `GET /api/activities/featured`
- `GET /api/activities/{publicKey}`

### Feed
- `feed` — cursor-based contract для infinite loading;
- response содержит:
  - `items`
  - `next_cursor`
- admin-style `page/per_page/total` для `feed` не используется.

## Operational Notes
- миграции `activities`, `activity_schedules`, `leads`, `activity_categories` остаются в `backend/database/migrations`;
- backend module живет в `backend/app/Modules/Activities`;
- swagger source of truth:
  - `docker/swagger/modules/activities.yaml`
  - `docker/swagger/schemas/activity.yaml`

## Contract Changelog
- 2026-03-18:
  - зафиксирована status model первого релиза;
  - подтвержден category contract `root -> leaf`;
  - зафиксирован media-binding через `Files` module;
  - подтвержден cursor-based public feed contract.
