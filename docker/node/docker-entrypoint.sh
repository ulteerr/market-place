#!/bin/sh
set -eu

if [ ! -d node_modules ] || [ ! -f node_modules/.bin/nuxt ] || [ ! -d node_modules/@nuxtjs/tailwindcss ]; then
  echo "[frontend] Installing npm dependencies inside container..."
  npm install
fi

exec "$@"
