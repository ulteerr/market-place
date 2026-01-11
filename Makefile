# Makefile для Laravel 12 + Docker
# Работает с docker-compose.yml, контейнер app

.PHONY: up down restart art comp migrate seed cache clear

# --------------------------
# Контейнеры
# --------------------------

up:
	docker-compose up -d --build

down:
	docker-compose down

restart:
	make down
	make up

# --------------------------
# Laravel artisan
# Использование:
# make art cmd="migrate"
# --------------------------
art:
	docker-compose exec app php artisan $(cmd)

# --------------------------
# Composer
# Использование:
# make comp cmd="install"
# --------------------------
comp:
	docker-compose exec app composer $(cmd)

# --------------------------
# Миграции базы данных
# --------------------------
migrate:
	make art cmd="migrate"

migrate-fresh:
	make art cmd="migrate:fresh --seed"

# --------------------------
# Seeder
# --------------------------
db-seed:
	make art cmd="db:seed"

# --------------------------
# Кэш и конфигурации
# --------------------------
cache-clear:
	make art cmd="cache:clear"

config-cache:
	make art cmd="config:cache"

route-cache:
	make art cmd="route:cache"

view-clear:
	make art cmd="view:clear"
