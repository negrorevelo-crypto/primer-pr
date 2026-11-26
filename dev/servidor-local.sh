#!/usr/bin/env bash
# Alternativa sin Docker: necesita PHP 8.2+ con pdo_mysql y (opcional) MariaDB local.
set -e
cd "$(dirname "$0")/.."
if [ -f .env ]; then set -a; . ./.env; set +a; else set -a; . dev/local.env.example; set +a; fi
PORT="${PORT:-8080}"
echo "Chelart local en http://localhost:$PORT  (Ctrl+C para detener)"
exec php -d display_errors=1 -S "localhost:$PORT" -t chelart/public dev/router.php
