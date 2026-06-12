#!/usr/bin/env bash
#
# Aktualisiert eine bereits installierte Laptop-Austausch-Instanz.
# Als root AUS dem entpackten Paketordner ausführen:
#
#   sudo bash deploy/update.sh
#
# Workflow für Bundle-Updates (USB/Cloud):
#   1. Auf dem Entwickler-Laptop:  bash deploy/bundle.sh
#   2. laptop-austausch-paket.tar.gz auf den Server übertragen
#   3. Auf dem Server (im Elternordner der Installation, z.B. /var/www):
#        tar -xzf laptop-austausch-paket.tar.gz   # überschreibt Dateien direkt
#        cd laptop-austausch
#        sudo bash deploy/update.sh
#
#   .env und storage/ (Backups, Daten) sind nicht im Paket und bleiben erhalten.
#   Nichts muss vorher gelöscht werden.

set -euo pipefail

GREEN='\033[0;32m'; NC='\033[0m'
step() { echo -e "\n${GREEN}==> $*${NC}"; }
die()  { echo "FEHLER: $*" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "Bitte als root ausführen:  sudo bash deploy/update.sh"

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"
[ -f artisan ] || die "Kein Laravel-Projekt gefunden (artisan fehlt)."

# PHP-Version automatisch ermitteln.
if   command -v php8.4 >/dev/null 2>&1; then PHP="php8.4"
elif command -v php8.3 >/dev/null 2>&1; then PHP="php8.3"
elif command -v php8.2 >/dev/null 2>&1; then PHP="php8.2"
elif command -v php    >/dev/null 2>&1; then PHP="php"
else die "Kein PHP gefunden."
fi

HAVE_VENDOR=false; [ -f vendor/autoload.php ]        && HAVE_VENDOR=true
HAVE_BUILD=false;  [ -f public/build/manifest.json ] && HAVE_BUILD=true

step "In Wartungsmodus schalten"
${PHP} artisan down || true
trap '${PHP} artisan up || true' EXIT

if [ "$HAVE_VENDOR" = true ]; then
  step "PHP-Abhängigkeiten bereits im Paket enthalten – kein composer nötig"
else
  die "vendor/ fehlt. Bitte Paket mit 'bash deploy/bundle.sh' neu erzeugen."
fi

if [ "$HAVE_BUILD" = true ]; then
  step "Frontend-Assets bereits im Paket enthalten – kein npm nötig"
else
  die "public/build/ fehlt. Bitte Paket mit 'bash deploy/bundle.sh' neu erzeugen."
fi

step "Datenbank migrieren (im Produktivbetrieb wird vorher automatisch gesichert)"
${PHP} artisan migrate --force

step "Caches erneuern"
${PHP} artisan config:cache
${PHP} artisan route:cache
${PHP} artisan view:cache

step "Berechtigungen setzen"
mkdir -p storage/app/backups
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

step "Fertig – App wird wieder aktiviert."
