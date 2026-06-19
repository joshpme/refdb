#!/bin/bash
set -euo pipefail

cd /var/www/html

mkdir -p var/cache var/log var/sessions config/jwt
chown -R www-data:www-data var config/jwt
chmod -R 775 var

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -f config/jwt/private.pem ]; then
    php bin/console lexik:jwt:generate-keypair --skip-if-exists
fi

until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
    echo "Waiting for database..."
    sleep 2
done

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec "$@"
