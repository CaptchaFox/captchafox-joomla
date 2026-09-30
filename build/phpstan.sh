#!/usr/bin/env bash
# Static analysis against Joomla 5.4 and Joomla 6, using the Joomla source in the official Docker images.
# Needs the dev dependencies (composer install) and Docker.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

for image in joomla:5.4-php8.2-apache joomla:6-php8.4-apache; do
    echo "==> PHPStan against ${image}"
    docker run --rm --entrypoint php -e JOOMLA_ROOT=/usr/src/joomla -v "$PWD":/app -w /app "$image" \
        vendor/bin/phpstan analyse --no-progress --memory-limit=1G
done
