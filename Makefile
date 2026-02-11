.PHONY: up down restart art comp migrate migrate-fresh db-seed \
        cache-clear config-cache route-cache view-clear \
        test test-auth swagger redoc openapi-validate openapi-bundle docs \
        hooks-install \
        front front-install front-npm front-nuxi front-test

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

# --------------------------
# Tests
# --------------------------

test:
	make art cmd="test"

test-auth:
	make art cmd="test app/Modules/Auth"

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
# Fronted (Nuxt)
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
