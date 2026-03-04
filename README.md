![CI](https://github.com/ulteerr/market-place/actions/workflows/ci.yml/badge.svg)

# 🛒 Marketplace Platform

Frontend + backend for Marketplace built with **Nuxt 4**, **Laravel**, **PostgreSQL**, **Redis**, and **Docker**.  
Frontend handles public and admin pages; backend is focused on API and authentication.

---

## 📦 Tech Stack

- PHP 8.x / Laravel
- Nuxt 4
- Tailwind CSS
- PostgreSQL 15
- Redis
- Nginx
- Docker & Docker Compose
- OpenAPI 3.0
- Swagger UI (standalone container)

---

## 📁 Project Structure (high level)

```text
project-root/
├─ frontend/              # Nuxt 4 frontend (public + admin pages)
├─ backend/              # Laravel application
├─ docker/
│  ├─ nginx/             # Nginx config
│  └─ swagger/           # OpenAPI documentation (source of truth)
│     ├─ openapi.yaml
│     ├─ modules/
│     │  └─ auth.yaml
│     ├─ schemas/
│     │  ├─ auth.yaml
│     │  └─ user.yaml
│     └─ errors.yaml
├─ docker-compose.yml
├─ Makefile
└─ README.md
```

---

## 🚀 Getting Started

### ✅ Prerequisites

Make sure you have installed:

- Docker >= 20.x
- Docker Compose >= 2.x
- Make

---

## ⚙️ Environment setup

```bash
cp .env.example .env
```

Файл `backend/.env` создаётся при первом запуске контейнера из `backend/.env.example`, если его ещё нет. При необходимости внесите в него изменения.
Файл `frontend/.env` создаётся при первом запуске frontend-контейнера из `frontend/.env.example`, если его ещё нет. При необходимости внесите в него изменения.

В `.env` настраиваются:

- префикс имен контейнеров через `COMPOSE_PROJECT_NAME`
- внешние порты сервисов (`FRONTEND_PORT`, `WEB_PORT`, и т.д.)

Default configuration works out of the box.

---

## 🐳 Run the project (Docker)

```bash
make up
```

---

## 🧪 Backend setup (first run only)

```bash
make art cmd="key:generate"
make migrate
```

Optional:

```bash
make db-seed
```

Для полного пересоздания БД + сидов + очистки загруженных файлов:

```bash
make db-reset-hard
```

---

## 🔐 Admin Panel Login

### 1. Убедиться, что есть сидовые пользователи

Сидовые аккаунты создаются в `backend/app/Modules/Users/Database/Seeders/UsersSeeder.php` и привязка ролей задается там же:

```php

$superAdminUser = User::query()->firstOrCreate(
	["email" => "superadmin@example.com"],
	[
		"first_name" => "System",
		"last_name" => "SuperAdmin",
		"phone" => "+79990001122",
		"password" => "password123",
	],
);
$superAdminUser->roles()->syncWithoutDetaching([$participantRole->id, $superAdminRole->id]);
$adminUser = User::query()->firstOrCreate(
    ["email" => "admin@example.com"],
    [
        "first_name" => "System",
        "last_name" => "Admin",
        "phone" => "+79991112233",
        "password" => "password123",
    ],
);
$adminUser->roles()->syncWithoutDetaching([$participantRole->id, $adminRole->id]);

$moderatorUser = User::query()->firstOrCreate(
    ["email" => "moderator@example.com"],
    [
        "first_name" => "System",
        "last_name" => "Moderator",
        "phone" => "+79994445566",
        "password" => "password123",
    ],
);
```

Если сиды еще не применены:

```bash
make db-seed
```

### 2. Открыть страницу логина

- Frontend login page: `http://localhost:3000/login`
- После успешного входа переход в админку: `http://localhost:3000/admin`

### 3. Ввести данные в форму

- Поле `Email`
- Поле `Password`

Демо-учетки:

- `superadmin@example.com` / `password123`
- `admin@example.com` / `password123`
- `moderator@example.com` / `password123`

Ограничения по ролям:

- `super_admin` имеет все права.
- `admin` может просматривать, создавать и удалять несистемные роли, но не может назначать роль `super_admin`.

---

## 🌐 Available Services

| Service        | URL                   |
| -------------- | --------------------- |
| frontend (Nuxt) | http://localhost:3000 |
| Storybook UI   | http://localhost:6006 |
| Backend API    | http://localhost:8080 |
| Swagger UI     | http://localhost:8081 |
| ReDoc CE       | http://localhost:8082 |
| PostgreSQL     | localhost:5433        |
| pgAdmin        | http://localhost:5050 |
| Redis          | localhost:6381        |

Порты можно изменить в корневом `.env`.

---

## 📚 Frontend Documentation

- Storybook component docs: `http://localhost:6006`
- Architecture/process docs: `frontend/docs/*`
- Local run: `make front-storybook`
- CI gate: `npm run build-storybook` (frontend job)

---

## 📘 API Documentation (Swagger)

API documentation is **OpenAPI-first** and fully decoupled from backend code.

Swagger UI:

```
http://localhost:8081
```

ReDoc CE:

```
http://localhost:8082
```

Authentication uses **Bearer token** (Laravel Sanctum).

---

All endpoints and request/response examples are available in Swagger UI.

---

## 🕘 ChangeLog (Audit Trail)

Admin ChangeLog is available for profile, users and roles in admin pages.

What it includes:

- event type (`create`, `update`, `delete`, `restore`)
- actor (current user is shown as `Я` / `Me`, others link to `/admin/users/{id}`)
- version, timestamp, changed fields
- rollback action from selected changelog record

Rollback behavior:

- rollback endpoint: `POST /api/admin/changelog/{id}/rollback`
- UI refreshes entity data after successful rollback
- empty `update` logs (without actual field changes) are skipped

All API details are documented in Swagger.

---

## 🆕 Recent ChangeLog updates

- rollback from `create` is supported (restore to initial snapshot / version `#1`)
- rollback entries are shown as `restore` with target version label
- create entries show one JSON block (`Created/Создано`) instead of `Before/After`
- diff view hides empty `before` and shows `set <value>` for first-time assignment
- profile page refreshes ChangeLog block after save/avatar actions without full reload
- pagination hides `Back` on first page and `Next` on last page
- added e2e coverage for pagination navigation and rollback from `create`

---

## ⚙️ ChangeLog ENV

Configure backend changelog list mode in `backend/.env`:

```env
CHANGELOG_ADMIN_LIST_MODE=latest
CHANGELOG_ADMIN_LIMIT=20
```

Meaning:

- `CHANGELOG_ADMIN_LIST_MODE`: `latest` or `paginated`
- `CHANGELOG_ADMIN_LIMIT`: record limit (`latest`) or page size cap (`paginated`)

---

## 🧰 Makefile Commands

The project includes a Makefile to simplify common Docker and Laravel commands.

### Containers

```bash
make up        # Build and start containers
make front     # Start only frontend container
make down      # Stop containers
make restart   # Restart containers
```

### Artisan commands

```bash
make art cmd="key:generate"
make art cmd="migrate"
make art cmd="test"
```

### Composer

```bash
make comp cmd="install"
make comp cmd="dump-autoload"
```

### Frontend (Nuxt)

```bash
make front-install        # Install npm deps inside frontend container
make front-npm cmd="run build"
make front-nuxi cmd="add page profile"
make front-storybook      # Start Storybook container (port from .env, default :6006)
make front-storybook-build
```

`node_modules` is stored in the container volume (`/app/node_modules`), so all dependency operations run through Docker.

### Database

```bash
make migrate
make migrate-fresh
make db-seed
make db-reset-hard
```

`db-reset-hard` выполняет полный цикл: `migrate:fresh` → `db:seed` и очищает `backend/storage/app/public/uploads/*`.

### Cache & optimization

```bash
make cache-clear
make config-cache
make route-cache
make view-clear
```

### Testing

```bash
make test
make test-auth
```

### Frontend E2E (Playwright)

```bash
make up
make front-install
make front-test
```

`front-test` прогоняет unit-тесты (`vitest`), затем обязательный e2e-сценарий `/admin`
в `chrome`, `mozilla-firefox` и `safari-webkit`.
Браузеры и системные зависимости устанавливаются автоматически при первом e2e запуске.

Для UI-режима:

```bash
make front-npm cmd="run test:e2e:ui"
```

Запуск по конкретному браузеру:

```bash
make front-npm cmd="run test:e2e -- --project=chrome"
make front-npm cmd="run test:e2e -- --project=mozilla-firefox"
make front-npm cmd="run test:e2e -- --project=safari-webkit"
```

Примечание по Safari: на Linux нельзя запустить нативный Safari, но `safari-webkit` в Playwright проверяет движок WebKit (ближайший эквивалент Safari).

### Frontend documentation

- Архитектура и процессы: `frontend/docs/README.mdx`
- Storybook UI-каталог: `http://localhost:6006`

### OpenAPI

```bash
make docs                # Validate + bundle + restart Swagger/ReDoc
make openapi-validate    # Validate spec and refs
make openapi-bundle      # Build bundled spec: docker/swagger/openapi.bundle.yaml
make swagger             # Restart Swagger UI
make redoc               # Restart ReDoc CE
```

---

## 🧠 Architecture Notes

- OpenAPI documentation is manually maintained
- Frontend is modular: component styles live near components
- Swagger UI runs as a standalone container
- Backend is fully decoupled from frontend pages (API/Auth only)
- Errors are centralized and reused across modules
- API documentation acts as a contract for frontend integration

### Permissions strategy

Права разделены по namespace:

- `admin.*` — доступ и действия в админке
- `org.*` — права в контексте организации
- `user.*` — права обычного пользователя (собственный профиль и т.д.)
- Для `admin.users` и `admin.roles` используются отдельные CRUD-коды:
- `admin.users.read|create|update|delete`
- `admin.roles.read|create|update|delete`
- Для geo-справочников: `admin.geo.read|create|update|delete`
- Для metro-справочников: `admin.metro.read|create|update|delete`
- Обычный пользователь управляет только своими детьми через `/children` (full CRUD на собственные записи)
- Для action logs: `admin.action-log.read`
- Для changelog: `admin.changelog.read`
- Для rollback changelog: `admin.changelog.rollback` + проверка права на целевую модель:
- `User` rollback требует `admin.users.update`
- `Role` rollback требует `admin.roles.update`
- Для моделей подключены Policy:
- `UserPolicy`, `RolePolicy`, `ActionLogPolicy`, `ChangeLogPolicy`, `OrganizationPolicy`, `ChildPolicy`, `CountryPolicy`, `RegionPolicy`, `CityPolicy`, `DistrictPolicy`, `MetroLinePolicy`, `MetroStationPolicy`
- Регистрация policy выполнена в `Modules\\Auth\\AuthServiceProvider` через `Gate::policy(...)`
- Источник прав: таблицы `access_permissions` и `role_access_permission` (role-based).
- Дополнительно поддерживаются персональные оверрайды прав пользователя в `user_access_permissions`:
- `allowed=true` — точечно выдать право пользователю
- `allowed=false` — точечно запретить право (даже если оно есть через роль)
- Для UI редактирования прав доступен endpoint `GET /api/admin/permissions`.
- Роли и базовые права синхронизируются в `Modules\\Users\\Database\\Seeders\\RolesSeeder`.
- Для маршрутов доступен middleware `can_permission`:

```php
Route::middleware(['auth:sanctum', 'can_permission:admin.users.read'])->group(function () {
    // ...
});
```

- Доступ в админ-маршруты проверяется через `can_permission:admin.panel.access`.

---

## 📌 Roadmap

- API versioning (/v1)
- OpenAPI validation in CI
- Additional domain modules
- Production-ready Docker setup

---
