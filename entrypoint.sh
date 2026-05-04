#!/bin/bash
set -e

# Railway injects PORT at runtime. Default to 8080 to match our domain config.
PORT="${PORT:-8080}"
echo "[entrypoint] Binding Apache to PORT=${PORT}"

# Patch ports.conf - replace any existing Listen directive
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf

# Patch the vhost - replace port in VirtualHost directive
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Show what we ended up with
echo "[entrypoint] ports.conf:"
cat /etc/apache2/ports.conf
echo "[entrypoint] vhost port line:"
grep VirtualHost /etc/apache2/sites-available/000-default.conf

# Test config
apache2ctl configtest

exec apache2-foreground
