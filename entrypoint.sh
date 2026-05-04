#!/bin/bash
set -e

PORT="${PORT:-80}"
echo "[entrypoint] Using PORT=${PORT}"

# Patch Apache to listen on $PORT
sed -i "s/Listen [0-9]*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Verify config before starting
apache2ctl configtest 2>&1 || true

exec apache2-foreground
