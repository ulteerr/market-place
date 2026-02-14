#!/bin/sh
set -e

# Проверяем и создаём нужные папки
mkdir -p /var/www/storage/framework/{sessions,views,cache} /var/www/bootstrap/cache

# Создаем storage symlink только если он отсутствует
if [ ! -e /var/www/public/storage ]; then
  if [ -f /var/www/artisan ]; then
    php /var/www/artisan storage:link --no-interaction || true
  else
    ln -s /var/www/storage/app/public /var/www/public/storage
  fi
fi

# Даем права на запись www-data + текущему пользователю
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Запускаем команду контейнера (например php-fpm)
exec "$@"
