#!/bin/sh
set -e

PORT=${PORT:-8080}

echo "Using PORT=$PORT"

# Only replace port 80 safely
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

echo "Starting Apache on port ${PORT}..."

exec apache2-foreground