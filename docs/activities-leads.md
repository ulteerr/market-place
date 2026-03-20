# Activities Leads Flow

## Scope
Документ фиксирует `leads` / application flow как часть первого релиза модуля `Activities`.

`lead` — это заявка на активность, поданная авторизованным пользователем за себя или за своего ребенка.

## Final Data Model

### `leads`
- `id` (`uuid`)
- `activity_id`
- `user_id`
- `child_id` (`nullable`)
- `request_for_type`
- `contact_channels`
- `contact_payload`
- `message`
- `status`
- `created_at`
- `updated_at`

## Request Subject Model

### `request_for_type`
- `self`
- `child`

### Rules
- `self`
  - заявка подается за самого пользователя;
  - `child_id = null`
- `child`
  - заявка подается за ребенка;
  - `child_id` обязателен;
  - ребенок должен принадлежать текущему пользователю

## Contact Contract

### `contact_channels`
Допустимые значения:
- `any`
- `chat`
- `phone`
- `telegram`
- `whatsapp`
- `max`

### `contact_payload`
Поддерживаемые ключи:
- `phone`
- `telegram`
- `whatsapp`
- `max`

### Reachability Rules
- должен быть выбран хотя бы один `contact channel`;
- если выбран только `chat`, отдельный payload не обязателен;
- если выбран `any`, должен существовать хотя бы один реальный контакт в `contact_payload`;
- для `phone/telegram/whatsapp/max` должен быть заполнен соответствующий reachable contact.

## Status Model

### `leads.status`
- `new`
- `in_progress`
- `contacted`
- `registered`
- `cancelled`

### Semantics
- `new`: новая заявка после submit;
- `in_progress`: заявка взята в обработку;
- `contacted`: контакт с пользователем установлен;
- `registered`: пользователь/ребенок зачислен;
- `cancelled`: заявка закрыта без регистрации.

## Submission Flow

### Endpoint
- `POST /api/activities/{activityId}/leads`

### Preconditions
- пользователь должен быть авторизован;
- `activity` должна существовать;
- payload должен пройти `CreateLeadRequest`;
- service-layer дополнительно проверяет subject ownership и reachable contact.

### Result
- создается `lead` со статусом `new`;
- в ответ возвращается `LeadResource`.

## Review Contours

### Organization Contour
Endpoints:
- `GET /api/organizations/{organizationId}/activity-leads`
- `PATCH /api/organizations/{organizationId}/activity-leads/{leadId}/status`

Access:
- чтение через `OrganizationPolicy::viewActivityLeads`
- изменение статуса через `OrganizationPolicy::reviewActivityLeads`

Visibility:
- organization видит только те `leads`, где `lead.activity.organization_id = {organizationId}`

### Admin Contour
Endpoints:
- `GET /api/admin/activity-leads`
- `PATCH /api/admin/activity-leads/{leadId}/status`

Access:
- `admin.panel.access`
- `admin.activity-leads.read`
- `admin.activity-leads.update`

## Response Model

`LeadResource` отдает:
- базовые поля `lead`
- `activity`
- `subject`

### `subject`
- для `self`:
  - агрегируется из `user`
- для `child`:
  - агрегируется из `child`

Цель:
- UI получает уже нормализованный label/subject block и не восстанавливает его вручную.

## Filtering And Sorting

### Organization/Admin List Filters
- `status`
- `request_for_type`
- `activity_id`
- `search`
- `sort_by`
- `sort_dir`

### Allowed Sorts
- `created_at`
- `updated_at`
- `status`

## Operational Notes
- `leads` входят в первый релиз `Activities` и не вынесены в отдельный модуль;
- backend routes живут в `backend/app/Modules/Activities/routes.php`;
- lead orchestration живет в `LeadsService`;
- repository scope:
  - admin list
  - organization-scoped list
  - status update

## Contract Changelog
- 2026-03-18:
  - зафиксирован submit flow `self | child`;
  - подтвержден organization + admin review contour;
  - зафиксирован reachable contact contract;
  - подтверждена status model первого релиза.
