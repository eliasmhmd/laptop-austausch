# Installation auf dem Server (Debian + Apache + MariaDB)

Diese Anleitung bringt **Laptop-Austausch** auf einen frischen Debian-Server.
Du brauchst dafür im Grunde nur **drei Schritte**: Code auf den Server bringen,
ein Skript starten, Fragen beantworten. Das Skript erledigt den Rest automatisch
(PHP, Composer, Node, Datenbank, Apache, Berechtigungen, Admin-Zugang).

> Du brauchst: SSH-Zugang zum Server und **root**-Rechte (oder `sudo`).
> Die App läuft danach über **HTTP** unter der Server-IP. HTTPS/Domain kann
> später ergänzt werden.

---

## Schritt 1 — Code auf den Server bringen

Melde dich per SSH am Server an, z. B.:

```bash
ssh root@SERVER-IP
```

Installiere Git und lege das Projektverzeichnis an:

```bash
apt-get update && apt-get install -y git
mkdir -p /var/www
```

Jetzt den Code holen — **eine** der beiden Varianten:

### Variante A — direkt von GitHub klonen (empfohlen, einfache Updates später)

```bash
git clone https://github.com/eliasmhmd/laptop-austausch.git /var/www/laptop-austausch
```

Falls das Repository **privat** ist, fragt Git nach Benutzername + Passwort.
Als Passwort brauchst du einen **Personal Access Token** (GitHub →
Settings → Developer settings → Personal access tokens → „Generate new token
(classic)", Haken bei `repo`). Den Token als Passwort eingeben.

### Variante B — vom Laptop hochladen (kein GitHub-Zugang nötig)

Auf **deinem Laptop** (im Projektordner) ausführen:

```bash
# .git und node_modules werden mit hochgeladen bzw. neu erzeugt – das ist ok.
rsync -av --exclude node_modules --exclude vendor --exclude .env \
  ./ root@SERVER-IP:/var/www/laptop-austausch/
```

Hast du kein `rsync`, geht auch `scp -r`.

> **Wichtig:** Niemals die `.env`-Datei oder echte Mitarbeiterdaten mit hochladen
> oder ins Git geben. Das Skript erzeugt auf dem Server eine eigene `.env`.

### Variante C — per USB-Stick (kein Netzwerkzugang zum Server nötig)

Ideal, wenn der Server nicht im selben Netz erreichbar ist. Hier wird **alles
Aufwändige schon auf dem Laptop vorbereitet** (PHP-Bibliotheken herunterladen,
Frontend bauen), sodass der Server dafür **kein Internet** und **kein
Composer/Node/npm** braucht.

1. Auf **deinem Laptop** (im Projektordner) das Paket erzeugen:

   ```bash
   bash deploy/bundle.sh
   ```

   Das erstellt die Datei `laptop-austausch-paket.tar.gz` (enthält den Code
   **inklusive** `vendor/` und der fertig gebauten Assets, **ohne** `.env`).

2. Diese eine Datei auf den USB-Stick kopieren.

3. Am Server den Stick einstecken, die Datei z. B. nach `/var/www` kopieren und
   auspacken:

   ```bash
   mkdir -p /var/www && cd /var/www
   cp /media/usb/laptop-austausch-paket.tar.gz .   # Pfad ggf. anpassen
   tar -xzf laptop-austausch-paket.tar.gz
   ```

   Der Code liegt danach in `/var/www/laptop-austausch`.

> Der Server braucht dann nur noch Internet für die **Systempakete** (PHP, Apache,
> MariaDB via `apt`). Ob er ins Internet kommt, prüfst du mit:
>
> ```bash
> ping -c1 deb.debian.org
> ```
>
> Klappt das, läuft `setup.sh` einfach durch. Hat der Server **gar kein**
> Internet, müssen auch diese Systempakete vorab als `.deb`-Dateien auf einem
> baugleichen Debian-Rechner besorgt werden (`apt-get install --download-only …`)
> — das ist deutlich aufwändiger; in dem Fall vorab Bescheid geben.

---

## Schritt 2 — Installationsskript starten

Auf dem **Server**:

```bash
cd /var/www/laptop-austausch
sudo bash deploy/setup.sh
```

Das Skript fragt nacheinander:

1. **Adresse** der App – einfach mit Enter bestätigen (nimmt die Server-IP).
2. **Admin-Name** – z. B. dein Name oder „IT Kreis Groß-Gerau".
3. **Admin-E-Mail** – damit meldest du dich später im Admin-Panel an.
4. **Admin-Passwort** – frei wählbar (mind. 8 Zeichen).

Danach läuft alles automatisch (dauert ein paar Minuten). Am Ende zeigt es dir
die App-Adresse und die Admin-Anmeldung an.

Das Skript installiert/erledigt:

- PHP 8.3 inkl. aller nötigen Erweiterungen (u. a. `intl`, `gd`, `mysql`, `zip`)
- Composer + Node.js 20, Frontend-Build (`npm run build`)
- `mariadb-server` **und** `mariadb-client` (Letzteres für die Datensicherung)
- Datenbank `laptop_austausch` + Benutzer `laptop_app` (Passwort wird erzeugt
  und in `storage/app/db-zugang.txt` abgelegt)
- `.env` mit korrekten Werten (u. a. `SESSION_DRIVER=file`, `APP_LOCALE=de`)
- Datenbank-Migration + Standardsoftware-Katalog
- deinen Admin-Zugang
- Dateiberechtigungen + Apache-VirtualHost

---

## Schritt 3 — Im Admin-Panel die echten Daten einrichten

Öffne im Browser `http://SERVER-IP/admin` und melde dich mit deiner Admin-E-Mail
und deinem Passwort an. Dann:

1. **Mitarbeitende importieren** – Menü „Mitarbeitende" → Import, die echte
   CSV hochladen (Spalten: `PC-Nummer`, `Login`, `Vorname`, `Nachname`,
   `eMail-Adresse`, `Fachabteilung`).
2. **Zeitfenster erzeugen** – im **Kalender** auf „Slots generieren" und den
   Zeitraum wählen (z. B. 10.08.2026 – 11.09.2026).
3. **Standardsoftware** im „Software-Katalog" prüfen/ergänzen.

Fertig – die Mitarbeitenden können sich nun unter `http://SERVER-IP` mit
**KVGG-Nummer + PC-Nummer** anmelden und Termine buchen.

---

## Updates später einspielen (Bundle-Workflow)

Der Server hat **kein** Git/Composer/Node. Updates laufen daher immer über ein
Paket, das auf dem **Entwickler-Laptop** gebaut und auf den Server übertragen
wird. Drei Schritte:

**1. Auf dem Laptop** (im Projektordner, nachdem alle Änderungen committet sind):

```bash
bash deploy/bundle.sh
```

Erzeugt `laptop-austausch-paket.tar.gz` (Code **inkl.** `vendor/` und fertig
gebauter Assets, **ohne** `.env`). Per USB/Cloud auf den Server bringen.

**2. Auf dem Server** das Paket im **Elternordner** der Installation auspacken –
es legt sich direkt über die bestehenden Dateien:

```bash
cd /var/www
tar -xzf laptop-austausch-paket.tar.gz    # überschreibt Code; .env + storage/ bleiben erhalten
```

**3. Update-Skript starten:**

```bash
cd /var/www/laptop-austausch
sudo bash deploy/update.sh
```

Das Skript schaltet in den Wartungsmodus, **migriert die Datenbank** und erneuert
die Caches. `.env` und `storage/` (Backups, Daten) bleiben unangetastet.

> **Vor jeder Migration im Produktivbetrieb wird automatisch eine Sicherung
> erstellt** (unter `storage/app/backups/`). Zur Sicherheit kannst du vorher
> zusätzlich im Admin-Panel unter **„Datensicherung"** ein Backup erstellen und
> herunterladen.

### Dieses Update (Stand 2026-06-15) enthält

- Neue Admin-Seiten **Warteschlange**, **Erinnerungen** und **Einstellungen**
- Neue Seite **Einstellungen** → dort den **Raum** pflegen (z. B. „Raum 345"); er erscheint
  auf der Bestätigungsseite der Mitarbeitenden und im Kalender-Eintrag (.ics)
- Login: Groß-/Kleinschreibung egal, Sperre nach 5 Fehlversuchen, Logo + Hinweis
- Massenlöschen (Buchungen/Software-Katalog), „Alles zurücksetzen" im Kalender
- Geänderter Buchungsablauf (erst Software, dann Bestätigung), Verschieben über den Kalender
- **DB-Migrationen `add_reviewed_at_to_bookings_table` und `create_settings_table`** → laufen
  automatisch in Schritt 3 (vorher Auto-Backup). Es sind **keine** manuellen DB-Schritte nötig.

Nach dem Update prüfen: Login mit Logo erscheint, im Admin-Panel sind „Warteschlange",
„Erinnerungen" und „Einstellungen" in der Navigation, bestehende Buchungen sind unverändert da
(sie landen zunächst im Reiter „Offen" der Warteschlange). Anschließend unter **Einstellungen**
den Raum eintragen.

---

## Datensicherung

- **Automatisch:** vor jeder Migration (siehe oben).
- **Manuell:** im Admin-Panel unter **„Datensicherung"** (nur Admin-Rolle) –
  Backup erstellen, herunterladen, löschen oder aus einer `.sql`-Datei
  wiederherstellen.
- Die Sicherungen liegen lokal in `storage/app/backups/` und sind **nicht** im
  Git (sie enthalten Personendaten).

---

## Wenn etwas klemmt

- **Weiße Seite / Fehler 500:** Detailmeldung anzeigen lassen:
  `tail -n 50 storage/logs/laravel.log`
- **„403 Forbidden" oder es lädt nur das Verzeichnis:** mod_rewrite fehlt –
  `a2enmod rewrite && systemctl reload apache2`.
- **Anmeldung schlägt sofort fehl:** prüfen, dass in `.env`
  `SESSION_DRIVER=file` steht, danach `php8.3 artisan config:cache`.
- **Backups gehen nicht:** prüfen, dass `mysqldump` da ist (`which mysqldump`);
  notfalls `apt-get install -y mariadb-client`.
- **Nach Code-Änderungen wirkt nichts:** Caches erneuern –
  `php8.3 artisan config:cache && php8.3 artisan route:cache && php8.3 artisan view:cache`.

> Hinweis: Die Skripte sind für einen Standard-Debian-Server geschrieben, aber
> auf deinem konkreten Server noch nicht erprobt. Falls eine Stelle hakt, kopier
> die Fehlermeldung – damit lässt sich gezielt nachbessern.
