# 🛒 Marketplace API

Backend API for Marketplace project built with **Laravel**, **PostgreSQL**, **Redis**, and **Docker**.  
API documentation follows **OpenAPI-first** approach and is served via a standalone **Swagger UI** container.

---

## 📦 Tech Stack

- PHP 8.x / Laravel
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
└─ README.md
```

---

## 🚀 Getting Started

### ✅ Prerequisites

Make sure you have installed:

- Docker >= 20.x
- Docker Compose >= 2.x

---

## ⚙️ Environment setup

```bash
cp backend/.env.example backend/.env
```

Default configuration works out of the box.

---

## 🐳 Run the project (Docker)

```bash
docker-compose up -d
```

---

## 🧪 Backend setup (first run only)

```bash
docker-compose exec backend php artisan key:generate
docker-compose exec backend php artisan migrate
```

Optional:

```bash
docker-compose exec backend php artisan db:seed
```

---

## 🌐 Available Services

| Service        | URL                          |
|---------------|------------------------------|
| Backend API   | http://localhost:8080        |
| Swagger UI    | http://localhost:8081        |
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

Authentication uses **Bearer token** (Laravel Sanctum).

---

## 🔐 Auth Endpoints

| Method | Endpoint              | Description |
|------|------------------------|------------|
| POST | /api/auth/login        | User login |
| POST | /api/auth/register     | User registration |
| POST | /api/auth/logout       | User logout (protected) |

---

## 🧪 Running Tests

```bash
docker-compose exec backend php artisan test
```

---

## 🛠 Useful Commands

```bash
docker-compose down
docker-compose build
docker-compose logs -f backend
docker-compose exec backend php artisan
```

---

## 🧠 Architecture Notes

- OpenAPI documentation is manually maintained
- Swagger UI runs as a standalone container
- Backend is fully decoupled from API docs
- Errors are centralized and reused across modules

---

## 📌 Roadmap

- API versioning (/v1)
- OpenAPI validation in CI
- Additional domain modules

---
