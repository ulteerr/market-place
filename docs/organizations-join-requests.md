# Organizations Join Requests

## Scope
Документ фиксирует текущий контракт заявок в организации и клиентского участия.

## Final Data Model

### `organization_join_requests`
- `organization_id`
- `subject_type` (`user` | `child`)
- `subject_id`
- `requested_by_user_id`
- `status` (`pending` | `approved` | `rejected`)
- `message`
- `review_note`
- `reviewed_by_user_id`
- `reviewed_at`

### `organization_users` (staff only)
- Таблица сотрудников организации.
- Используется для ролей и доступа (`owner/admin/manager/member/...`).
- Клиенты сюда не записываются.

### `organization_clients` (client domain)
- Таблица клиентского участия в организации.
- Хранит и родителей, и детей:
  - `subject_type` (`user` | `child`)
  - `subject_id`
- Важные поля:
  - `organization_id`
  - `status`
  - `added_by_user_id`
  - `joined_at`
- Уникальность:
  - `(organization_id, subject_type, subject_id)`

## Business Rules

### Submit
- `subject_type=user`: пользователь может подать заявку только за себя.
- `subject_type=child`: заявку может подать только родитель/представитель связанного ребенка.
- Запрещены дубликаты pending-заявок по `(organization_id, subject_type, subject_id)`.

### Review
- Модерация доступна владельцу организации и staff-админам.
- `approve`:
  - меняет статус заявки на `approved`;
  - создает/обновляет запись в `organization_clients` для субъекта заявки.
- `reject`:
  - меняет статус заявки на `rejected`;
  - не создает запись в `organization_clients`.

## Client Lifecycle (`organization_clients`)

### Statuses
- `active`: клиент участвует в организации.
- `left`: клиент выбыл (добровольно/по решению администратора).
- `blocked`: участие ограничено администратором.

### Transition Rules
- запись создается при `approve` заявки со статусом `active`;
- `left` и `blocked` выставляются административными действиями;
- возврат в `active` допускается через административное изменение статуса;
- для `left/blocked` новая pending-заявка может быть разрешена отдельным правилом сервиса (на текущем этапе правило пока не внедрено).

## API Notes

### Request: create join request
`POST /api/organizations/{organizationId}/join-requests`

```json
{
  "subject_type": "child",
  "subject_id": "00000000-0000-0000-0000-000000000000",
  "message": "Прошу принять в организацию"
}
```

### Response fragment: list join requests
```json
{
  "id": "request-id",
  "subject_type": "child",
  "subject_id": "child-id",
  "subject": {
    "type": "child",
    "id": "child-id",
    "label": "Иванов Петр Сергеевич"
  },
  "requested_by": {
    "id": "user-id",
    "label": "Иванов Иван Иванович"
  },
  "reviewed_by": null
}
```

## Edge Cases
- пользователь без детей может подавать только за себя (`subject_type=user`);
- родитель не может подавать за чужого ребенка;
- approve child-заявки не создает staff-членство в `organization_users`;
- если клиент уже `active` в `organization_clients`, новая заявка должна быть отклонена.

## Contract Changelog
- 2026-03-08:
  - удален legacy-подход `organization_join_requests.user_id`;
  - введены `subject_type`, `subject_id`, `requested_by_user_id`;
  - разделены staff (`organization_users`) и клиенты (`organization_clients`).
