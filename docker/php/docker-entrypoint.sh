#!/bin/sh
set -e

LOCK_HASH_FILE="/var/www/vendor/.deps-lock-hash"
CURRENT_LOCK_HASH=""

if [ -f /var/www/composer.lock ]; then
  CURRENT_LOCK_HASH="$(sha1sum /var/www/composer.lock | awk '{print $1}')"
fi

needs_install="0"
if [ ! -d /var/www/vendor ] || [ ! -f /var/www/vendor/autoload.php ]; then
  needs_install="1"
fi

if [ -n "$CURRENT_LOCK_HASH" ]; then
  if [ ! -f "$LOCK_HASH_FILE" ] || [ "$(cat "$LOCK_HASH_FILE" 2>/dev/null || true)" != "$CURRENT_LOCK_HASH" ]; then
    needs_install="1"
  fi
fi

if [ "$needs_install" = "1" ] && [ -f /var/www/composer.json ]; then
  echo "[backend] Installing composer dependencies inside container..."
  composer install --working-dir=/var/www --no-interaction --prefer-dist

  if [ -n "$CURRENT_LOCK_HASH" ]; then
    mkdir -p /var/www/vendor
    echo "$CURRENT_LOCK_HASH" > "$LOCK_HASH_FILE"
  fi
fi

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
