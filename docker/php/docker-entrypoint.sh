#!/bin/sh
set -e

# Проверяем и создаём нужные папки
mkdir -p /var/www/storage/framework/{sessions,views,cache} /var/www/bootstrap/cache

# Даем права на запись www-data + текущему пользователю
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Запускаем команду контейнера (например php-fpm)
exec "$@"
