#!/bin/bash
set -euo pipefail

# Wait for MariaDB service to be available
until mysqladmin ping -h "${DB_HOST}" -u "${DB_USER}" --password="${DB_PASSWORD}" --silent; do
  echo "Waiting for MariaDB..."
  sleep 2
done

# Check if a known table exists
TABLE_EXISTS=$(mysql -h "$DB_HOST" -u "$DB_USER" --password="$DB_PASSWORD" -D "$DB_NAME" -sse "SHOW TABLES LIKE 'users';")
if [ -z "$TABLE_EXISTS" ]; then
  echo "Importing initial database..."
  mysql -h "$DB_HOST" -u "$DB_USER" --password="$DB_PASSWORD" "$DB_NAME" < /var/www/install.sql
fi

# Remove install script if initialization succeeded
if mysql -h "$DB_HOST" -u "$DB_USER" --password="$DB_PASSWORD" -D "$DB_NAME" -sse "SHOW TABLES LIKE 'users';" | grep -q users; then
  rm -f /var/www/public/install.php
fi

# Start cron and Apache
service cron start
exec apache2-foreground
