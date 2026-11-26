#!/usr/bin/env bash
# Netlify build: the build image only ships PHP <= 8.3, but the locked
# Symfony 8.x / Pest 5 packages need PHP >= 8.4. Download a prebuilt static
# PHP 8.4 CLI, put it first on PATH, install Composer deps, then build assets.
set -euo pipefail

PHP_STATIC_VERSION="${PHP_STATIC_VERSION:-8.4.23}"
PHP_DIR="${NETLIFY_CACHE_DIR:-/tmp}/php-static-${PHP_STATIC_VERSION}"

if [ ! -x "${PHP_DIR}/php" ]; then
  echo "Downloading static PHP ${PHP_STATIC_VERSION}..."
  mkdir -p "${PHP_DIR}"
  curl -fsSL "https://dl.static-php.dev/static-php-cli/common/php-${PHP_STATIC_VERSION}-cli-linux-x86_64.tar.gz" \
    | tar -xz -C "${PHP_DIR}"
  chmod +x "${PHP_DIR}/php"
fi

export PATH="${PHP_DIR}:${PATH}"
php -v | head -n 1

# Laravel needs an .env to boot artisan (used by the Wayfinder Vite plugin).
if [ ! -f .env ]; then
  cp .env.example .env
fi
composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
grep -q '^APP_KEY=base64' .env || php artisan key:generate --force --no-interaction

npm run build
