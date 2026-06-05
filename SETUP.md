# Einrichtung auf einem neuen Rechner (Windows + WSL2)

Schritt-für-Schritt-Anleitung, um das Projekt **Laptop-Austausch** auf einem
zweiten Windows-Laptop zum Laufen zu bringen. Alle Befehle laufen in **WSL2
(Ubuntu)** – das ist dasselbe Linux wie auf dem späteren Server, deshalb passt
alles 1:1 zusammen.

> Tipp: Kopiere jeden Block einzeln und führe ihn aus. Bei Fehlern STOP und nachsehen.

---

## 0. WSL2 + Ubuntu installieren (nur Windows-Seite)

1. **PowerShell als Administrator** öffnen (Startmenü → „PowerShell" → Rechtsklick → „Als Administrator ausführen").
2. WSL + Ubuntu installieren:
   ```powershell
   wsl --install -d Ubuntu
   ```
3. **Windows neu starten**, wenn dazu aufgefordert.
4. Nach dem Neustart öffnet sich automatisch ein Ubuntu-Fenster. Lege einen
   **Benutzernamen** und ein **Passwort** an (merken – das ist dein sudo-Passwort).

Ab jetzt laufen alle Befehle **im Ubuntu-Terminal**.

---

## 1. Ubuntu aktualisieren

```bash
sudo apt update && sudo apt upgrade -y
```

---

## 2. Git installieren

```bash
sudo apt install -y git
git --version
```

---

## 3. PHP 8.3 + benötigte Erweiterungen

```bash
sudo apt install -y php8.3 php8.3-cli php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-intl php8.3-bcmath php8.3-gd
php -v        # sollte PHP 8.3.x zeigen
php -m | grep -E 'intl|pdo_mysql|mbstring'   # alle drei müssen erscheinen
```

> `php8.3-intl` ist für das Admin-Panel (Filament) zwingend nötig.

---

## 4. Composer installieren (PHP-Paketmanager)

```bash
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
composer --version
```

---

## 5. Node.js 20 + npm installieren

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v       # v20.x
npm -v
```

---

## 6. MariaDB (Datenbank) installieren & starten

```bash
sudo apt install -y mariadb-server
sudo service mariadb start
```

Datenbank + Benutzer anlegen (genau diese Namen, damit alles passt):

```bash
sudo mariadb -e "
CREATE DATABASE IF NOT EXISTS laptop_austausch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS laptop_austausch_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'laptop_app'@'127.0.0.1' IDENTIFIED BY 'laptop_dev_pw';
CREATE USER IF NOT EXISTS 'laptop_app'@'localhost' IDENTIFIED BY 'laptop_dev_pw';
GRANT ALL PRIVILEGES ON laptop_austausch.* TO 'laptop_app'@'127.0.0.1';
GRANT ALL PRIVILEGES ON laptop_austausch.* TO 'laptop_app'@'localhost';
GRANT ALL PRIVILEGES ON laptop_austausch_test.* TO 'laptop_app'@'127.0.0.1';
GRANT ALL PRIVILEGES ON laptop_austausch_test.* TO 'laptop_app'@'localhost';
FLUSH PRIVILEGES;
"
```

> `laptop_dev_pw` ist nur ein lokales Entwicklungs-Passwort. Auf dem echten
> Server (Phase 9) wird ein anderes, sicheres Passwort verwendet.

MariaDB nach jedem WSL-Neustart wieder starten mit: `sudo service mariadb start`

---

## 7. Projekt von GitHub holen

```bash
cd ~
git clone https://github.com/eliasmhmd/laptop-austausch.git
cd laptop-austausch
```

> Falls der Repo-Name/URL anders lautet: auf GitHub den grünen „Code"-Button →
> HTTPS-Adresse kopieren und oben einsetzen.
>
> Ist das Repository **privat**, fragt Git nach Anmeldedaten. Als Passwort nicht
> dein GitHub-Passwort eingeben, sondern einen **Personal Access Token**
> (GitHub → Settings → Developer settings → Personal access tokens → „Generate new
> token (classic)", Haken bei `repo`, Token kopieren und als Passwort einfügen).

---

## 8. PHP- und JS-Pakete installieren

```bash
composer install
npm install
```

---

## 9. Konfiguration (.env) einrichten

Die Datei `.env` ist **nicht** in Git (sie enthält lokale Einstellungen). Neu anlegen:

```bash
cp .env.example .env
php artisan key:generate
```

Dann `.env` öffnen (`nano .env`) und das Datenbank-Passwort eintragen:

```
DB_PASSWORD=laptop_dev_pw
```

Speichern in nano: `Strg+O`, Enter, dann `Strg+X`.

---

## 10. Datenbank füllen (Tabellen + Testdaten)

```bash
php artisan migrate:fresh --seed
```

Das legt 150 Beispiel-Mitarbeitende, alle Zeitfenster (10.08.–11.09.2026),
2 Admin-Konten und 50 Test-Buchungen an.

---

## 11. Frontend bauen

```bash
npm run build
```

---

## 12. Starten & im Browser ansehen

```bash
php artisan serve
```

Im Windows-Browser öffnen:

- **Mitarbeiter-Portal:** http://127.0.0.1:8000
  Login = KVGG-Nummer + PC-Nummer (Beispieldaten siehe unten)
- **Admin-Panel:** http://127.0.0.1:8000/admin
  - `admin@kreisgg.de` / `password`  (Vollzugriff)
  - `viewer@kreisgg.de` / `password`  (nur Lesen)

Server stoppen: **Strg+C**.

### Test-Login für das Mitarbeiter-Portal finden

Die Beispieldaten sind zufällig. Ein gültiges Login-Paar anzeigen:

```bash
php artisan tinker --execute="\$e = App\Models\Employee::first(); echo 'KVGG: '.\$e->kvgg_nummer.'  PC: '.\$e->pc_nummer.PHP_EOL;"
```

---

## 13. (Optional) Tests laufen lassen

```bash
php artisan test
```

Sollte „32 passed" zeigen.

---

## Häufige Probleme

| Problem | Lösung |
|---|---|
| `could not find driver` | `php8.3-mysql` fehlt → Schritt 3 nachholen |
| Admin-Panel-Fehler zu `intl` | `php8.3-intl` fehlt → Schritt 3 nachholen |
| `Access denied for user 'laptop_app'` | Schritt 6 (DB-Benutzer) erneut ausführen |
| `vite: not found` beim Build | `npm install` vergessen → Schritt 8 |
| MariaDB nicht erreichbar nach Neustart | `sudo service mariadb start` |
