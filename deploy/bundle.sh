#!/usr/bin/env bash
#
# Erzeugt ein vollständiges Installationspaket für den USB-Stick.
#
# AUF DIESEM (Entwickler-)RECHNER ausführen – hier ist Internet vorhanden:
#
#   bash deploy/bundle.sh
#
# Das Skript lädt hier alle PHP-Bibliotheken herunter (composer) und baut die
# Frontend-Assets (npm) und packt dann den kompletten Code INKLUSIVE vendor/
# und public/build/ in eine einzige Datei:  laptop-austausch-paket.tar.gz
#
# Damit braucht der Server WEDER Composer NOCH Node/npm und lädt davon nichts aus
# dem Internet – deploy/setup.sh erkennt die mitgelieferten Teile und überspringt
# diese Schritte. (Internet auf dem Server wird dann nur noch für die System-
# pakete PHP/Apache/MariaDB via apt benötigt.)
#
# Hinweis: Es wird der zuletzt committete Stand (git HEAD) verpackt – die .env und
# der .git-Ordner sind dabei bewusst NICHT enthalten. Vor dem Packen also alle
# gewünschten Änderungen committen.

set -euo pipefail

GREEN='\033[0;32m'; NC='\033[0m'
step() { echo -e "\n${GREEN}==> $*${NC}"; }
die()  { echo "FEHLER: $*" >&2; exit 1; }

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"
[ -f artisan ] || die "Bitte aus dem Projektordner ausführen."
command -v git >/dev/null      || die "git fehlt auf diesem Rechner."
command -v composer >/dev/null || die "composer fehlt auf diesem Rechner."
command -v npm >/dev/null       || die "npm fehlt auf diesem Rechner."

OUT="$APP_DIR/laptop-austausch-paket.tar.gz"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
DEST="$STAGE/laptop-austausch"
mkdir -p "$DEST"

step "Versionierten Code exportieren (ohne .git und .env)"
git archive --format=tar HEAD | tar -x -C "$DEST"

step "PHP-Abhängigkeiten herunterladen (composer, ohne Dev-Pakete)"
( cd "$DEST" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction )

step "Frontend-Assets bauen (npm) und node_modules wieder entfernen"
( cd "$DEST" && npm ci && npm run build && rm -rf node_modules )

step "Paket schnüren"
tar -czf "$OUT" -C "$STAGE" laptop-austausch

step "Fertig! 🎉"
echo "    Paket:  $OUT  ($(du -h "$OUT" | cut -f1))"
echo
echo "    Nächste Schritte:"
echo "      1. $OUT auf den USB-Stick kopieren."
echo "      2. Auf dem Server auspacken:  tar -xzf laptop-austausch-paket.tar.gz"
echo "      3. In den Ordner wechseln:    cd laptop-austausch"
echo "      4. Installieren:              sudo bash deploy/setup.sh"
