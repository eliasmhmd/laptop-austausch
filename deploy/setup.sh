#!/usr/bin/env bash
#
# Automatische Erstinstallation von Laptop-Austausch auf einem frischen
# Debian-Server (Apache + MariaDB). Als root ausführen, AUS dem Projektordner:
#
#   sudo bash deploy/setup.sh
#
# Das Skript richtet PHP-Erweiterungen, die Datenbank, die .env, die
# Berechtigungen und den Apache-VirtualHost ein und legt einen Admin-Zugang an.
# Es ist gefahrlos mehrfach ausführbar.
#
# Voraussetzung: Das Paket wurde mit deploy/bundle.sh erstellt (enthält vendor/
# und public/build/) – dann wird weder Composer noch Node/npm benötigt.
# Alle Pakete kommen ausschließlich aus den offiziellen Debian-Paketquellen.

set -euo pipefail

# ----------------------------------------------------------------------------
# Hilfsausgaben
# ----------------------------------------------------------------------------
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
step() { echo -e "\n${GREEN}==> $*${NC}"; }
info() { echo -e "    $*"; }
warn() { echo -e "${YELLOW}!   $*${NC}"; }
die()  { echo -e "${RED}FEHLER: $*${NC}" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "Bitte als root ausführen:  sudo bash deploy/setup.sh"

# Projektverzeichnis = übergeordneter Ordner dieses Skripts.
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"
[ -f artisan ] || die "Kein Laravel-Projekt gefunden (artisan fehlt). Skript aus dem Projektordner starten."

# ----------------------------------------------------------------------------
# Einstellungen
# ----------------------------------------------------------------------------
DB_NAME="laptop_austausch"
DB_USER="laptop_app"

# PHP-Version automatisch ermitteln (bevorzugt 8.4, dann 8.3, dann 8.2).
if   command -v php8.4 >/dev/null 2>&1; then PHP="php8.4"
elif command -v php8.3 >/dev/null 2>&1; then PHP="php8.3"
elif command -v php8.2 >/dev/null 2>&1; then PHP="php8.2"
elif command -v php    >/dev/null 2>&1; then PHP="php"
else die "Kein PHP gefunden. Bitte PHP 8.2+ über die offiziellen Debian-Paketquellen installieren."
fi
info "Verwende PHP: $PHP ($(${PHP} -r 'echo PHP_VERSION;'))"

# Server-Adresse automatisch ermitteln (erste IP), kann überschrieben werden.
SERVER_IP="$(hostname -I | awk '{print $1}')"
read -r -p "Adresse, unter der die App erreichbar sein soll [${SERVER_IP}]: " INPUT_ADDR
SERVER_NAME="${INPUT_ADDR:-$SERVER_IP}"

# Admin-Zugang abfragen.
read -r -p "Admin – Name [IT Kreis Groß-Gerau]: " ADMIN_NAME;  ADMIN_NAME="${ADMIN_NAME:-IT Kreis Groß-Gerau}"
read -r -p "Admin – E-Mail [admin@kreisgg.de]: "   ADMIN_EMAIL; ADMIN_EMAIL="${ADMIN_EMAIL:-admin@kreisgg.de}"
while :; do
  read -r -s -p "Admin – Passwort (mind. 8 Zeichen): " ADMIN_PASS; echo
  [ "${#ADMIN_PASS}" -ge 8 ] && break || warn "Zu kurz, bitte erneut."
done

# DB-Passwort: vorhandenes aus .env wiederverwenden, sonst neu erzeugen.
# So bleibt das Passwort bei mehrfachem Ausführen des Skripts konsistent.
if [ -f .env ] && grep -qE "^DB_PASSWORD=.+" .env; then
  DB_PASS="$(grep ^DB_PASSWORD .env | cut -d= -f2)"
else
  DB_PASS="$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | cut -c1-24)"
fi

# ----------------------------------------------------------------------------
# 1. Systempakete (nur offizielle Debian-Quellen)
# ----------------------------------------------------------------------------
step "Systempakete aktualisieren und Grundwerkzeuge installieren"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y ca-certificates unzip openssl

step "Apache und MariaDB installieren (falls noch nicht vorhanden)"
apt-get install -y apache2 mariadb-server mariadb-client
systemctl enable --now apache2
systemctl enable --now mariadb

# ----------------------------------------------------------------------------
# 2. PHP-Erweiterungen nachinstallieren (PHP selbst ist bereits vorhanden)
# ----------------------------------------------------------------------------
step "PHP-Erweiterungen sicherstellen (aus offiziellen Debian-Paketen)"

# PHP-Kurzversion ermitteln (z.B. "8.4") für Paketnamen wie libapache2-mod-php8.4
PHP_VER="$(${PHP} -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"

apt-get install -y \
  ${PHP}-mysql ${PHP}-mbstring ${PHP}-xml \
  ${PHP}-curl ${PHP}-zip ${PHP}-gd ${PHP}-intl ${PHP}-bcmath \
  libapache2-mod-php${PHP_VER}

# Sicherstellen, dass Apache die richtige PHP-Version nutzt.
a2enmod php${PHP_VER} rewrite mpm_prefork >/dev/null 2>&1 || true
# Andere PHP-Versionen für Apache deaktivieren (falls vorhanden).
for OTHER in 8.1 8.2 8.3 8.4; do
  [ "$OTHER" != "$PHP_VER" ] && a2dismod php${OTHER} mpm_event >/dev/null 2>&1 || true
done

# ----------------------------------------------------------------------------
# Bundle-Erkennung: vendor/ und public/build/ aus deploy/bundle.sh?
# ----------------------------------------------------------------------------
HAVE_VENDOR=false; [ -f vendor/autoload.php ]        && HAVE_VENDOR=true
HAVE_BUILD=false;  [ -f public/build/manifest.json ] && HAVE_BUILD=true

if [ "$HAVE_VENDOR" = false ]; then
  die "vendor/ fehlt. Bitte das Paket mit 'bash deploy/bundle.sh' auf dem Entwickler-Rechner erzeugen und erneut übertragen."
fi
if [ "$HAVE_BUILD" = false ]; then
  die "public/build/ fehlt. Bitte das Paket mit 'bash deploy/bundle.sh' auf dem Entwickler-Rechner erzeugen und erneut übertragen."
fi

step "PHP-Abhängigkeiten und Frontend-Assets bereits im Paket enthalten – kein Download nötig"

# ----------------------------------------------------------------------------
# 3. Datenbank anlegen
# ----------------------------------------------------------------------------
step "Datenbank und Datenbank-Benutzer anlegen"
mariadb <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
info "Datenbank '${DB_NAME}', Benutzer '${DB_USER}' bereit."

# ----------------------------------------------------------------------------
# 4. .env erstellen
# ----------------------------------------------------------------------------
step "Konfiguration (.env) erstellen"
if [ ! -f .env ]; then
  cp deploy/.env.production.example .env
fi
set_env() {
  local key="$1"; local val="$2"
  if grep -qE "^${key}=" .env; then
    val="$(printf '%s' "$val" | sed -e 's/[\/&|]/\\&/g')"
    sed -i -E "s|^${key}=.*|${key}=${val}|" .env
  else
    echo "${key}=${val}" >> .env
  fi
}
set_env APP_URL      "http://${SERVER_NAME}"
set_env DB_DATABASE  "${DB_NAME}"
set_env DB_USERNAME  "${DB_USER}"
set_env DB_PASSWORD  "${DB_PASS}"

# ----------------------------------------------------------------------------
# 5. App-Key, Migrationen, Grunddaten
# ----------------------------------------------------------------------------
step "App-Key erzeugen"
${PHP} artisan key:generate --force

step "Datenbank migrieren"
${PHP} artisan migrate --force

step "Standardsoftware-Katalog einspielen"
${PHP} artisan db:seed --class='Database\Seeders\SoftwareCatalogSeeder' --force

step "Admin-Zugang anlegen"
${PHP} artisan tinker --execute="\App\Models\AdminUser::updateOrCreate(['email' => '${ADMIN_EMAIL}'], ['name' => '${ADMIN_NAME}', 'role' => 'admin', 'password' => '${ADMIN_PASS}']);" >/dev/null
info "Admin angelegt: ${ADMIN_EMAIL}"

# ----------------------------------------------------------------------------
# 6. Caches aufbauen
# ----------------------------------------------------------------------------
step "Symlink und Konfigurations-Caches erstellen"
${PHP} artisan storage:link || true
${PHP} artisan config:cache
${PHP} artisan route:cache
${PHP} artisan view:cache

# ----------------------------------------------------------------------------
# 7. Berechtigungen (NACH den Caches – alles von root Erstellte an www-data übergeben)
# ----------------------------------------------------------------------------
step "Dateiberechtigungen setzen"
mkdir -p storage/app/backups
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

# ----------------------------------------------------------------------------
# 8. Apache-VirtualHost
# ----------------------------------------------------------------------------
step "Apache-VirtualHost einrichten"
VHOST=/etc/apache2/sites-available/laptop-austausch.conf
sed -e "s|__APP_DIR__|${APP_DIR}|g" -e "s|__SERVER_NAME__|${SERVER_NAME}|g" \
  deploy/apache-vhost.conf > "$VHOST"
a2ensite laptop-austausch >/dev/null
a2dissite 000-default >/dev/null 2>&1 || true
apache2ctl configtest
systemctl reload apache2

# ----------------------------------------------------------------------------
# Fertig
# ----------------------------------------------------------------------------
DB_PASS_FILE="${APP_DIR}/storage/app/db-zugang.txt"
{
  echo "Datenbank: ${DB_NAME}"
  echo "Benutzer:  ${DB_USER}"
  echo "Passwort:  ${DB_PASS}"
} > "$DB_PASS_FILE"
chmod 600 "$DB_PASS_FILE"

echo
step "Fertig!"
info "App-Adresse:     http://${SERVER_NAME}"
info "Admin-Anmeldung: http://${SERVER_NAME}/admin   (${ADMIN_EMAIL})"
info "DB-Passwort liegt in: ${DB_PASS_FILE}"
echo
warn "Nächste Schritte im Admin-Panel (http://${SERVER_NAME}/admin):"
info "  1. Mitarbeitende-CSV importieren (Menü 'Mitarbeitende')."
info "  2. Zeitfenster erzeugen (Kalender → 'Slots generieren')."
info "  3. Standardsoftware-Katalog prüfen/ergänzen."
