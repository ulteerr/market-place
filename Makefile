.PHONY: up down restart art comp migrate migrate-fresh db-seed \
        cache-clear config-cache route-cache view-clear \
        test test-auth swagger

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
