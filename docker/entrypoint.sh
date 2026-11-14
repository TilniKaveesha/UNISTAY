#!/usr/bin/env bash
set -Eeuo pipefail

PORT="${PORT:-10000}"
if ! [[ "$PORT" =~ ^[0-9]{2,5}$ ]] || (( PORT < 1024 || PORT > 65535 )); then
    echo "Invalid PORT value" >&2
    exit 1
fi

# Generate private config files from Render runtime env vars, not at image build time.
php /var/www/html/docker/write_runtime_config.php

# Render requires binding HTTP on 0.0.0.0:$PORT.
sed -i -E "s/^Listen [0-9]+$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/__PORT__/${PORT}/g" /etc/apache2/sites-available/000-default.conf
exec apache2-foreground
