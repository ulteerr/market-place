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
├─ fronted/              # Nuxt 4 frontend (public + admin pages)
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
cp backend/.env.example backend/.env
```

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

---

## 🌐 Available Services

| Service        | URL                          |
|---------------|------------------------------|
| Fronted (Nuxt)| http://localhost:3000        |
| Backend API   | http://localhost:8080        |
| Swagger UI    | http://localhost:8081        |
| ReDoc CE      | http://localhost:8082        |
| PostgreSQL    | localhost:5433               |
| pgAdmin       | http://localhost:5050        |
| Redis         | localhost:6381               |

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

## 🔐 Auth Endpoints

| Method | Endpoint              | Description |
|------|------------------------|------------|
| POST | /api/auth/login        | User login |
| POST | /api/auth/register     | User registration |
| POST | /api/auth/logout       | User logout (protected) |

All request/response examples are available directly in Swagger UI.

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

### Fronted (Nuxt)

```bash
make front-install        # Install npm deps inside frontend container
make front-npm cmd="run build"
make front-nuxi cmd="add page profile"
```

`node_modules` is stored in the container volume (`/app/node_modules`), so all dependency operations run through Docker.

### Database

```bash
make migrate
make migrate-fresh
make db-seed
```

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

### Fronted E2E (Playwright)

```bash
make up
make front-install
make front-test
```

`test` прогоняет обязательный e2e-сценарий `/admin` в `chrome`, `mozilla-firefox` и `safari-webkit`.
Браузеры и системные зависимости устанавливаются автоматически при первом запуске.

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
- Fronted is modular: component styles live near components
- Swagger UI runs as a standalone container
- Backend is fully decoupled from frontend pages (API/Auth only)
- Errors are centralized and reused across modules
- API documentation acts as a contract for frontend integration

---

## 📌 Roadmap

- API versioning (/v1)
- OpenAPI validation in CI
- Additional domain modules
- Production-ready Docker setup

---
