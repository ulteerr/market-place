#!/bin/sh
set -eu

if [ ! -f .env ] && [ -f .env.example ]; then
  echo "[frontend] Creating .env from .env.example..."
  cp .env.example .env
fi

LOCK_HASH_FILE="node_modules/.deps-lock-hash"
LOCK_READY_FILE="node_modules/.deps-ready"
CURRENT_LOCK_HASH=""

if [ -f package-lock.json ]; then
  CURRENT_LOCK_HASH="$(sha1sum package-lock.json | awk '{print $1}')"
fi

needs_install="0"
if [ ! -d node_modules ] || [ ! -f node_modules/.bin/nuxt ] || [ ! -f node_modules/.bin/storybook ] || [ ! -d node_modules/@tailwindcss ]; then
  needs_install="1"
fi

if [ -n "$CURRENT_LOCK_HASH" ]; then
  if [ ! -f "$LOCK_HASH_FILE" ] || [ "$(cat "$LOCK_HASH_FILE" 2>/dev/null || true)" != "$CURRENT_LOCK_HASH" ]; then
    needs_install="1"
  fi
fi

if [ "$needs_install" = "1" ]; then
  if [ "${FRONTEND_INSTALL_OWNER:-0}" = "1" ]; then
    echo "[frontend] Installing npm dependencies inside container..."
    npm install
    if [ -n "$CURRENT_LOCK_HASH" ]; then
      echo "$CURRENT_LOCK_HASH" > "$LOCK_HASH_FILE"
    fi
    touch "$LOCK_READY_FILE"
  else
    echo "[frontend] Waiting for dependency installation by owner container..."
    i=0
    while : ; do
      if [ -f "$LOCK_READY_FILE" ] && [ -f "$LOCK_HASH_FILE" ] && [ -n "$CURRENT_LOCK_HASH" ] && [ "$(cat "$LOCK_HASH_FILE" 2>/dev/null || true)" = "$CURRENT_LOCK_HASH" ]; then
        break
      fi

      i=$((i + 1))
      if [ "$i" -ge 180 ]; then
        echo "[frontend] Wait timeout reached, installing dependencies locally..."
        npm install
        if [ -n "$CURRENT_LOCK_HASH" ]; then
          echo "$CURRENT_LOCK_HASH" > "$LOCK_HASH_FILE"
        fi
        touch "$LOCK_READY_FILE"
        break
      fi

      sleep 2
    done
  fi
fi

exec "$@"
