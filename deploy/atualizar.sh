#!/usr/bin/env bash
# Puxa a versão nova do GitHub e reaplica no servidor.   bash deploy/atualizar.sh
set -euo pipefail
cd "$(dirname "$0")/.."
DC="sudo docker compose -f docker-compose.prod.yml"
IA_DIR=$(grep '^IA_DIR=' .env | cut -d= -f2-)

git pull --ff-only
[ -n "$IA_DIR" ] && git -C "$IA_DIR" pull --ff-only

$DC up -d --build mysql app ia
$DC exec -T app composer install --no-dev --optimize-autoloader --no-interaction
$DC run --rm node sh -c "npm ci --no-audit --no-fund && npm run build"
$DC exec -T app php artisan migrate --force
sudo chown -R 33:33 storage bootstrap/cache
$DC exec -T app php artisan config:cache
$DC exec -T app php artisan route:cache
$DC exec -T app php artisan view:cache
$DC up -d caddy
echo "Atualizado."
