#!/usr/bin/env bash
#
# Aktualisiert eine bereits installierte Laptop-Austausch-Instanz auf den
# neuesten Stand. Als root AUS dem Projektordner ausführen:
#
#   sudo bash deploy/update.sh
#
# Holt den neuen Code (git pull), installiert Abhängigkeiten, baut die Assets,
# migriert die Datenbank (im Produktivbetrieb wird davor automatisch eine
# Sicherung erstellt) und erneuert die Caches.

set -euo pipefail

GREEN='\033[0;32m'; NC='\033[0m'
step() { echo -e "\n${GREEN}==> $*${NC}"; }
die()  { echo "FEHLER: $*" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "Bitte als root ausführen:  sudo bash deploy/update.sh"

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"
[ -f artisan ] || die "Kein Laravel-Projekt gefunden (artisan fehlt)."
PHP="php8.3"

step "In Wartungsmodus schalten"
${PHP} artisan down || true
trap '${PHP} artisan up || true' EXIT

step "Neuen Code holen"
if [ -d .git ]; then
  git pull --ff-only
else
  echo "Kein Git-Repository – Code bitte manuell aktualisieren, dann erneut ausführen."
fi

step "PHP-Abhängigkeiten installieren"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

step "Frontend-Assets bauen"
npm ci
npm run build

step "Datenbank migrieren (im Produktivbetrieb wird vorher gesichert)"
${PHP} artisan migrate --force

step "Caches erneuern"
${PHP} artisan config:cache
${PHP} artisan route:cache
${PHP} artisan view:cache

step "Berechtigungen setzen"
mkdir -p storage/app/backups
chown -R www-data:www-data storage bootstrap/cache

step "Fertig – App wird wieder aktiviert."
