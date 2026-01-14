![CI](https://github.com/ulteerr/market-place/actions/workflows/ci.yml/badge.svg)


# 🛒 Marketplace API

Backend API for Marketplace project built with **Laravel**, **PostgreSQL**, **Redis**, and **Docker**.  
API documentation follows an **OpenAPI-first** approach and is served via a standalone **Swagger UI** container.

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

All request/response examples are available directly in Swagger UI.

---

## 🧰 Makefile Commands

The project includes a Makefile to simplify common Docker and Laravel commands.

### Containers

```bash
make up        # Build and start containers
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

---

## 🧠 Architecture Notes

- OpenAPI documentation is manually maintained
- Swagger UI runs as a standalone container
- Backend is fully decoupled from API docs
- Errors are centralized and reused across modules
- API documentation acts as a contract for frontend integration

---

## 📌 Roadmap

- API versioning (/v1)
- OpenAPI validation in CI
- Additional domain modules
- Production-ready Docker setup

---

## 👤 Author

Marketplace Backend Team
