.PHONY: up down restart art comp migrate migrate-fresh db-seed db-reset-hard \
        cache-clear config-cache route-cache view-clear app-clear \
        cache-reset-all \
        test test-unit test-redis test-auth test-observability test-observability-backend test-observability-frontend test-all all-test \
        ws-check all \
        swagger redoc openapi-validate openapi-bundle docs \
        hooks-install \
        front front-install front-npm front-nuxi front-test front-storybook front-storybook-build front-ssr-reset

# --------------------------
# Containers
# --------------------------

up:
	docker-compose up -d --build

down:
	docker-compose down

restart:
	make down
	make up

# --------------------------
# Laravel Artisan
# Usage:
# make art cmd="migrate"
# --------------------------

art:
	docker-compose exec backend php artisan $(cmd)

# --------------------------
# Composer
# Usage:
# make comp cmd="install"
# --------------------------

comp:
	docker-compose exec backend composer $(cmd)

# --------------------------
# Database
# --------------------------

migrate:
	make art cmd="migrate"

migrate-fresh:
	make art cmd="migrate:fresh --seed"

db-seed:
	make art cmd="db:seed"

db-reset-hard:
	make art cmd="migrate:fresh --force"
	make art cmd="db:seed --force"
	docker-compose exec backend sh -lc "rm -rf storage/app/public/uploads/*"

# --------------------------
# Cache & optimization
# --------------------------

cache-clear:
	make art cmd="cache:clear"

config-cache:
	make art cmd="config:cache"

route-cache:
	make art cmd="route:cache"

view-clear:
	make art cmd="view:clear"

app-clear:
	make art cmd="optimize:clear"
	make art cmd="cache:clear"
	make art cmd="config:clear"
	make art cmd="route:clear"
	make art cmd="view:clear"

cache-reset-all:
	make front-ssr-reset
	make app-clear

# --------------------------
# Tests
# --------------------------

test:
	make art cmd="config:clear"
	make art cmd="test"

test-all:
	make test
	make front-test
	make openapi-validate

all-test: test-all

all:
	make test-all
	make ws-check

ws-check:
	@set -eu; \
	echo "==> WS check: validate Reverb container status"; \
	docker compose ps --status running reverb | grep -q "reverb" || { \
		echo "ERROR: reverb container is not running"; \
		docker compose ps reverb; \
		exit 1; \
	}; \
	echo "==> WS check: validate presence-watcher status"; \
	docker compose ps --status running presence-watcher | grep -q "presence-watcher" || { \
		echo "ERROR: presence-watcher container is not running"; \
		docker compose ps -a presence-watcher; \
		exit 1; \
	}; \
	BACKEND_KEY="$$(grep -E '^REVERB_APP_KEY=' backend/.env | head -n1 | cut -d= -f2- | tr -d '\"')"; \
	FRONTEND_KEY="$$(grep -E '^NUXT_PUBLIC_REVERB_APP_KEY=' .env 2>/dev/null | head -n1 | cut -d= -f2- | tr -d '\"' || true)"; \
	if [ -z "$$FRONTEND_KEY" ]; then FRONTEND_KEY="local-app-key"; fi; \
	if [ -z "$$BACKEND_KEY" ]; then \
		echo "ERROR: REVERB_APP_KEY is empty in backend/.env"; \
		exit 1; \
	fi; \
	if [ "$$FRONTEND_KEY" != "$$BACKEND_KEY" ]; then \
		echo "ERROR: Reverb app key mismatch"; \
		echo "  backend/.env REVERB_APP_KEY=$$BACKEND_KEY"; \
		echo "  .env NUXT_PUBLIC_REVERB_APP_KEY=$$FRONTEND_KEY"; \
		echo "Set NUXT_PUBLIC_REVERB_APP_KEY in root .env to backend REVERB_APP_KEY and restart frontend."; \
		exit 1; \
	fi; \
	REVERB_PUBLIC_PORT="$${REVERB_PORT:-8083}"; \
	WS_URL="http://localhost:$$REVERB_PUBLIC_PORT/app/$$FRONTEND_KEY?protocol=7&client=js&version=8.4.0&flash=false"; \
	echo "==> WS check: handshake $$WS_URL"; \
	set +e; \
	HTTP_CODE="$$(curl -sS --max-time 3 -o /tmp/ws-check.body -D /tmp/ws-check.headers -w "%{http_code}" \
		-H "Connection: Upgrade" \
		-H "Upgrade: websocket" \
		-H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
		-H "Sec-WebSocket-Version: 13" \
		"$$WS_URL" 2>/tmp/ws-check.err)"; \
	CURL_EXIT_CODE="$$?"; \
	set -e; \
	if [ "$$CURL_EXIT_CODE" -ne 0 ] && [ "$$HTTP_CODE" != "101" ]; then \
		echo "ERROR: WebSocket handshake request failed (curl exit $$CURL_EXIT_CODE)"; \
		cat /tmp/ws-check.err || true; \
		exit 1; \
	fi; \
	if [ "$$HTTP_CODE" != "101" ]; then \
		echo "ERROR: WebSocket handshake failed (expected 101, got $$HTTP_CODE)"; \
		echo "--- Response headers ---"; \
		cat /tmp/ws-check.headers || true; \
		echo "--- Response body ---"; \
		cat /tmp/ws-check.body || true; \
		exit 1; \
	fi; \
	echo "OK: WebSocket handshake is healthy (101 Switching Protocols)."

test-unit:
	make art cmd="test --exclude-group=redis"

test-redis:
	make art cmd="test --group=redis"

test-auth:
	make art cmd="test app/Modules/Auth"

test-observability:
	make test-observability-backend
	make test-observability-frontend

test-observability-backend:
	make art cmd="test --filter=Observability"

test-observability-frontend:
	docker-compose exec frontend npm run test:unit -- tests/unit/composables/useAdminObservability.spec.ts tests/unit/pages/admin/monitoring/index.spec.ts

# --------------------------
# Swagger / OpenAPI
# --------------------------

swagger:
	docker-compose restart swagger-ui

redoc:
	docker-compose restart redoc

openapi-validate:
	docker run --rm -v $(PWD):/work -w /work node:20-alpine sh -lc "npm i -g swagger-cli@4.0.4 >/dev/null 2>&1 && swagger-cli validate docker/swagger/openapi.yaml"

openapi-bundle:
	docker run --rm -v $(PWD):/work -w /work node:20-alpine sh -lc "npm i -g swagger-cli@4.0.4 >/dev/null 2>&1 && swagger-cli bundle docker/swagger/openapi.yaml --type yaml --outfile docker/swagger/openapi.bundle.yaml"

docs:
	make openapi-validate
	make openapi-bundle
	make swagger
	make redoc

# --------------------------
# Git hooks
# --------------------------

hooks-install:
	git config core.hooksPath .githooks

# --------------------------
# Frontend (Nuxt)
# --------------------------

front:
	docker-compose up -d frontend

front-install:
	docker-compose exec frontend npm install

front-npm:
	docker-compose exec frontend npm $(cmd)

front-nuxi:
	docker-compose exec frontend npx nuxi $(cmd)

front-test:
	docker-compose exec frontend npm run test:unit
	docker-compose exec frontend npm run test

front-storybook:
	docker-compose up -d frontend-storybook

front-storybook-build:
	docker-compose run --rm frontend-storybook npm run build-storybook

front-ssr-reset:
	docker-compose exec frontend sh -lc "rm -rf .nuxt .output node_modules/.cache/nuxt"
	docker-compose restart frontend
