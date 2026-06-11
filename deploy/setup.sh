#!/usr/bin/env bash
#
# Automatische Erstinstallation von Laptop-Austausch auf einem frischen
# Debian-Server (Apache + MariaDB). Als root ausführen, AUS dem Projektordner:
#
#   sudo bash deploy/setup.sh
#
# Das Skript installiert PHP 8.3 + Erweiterungen, Composer, Node, richtet die
# Datenbank, die .env, die Berechtigungen und den Apache-VirtualHost ein und
# legt einen Admin-Zugang an. Es ist gefahrlos mehrfach ausführbar.

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
PHP="php8.3"

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

# DB-Passwort automatisch erzeugen (nur unkritische Zeichen).
DB_PASS="$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | cut -c1-24)"

# ----------------------------------------------------------------------------
# 1. Systempakete
# ----------------------------------------------------------------------------
step "Systempakete aktualisieren und Grundwerkzeuge installieren"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y ca-certificates curl wget gnupg lsb-release apt-transport-https unzip git openssl

step "Apache und MariaDB installieren (falls noch nicht vorhanden)"
apt-get install -y apache2 mariadb-server mariadb-client
systemctl enable --now apache2
systemctl enable --now mariadb

# ----------------------------------------------------------------------------
# 2. PHP 8.3 (über das Sury-Repository – funktioniert auf allen Debian-Versionen)
# ----------------------------------------------------------------------------
step "PHP 8.3 installieren"
if ! command -v "$PHP" >/dev/null 2>&1; then
  install -d -m 0755 /etc/apt/keyrings
  curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/keyrings/sury-php.gpg
  echo "deb [signed-by=/etc/apt/keyrings/sury-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
    > /etc/apt/sources.list.d/sury-php.list
  apt-get update -y
fi
apt-get install -y \
  ${PHP} ${PHP}-cli ${PHP}-common ${PHP}-mysql ${PHP}-mbstring ${PHP}-xml \
  ${PHP}-curl ${PHP}-zip ${PHP}-gd ${PHP}-intl ${PHP}-bcmath \
  libapache2-mod-${PHP}

# Sicherstellen, dass Apache PHP 8.3 nutzt.
a2enmod ${PHP} rewrite >/dev/null 2>&1 || true
a2dismod php8.4 php8.2 php8.1 mpm_event >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

# ----------------------------------------------------------------------------
# 3. Composer
# ----------------------------------------------------------------------------
step "Composer installieren"
if ! command -v composer >/dev/null 2>&1; then
  EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
  curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
  ACTUAL="$(${PHP} -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
  [ "$EXPECTED" = "$ACTUAL" ] || die "Composer-Installer-Prüfsumme stimmt nicht."
  ${PHP} /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
fi

# ----------------------------------------------------------------------------
# 4. Node.js 20 (für den Asset-Build mit Vite)
# ----------------------------------------------------------------------------
step "Node.js 20 installieren"
if ! command -v node >/dev/null 2>&1; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y nodejs
fi

# ----------------------------------------------------------------------------
# 5. Datenbank anlegen
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
# 6. .env erstellen
# ----------------------------------------------------------------------------
step "Konfiguration (.env) erstellen"
if [ ! -f .env ]; then
  cp deploy/.env.production.example .env
fi
set_env() { # set_env SCHLÜSSEL WERT
  local key="$1"; local val="$2"
  if grep -qE "^${key}=" .env; then
    # Wert escapen (für sed) und setzen.
    val="$(printf '%s' "$val" | sed -e 's/[\/&|]/\\&/g')"
    sed -i -E "s|^${key}=.*|${key}=${val}|" .env
  else
    echo "${key}=${val}" >> .env
  fi
}
set_env APP_URL "http://${SERVER_NAME}"
set_env DB_DATABASE "${DB_NAME}"
set_env DB_USERNAME "${DB_USER}"
set_env DB_PASSWORD "${DB_PASS}"

# ----------------------------------------------------------------------------
# 7. Abhängigkeiten + Build
# ----------------------------------------------------------------------------
step "PHP-Abhängigkeiten installieren (composer)"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

step "Frontend-Assets bauen (npm)"
npm ci
npm run build

step "App-Key erzeugen"
${PHP} artisan key:generate --force

# ----------------------------------------------------------------------------
# 8. Datenbank migrieren + Grunddaten
# ----------------------------------------------------------------------------
step "Datenbank migrieren"
${PHP} artisan migrate --force

step "Standardsoftware-Katalog einspielen"
${PHP} artisan db:seed --class='Database\Seeders\SoftwareCatalogSeeder' --force

step "Admin-Zugang anlegen"
${PHP} artisan tinker --execute="\App\Models\AdminUser::updateOrCreate(['email' => '${ADMIN_EMAIL}'], ['name' => '${ADMIN_NAME}', 'role' => 'admin', 'password' => '${ADMIN_PASS}']);" >/dev/null
info "Admin angelegt: ${ADMIN_EMAIL}"

# ----------------------------------------------------------------------------
# 9. Caches aufbauen
# ----------------------------------------------------------------------------
step "Symlink und Konfigurations-Caches erstellen"
${PHP} artisan storage:link || true
${PHP} artisan config:cache
${PHP} artisan route:cache
${PHP} artisan view:cache

# ----------------------------------------------------------------------------
# 10. Berechtigungen (NACH den Caches – alles von root Erstellte an www-data übergeben)
# ----------------------------------------------------------------------------
step "Dateiberechtigungen setzen"
mkdir -p storage/app/backups
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

# ----------------------------------------------------------------------------
# 11. Apache-VirtualHost
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
step "Fertig! 🎉"
info "App-Adresse:     http://${SERVER_NAME}"
info "Admin-Anmeldung: http://${SERVER_NAME}/admin   (${ADMIN_EMAIL})"
info "DB-Passwort liegt in: ${DB_PASS_FILE}"
echo
warn "Nächste Schritte im Admin-Panel (http://${SERVER_NAME}/admin):"
info "  1. Mitarbeitende-CSV importieren (Menü 'Mitarbeitende')."
info "  2. Zeitfenster erzeugen (Kalender → 'Slots generieren')."
info "  3. Standardsoftware-Katalog prüfen/ergänzen."
