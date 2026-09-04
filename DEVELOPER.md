# Entwickler-Handbuch — Laptop-Austausch-Buchungssystem
**Kreis Groß-Gerau · IT-Center** · Stand: Juni 2026

> Diese Dokumentation richtet sich an Entwickler, die das Projekt übernehmen, erweitern oder pflegen. Sie setzt grundlegende Laravel-Kenntnisse voraus, erklärt aber alle projektspezifischen Besonderheiten und Abweichungen vom Laravel-Standard ausführlich.

---

## Inhaltsverzeichnis

1. [Projektübersicht](#1-projektübersicht)
2. [Tech Stack](#2-tech-stack)
3. [Projektstruktur](#3-projektstruktur)
4. [Datenbank](#4-datenbank)
5. [Backend-Architektur](#5-backend-architektur)
6. [Frontend](#6-frontend)
7. [Installation & Lokales Setup](#7-installation--lokales-setup)
8. [Wichtige Dependencies](#8-wichtige-dependencies)
9. [Migrationen & Seeding](#9-migrationen--seeding)
10. [Deployment](#10-deployment)
11. [Umgebungsvariablen (.env)](#11-umgebungsvariablen-env)
12. [Composer- & npm-Scripts](#12-composer---npm-scripts)
13. [Testing](#13-testing)
14. [Tipps für Weiterentwicklung](#14-tipps-für-weiterentwicklung)

---

## 1. Projektübersicht

Das **Laptop-Austausch-Buchungssystem** ist eine Laravel-Self-Service-Webanwendung für den Kreis Groß-Gerau. Sie ermöglicht ca. 150 Mitarbeitenden, selbst einen Termin für die Übergabe ihres alten Laptops und die Abholung eines neuen Geräts zu buchen. Das IT-Center verwaltet den Prozess über ein Filament-Admin-Panel.

### Kernanforderungen
- **Keine E-Mail-Funktionalität** — der Produktionsserver hat keinen Mailzugang. Bestätigungen erfolgen On-Page + iCal-Download.
- **Keine externen APIs** — die App läuft im internen Netz ohne Internetverbindung.
- **Sprache** — vollständig Deutsch (`locale: de`, `faker: de_DE`).
- **Deployment ohne Internet** — der Server hat keinen Zugang zu Composer/npm. Alle Assets werden im Bundle vorgebaut.

### Systemgrenzen
- ~150 Mitarbeitende, 1 Termin pro Person
- 8 Zeitfenster/Tag (08:00–15:00 Uhr, je 1 Stunde, Kapazität 1)
- Kein öffentlicher Zugang — nur internes Behördennetz

---

## 2. Tech Stack

| Kategorie | Technologie | Version | Anmerkung |
|---|---|---|---|
| **Sprache** | PHP | ^8.3 (dev), 8.4 (prod) | |
| **Framework** | Laravel | ^13.8 | Nicht v11 (ursprüngliche Spec) |
| **Admin-Panel** | Filament | ^4.0 | Nicht v3 (Filament v3 läuft nicht auf Laravel 13) |
| **Datenbank** | MariaDB | lokal + prod | Verhält sich wie MySQL, Collation case-insensitiv |
| **Frontend-Build** | Vite | ^8.0 | Mit `laravel-vite-plugin` |
| **CSS** | Tailwind CSS | ^4.0 | Via `@tailwindcss/vite` Plugin |
| **JS** | Alpine.js | ^3.15 | Für Live-Verfügbarkeit und Tag-Input |
| **PDF** | barryvdh/laravel-dompdf | ^3.1 | Imaging-Blätter |
| **Excel** | maatwebsite/excel | ^3.1 | Buchungsexport |
| **Webserver (prod)** | Apache | 2.x | Debian 13 Trixie |
| **Betriebssystem (prod)** | Debian 13 Trixie | — | PHP 8.4 aus offiziellen Debian-Repos |
| **Paketmanager (PHP)** | Composer | 2.x | |
| **Paketmanager (JS)** | npm | — | |

### Abweichungen von der ursprünglichen Spec
> ⚠️ Die ursprüngliche Spezifikation nannte Laravel 11 + Filament v3. Beides ist **falsch** und wurde bereits überrissen:
> - **Laravel 13** ist installiert — Filament v4 setzt Laravel 12+ voraus.
> - **Filament v4** bricht stark mit v3 (Resource-Split in `Tables/`, `Schemas/`, `Pages/`). Nicht vermischen.
> - **MariaDB statt SQLite** — PHP auf dem Entwickler-Rechner hat kein `pdo_sqlite`.

---

## 3. Projektstruktur

```
laptop-austausch/
│
├── app/
│   ├── Filament/                    # Filament v4 Admin-Panel
│   │   ├── Pages/                   # Custom Pages (keine Resources)
│   │   │   ├── Kalender.php         # Startseite des Panels (überschreibt getRoutePath → '/')
│   │   │   ├── Warteschlange.php    # Review-Queue für IT
│   │   │   ├── Erinnerungen.php     # Liste: Mitarbeitende ohne Termin
│   │   │   ├── Einstellungen.php    # Ort, Software-Texte, Footer (admin-only)
│   │   │   ├── SystemBackups.php    # SQL-Backup erstellen/wiederherstellen (admin-only)
│   │   │   └── Downloads.php        # Dokumente für Mitarbeitende hochladen (admin-only)
│   │   └── Resources/               # Filament Resources (je aufgeteilt in Unterordner)
│   │       ├── Bookings/
│   │       │   ├── BookingResource.php
│   │       │   ├── Pages/           # ListBookings, ViewBooking
│   │       │   ├── Schemas/         # BookingInfolist
│   │       │   └── Tables/          # BookingsTable
│   │       ├── Employees/
│   │       │   ├── EmployeeResource.php
│   │       │   ├── Pages/           # ListEmployees, ViewEmployee
│   │       │   ├── Schemas/         # EmployeeInfolist
│   │       │   └── Tables/          # EmployeesTable
│   │       ├── SoftwareCatalogs/
│   │       │   ├── SoftwareCatalogResource.php
│   │       │   ├── Pages/           # List, Create, Edit, View
│   │       │   ├── RelationManagers/ # UsageRelationManager (Verwendet von)
│   │       │   ├── Schemas/
│   │       │   └── Tables/
│   │       └── AdminUsers/
│   │           ├── AdminUserResource.php
│   │           ├── Pages/           # List, Create, Edit, View
│   │           ├── Schemas/
│   │           └── Tables/
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── EmployeeAuthController.php  # Login/Logout Mitarbeitende
│   │   │   ├── Admin/
│   │   │   │   ├── ImagingSheetController.php  # PDF-Export (eigene Auth-Prüfung)
│   │   │   │   ├── BackupController.php         # Backup-Download (eigene Auth-Prüfung)
│   │   │   │   └── DownloadController.php       # Dokument-Download Admin (eigene Auth-Prüfung)
│   │   │   ├── Concerns/
│   │   │   │   └── BuildsSlotCalendar.php       # Trait: Kalender-Daten aufbereiten
│   │   │   ├── BookingController.php            # Kalender, Buchen, iCal, Verfügbarkeit-API
│   │   │   ├── DashboardController.php          # Mitarbeitenden-Dashboard
│   │   │   ├── DownloadController.php           # Dokument-Download für Mitarbeitende
│   │   │   ├── LaptopConfigController.php       # Software-Formular
│   │   │   └── RescheduleController.php         # Termin verschieben
│   │   └── Middleware/              # Standard Laravel-Middleware
│   │
│   ├── Models/
│   │   ├── AdminUser.php            # Admin-Panel-Benutzer (FilamentUser)
│   │   ├── Booking.php              # Buchung (Employee ↔ TimeSlot)
│   │   ├── BookingSoftware.php      # Pivot: Buchung ↔ Software
│   │   ├── DownloadFile.php         # Metadaten hochgeladener Dokumente
│   │   ├── Employee.php             # Mitarbeitende (Authenticatable, Guard 'employee')
│   │   ├── LaptopConfig.php         # Gerätekonfiguration einer Buchung
│   │   ├── Setting.php              # Key-Value-Store (Ort, Software-Texte, Footer)
│   │   ├── SoftwareCatalog.php      # Software-Katalog-Eintrag
│   │   ├── TimeSlot.php             # Zeitfenster
│   │   └── User.php                 # Standard Laravel User (ungenutzt, vorhanden wegen Migrationen)
│   │
│   ├── Providers/
│   │   └── AppServiceProvider.php   # Locale-Setup, Filament-Pagination, Auto-Backup vor Migrationen
│   │
│   └── Services/                    # Business-Logik (kein Code in Controllern/Filament-Actions)
│       ├── BookingManager.php        # create / cancel / move / releaseSlotsFor... / purgeAll...
│       ├── DatabaseBackupService.php # mysqldump, Restore, Listing
│       ├── DownloadFileService.php   # Upload/Delete von Admin-Dokumenten
│       ├── EmployeeImporter.php      # CSV → Employee (upsert)
│       ├── ImagingSheetExporter.php  # Daten für Imaging-PDF aufbereiten
│       ├── SlotGenerator.php         # Zeitfenster erzeugen (idempotent)
│       ├── SoftwareCatalogMerger.php # Zwei Katalog-Einträge zusammenführen
│       └── SoftwareCatalogResolver.php # Eintrag in Katalog auflösen/anlegen
│
├── config/
│   ├── auth.php                     # Guards: 'employee', 'admin', 'web'
│   ├── app.php                      # locale: 'de', faker_locale: 'de_DE'
│   └── filament.php                 # Panel-Konfiguration
│
├── database/
│   ├── factories/                   # Model Factories (Faker de_DE)
│   ├── migrations/                  # Alle Migrationen (chronologisch)
│   └── seeders/
│       ├── DatabaseSeeder.php       # Orchestrierung
│       ├── AdminUserSeeder.php      # 2 Admins
│       ├── EmployeeSeeder.php       # 150 Mitarbeitende
│       ├── TimeSlotSeeder.php       # 200 Slots (via SlotGenerator)
│       ├── BookingSeeder.php        # 50 Buchungen
│       └── SoftwareCatalogSeeder.php # Standard-Software-Katalog
│
├── deploy/
│   ├── bundle.sh                    # Paket bauen (Dev-Maschine)
│   ├── setup.sh                     # Erstinstallation (Server, als root)
│   ├── update.sh                    # Updates einspielen (Server, als root)
│   ├── .env.production.example      # Produktions-.env-Vorlage
│   └── apache-vhost.conf            # Apache VirtualHost-Vorlage
│
├── resources/
│   ├── css/app.css                  # Tailwind-Einstiegspunkt
│   ├── js/app.js                    # Alpine.js-Einstiegspunkt
│   └── views/
│       ├── auth/login.blade.php     # Mitarbeitenden-Login
│       ├── booking/                 # Kalender, Buchen, Termin ansehen
│       ├── config/edit.blade.php    # Software-Formular
│       ├── dashboard.blade.php      # Mitarbeitenden-Dashboard
│       ├── errors/                  # Benutzerdefinierte Fehlerseiten (404, 403, 419, 429, 500, 503)
│       ├── layouts/app.blade.php    # Haupt-Layout (Mitarbeitende)
│       ├── reschedule/              # Termin verschieben
│       ├── admin/imaging-sheet.blade.php # Imaging-PDF-Vorlage
│       └── filament/pages/          # Blade-Views für Custom Filament Pages
│           ├── kalender.blade.php
│           ├── warteschlange.blade.php
│           ├── erinnerungen.blade.php
│           ├── einstellungen.blade.php
│           ├── system-backups.blade.php
│           └── downloads.blade.php
│
├── routes/
│   └── web.php                      # Alle Routen (kein api.php, kein console.php relevant)
│
├── storage/
│   └── app/
│       ├── backups/                 # SQL-Dumps (gitignored, .gitignore: *)
│       └── downloads/               # Hochgeladene Admin-Dokumente (gitignored)
│
├── tests/
│   ├── Feature/                     # 188 Feature-Tests
│   └── Unit/
│
├── vite.config.js                   # Vite: Tailwind + Alpine + Bunny-Fonts
├── composer.json
├── package.json
├── phpunit.xml                      # Test-DB: laptop_austausch_test
├── CLAUDE.md                        # Projektdoku für Claude/AI (diese Datei)
├── DEPLOY.md                        # Deployment-Anleitung (Deutsch)
└── SETUP.md                         # Lokales Dev-Setup (Windows + WSL2)
```

---

## 4. Datenbank

### 4.1 Überblick

Die App nutzt **MariaDB** (kompatibel mit MySQL). Die Standard-Collation ist case-insensitiv — das ist wichtig beim Schreiben von Tests (keine `LOWER()`-Vergleiche in Assertions nötig, aber auch keine Groß-/Klein-Unterschiede testbar).

| Datenbank | Zweck |
|---|---|
| `laptop_austausch` | Entwicklung |
| `laptop_austausch_test` | Tests (`phpunit.xml`) |
| `laptop_austausch` (prod) | Produktion |

### 4.2 Schema (vollständig)

#### `admin_users`
```sql
id              BIGINT UNSIGNED PK
name            VARCHAR
email           VARCHAR UNIQUE
password        VARCHAR (bcrypt-gehasht)
role            ENUM('admin', 'viewer')
created_at, updated_at
```

#### `employees`
```sql
id                   BIGINT UNSIGNED PK
kvgg_nummer          VARCHAR UNIQUE     -- Login-Username (case-insensitiv)
vorname              VARCHAR
nachname             VARCHAR
email                VARCHAR
abteilung            VARCHAR
pc_nummer            VARCHAR            -- Login-Passwort (KLARTEXT — kein Hash!)
last_laptop_exchange DATE NULL
remember_token       VARCHAR NULL
created_at, updated_at
```
> ⚠️ **`pc_nummer` ist im Klartext gespeichert.** Begründung: (1) Es ist eine Geräte-Stickernummer, kein echtes Geheimnis. (2) Das Software-Formular füllt sie automatisch aus — ein Hash würde das verhindern.

#### `time_slots`
```sql
id             BIGINT UNSIGNED PK
slot_date      DATE
start_time     TIME
end_time       TIME
calendar_week  INT
status         ENUM('available', 'booked', 'blocked')
capacity       INT DEFAULT 1
booked_count   INT DEFAULT 0
created_by     FK → admin_users (nullable)
created_at, updated_at
```
> `time_slot_id` in `bookings` ist **kein UNIQUE-Constraint**, da stornierte Buchungen als Historie erhalten bleiben. Die "ein aktiver Termin pro Slot"-Regel wird über `booked_count < capacity` und Transaktionen durchgesetzt.

#### `bookings`
```sql
id                  BIGINT UNSIGNED PK
employee_id         FK → employees (cascadeDelete)
time_slot_id        FK → time_slots (cascadeDelete)
status              ENUM('confirmed', 'cancelled', 'completed', 'no_show', 'sick')
cancellation_reason TEXT NULL
unplanned_note      TEXT NULL          -- Notiz für krank/nicht erschienen
booked_at           TIMESTAMP NULL
reviewed_at         TIMESTAMP NULL     -- NULL = Warteschlange offen; gesetzt = bereit
created_at, updated_at
```

#### `software_catalog`
```sql
id           BIGINT UNSIGNED PK
name         VARCHAR
version      VARCHAR NULL
publisher    VARCHAR NULL
is_standard  BOOLEAN DEFAULT true
status       ENUM('pending', 'approved') DEFAULT 'approved'
submitted_by FK → employees NULL (nullOnDelete)
created_at, updated_at
```

#### `booking_software`
```sql
id                   BIGINT UNSIGNED PK
booking_id           FK → bookings (cascadeDelete)
software_catalog_id  FK → software_catalog NULL (nullOnDelete)
custom_software_name VARCHAR NULL       -- Legacy: für is_custom=true Einträge
is_custom            BOOLEAN DEFAULT false  -- Legacy-Feld
created_at, updated_at
```

#### `laptop_configs`
```sql
id                  BIGINT UNSIGNED PK
booking_id          FK → bookings UNIQUE (cascadeDelete)
old_manufacturer    VARCHAR NULL
old_serial          VARCHAR NULL
old_cpu             VARCHAR NULL
old_ram             VARCHAR NULL
old_storage         VARCHAR NULL
new_manufacturer    VARCHAR NULL
new_serial          VARCHAR NULL
new_cpu             VARCHAR NULL
new_ram             VARCHAR NULL
new_storage         VARCHAR NULL
additional_notes    TEXT NULL
created_at, updated_at
```

#### `settings`
```sql
id    BIGINT UNSIGNED PK
key   VARCHAR UNIQUE
value TEXT NULL
created_at, updated_at
```
Bekannte Keys: `austausch_raum`, `footer_text`, `software_intro_text`, `software_center_text`, `software_standard_programs`, `software_center_programs`, `software_warning_text`

#### `download_files`
```sql
id             BIGINT UNSIGNED PK
original_name  VARCHAR        -- Anzeigename (ursprünglicher Dateiname)
stored_name    VARCHAR UNIQUE -- UUID-basierter Dateiname auf Disk
mime_type      VARCHAR NULL
size           BIGINT UNSIGNED
uploaded_by    FK → admin_users NULL (nullOnDelete)
created_at, updated_at
```
Dateien liegen unter `storage/app/downloads/<stored_name>`.

### 4.3 Wichtige Eloquent-Beziehungen

```
Employee ──hasMany──► Booking ──hasOne──► LaptopConfig
                             ──hasMany──► BookingSoftware ──belongsTo──► SoftwareCatalog
         ──hasMany──► activeBookings()   (scope: status='confirmed')

TimeSlot ──hasMany──► Booking
         ──belongsTo──► AdminUser (creator)

SoftwareCatalog ──hasMany──► BookingSoftware
                ──belongsTo──► Employee (submittedBy, für pending-Einträge)
```

### 4.4 Das Setting-Modell

`Setting` ist kein normales Eloquent-Model — es wird **nur statisch** verwendet:

```php
// Lesen
Setting::room()                    // Ort (mit Fallback)
Setting::footer()                  // Footer-Text (mit Fallback)
Setting::standardPrograms()        // string[]
Setting::softwareCenterPrograms()  // string[]
Setting::softwareIntro()           // string
Setting::softwareCenterText()      // string
Setting::softwareWarning()         // string

// Schreiben
Setting::set('austausch_raum', 'Gebäude B, Raum 123');

// Generisch
Setting::get('my_key', 'fallback');

// Hilfsmethode für Formularfelder
[$heading, $body] = Setting::splitHeading($text); // 1. Zeile = Titel, Rest = Body
```

---

## 5. Backend-Architektur

### 5.1 Zwei Auth-Guards

Die App hat zwei völlig getrennte Authentifizierungssysteme:

| Guard | Modell | Login-Seite | Middleware | Verwendung |
|---|---|---|---|---|
| `employee` | `Employee` | `/login` | `auth:employee` | Mitarbeitenden-Portal |
| `admin` | `AdminUser` | `/admin/login` (Filament) | Filament-intern | Admin-Panel |

**Wichtig für Controller-Routen, die Admin-Zugang brauchen** (Imaging-PDF, Backup-Download, Dokument-Download): Diese können **nicht** die `auth:admin`-Middleware verwenden, da Laravel diese zur Standard-Login-Route (`/login`) weiterleiten würde — also zur Mitarbeitenden-Seite. Stattdessen prüfen diese Controller manuell:

```php
if (! Auth::guard('admin')->check()) {
    abort(403);
}
```

### 5.2 Services-Schicht

Die gesamte Business-Logik liegt in `app/Services/`. Controller und Filament-Actions delegieren dorthin.

#### `BookingManager`

Alle Buchungsoperationen laufen in `DB::transaction()` mit `lockForUpdate()` — kein Race-Condition-Risiko bei parallelen Zugriffen.

```php
$manager = app(BookingManager::class);

// Admin legt Buchung an
$booking = $manager->create($employeeId, $timeSlotId);

// Admin storniert
$manager->cancel($booking, 'Von der Verwaltung storniert');

// Admin verschiebt
$manager->move($booking, $newTimeSlot);

// Bulk-Operationen (vor dem Löschen aufrufen!)
$manager->releaseSlotsForEmployees($employeeIds); // gibt Slots frei
$manager->releaseSlotsForBookings($bookingIds);   // gibt Slots frei
$manager->purgeAllBookingsAndEmployees();          // Neustart-Runde
```

> ⚠️ **Wichtig:** Der Employee-seitige `BookingController` und `RescheduleController` haben **eigene Transaktions-Logik** (die gleiche Idee, aber separater Code). Eine Vereinheitlichung wäre ein sinnvoller Refactoring-Kandidat.

#### `SlotGenerator`

```php
$gen = app(SlotGenerator::class);
$result = $gen->generate('2026-09-01', '2026-09-30', $adminUserId);
// $result = ['created' => 176, 'existing' => 0, 'days' => 22]
```

Feiertage: `SlotGenerator::HOLIDAYS` (leeres Array — bei Bedarf mit `'Y-m-d'`-Strings befüllen).

#### `SoftwareCatalogResolver`

Wird im Software-Formular aufgerufen:

```php
$resolver = app(SoftwareCatalogResolver::class);
$catalogEntry = $resolver->resolve('AutoCAD', $employee->id);
// → sucht 'autocad' (case-insensitiv) in approved-Einträgen
// → sonst eigener pending-Eintrag der Person
// → sonst neuer pending-Eintrag (submitted_by = $employee->id)
```

#### `DatabaseBackupService`

Passwort wird via `MYSQL_PWD`-Umgebungsvariable an `mysqldump` übergeben (nicht als CLI-Argument, wegen `ps aux`-Sicherheit):

```php
$backup = app(DatabaseBackupService::class);
$filename = $backup->create();                      // 'backup_2026-06-15_10-30-00.sql'
$files    = $backup->all();                         // Collection von Backup-Infos
$backup->restore('/path/to/backup.sql');            // führt mysql-Restore aus
$backup->delete('backup_2026-06-15_10-30-00.sql'); // löscht Datei
```

### 5.3 Routen-Übersicht

```
GET  /                              → Redirect nach /dashboard
GET  /login                         → Employee Login-Formular         [guest:employee]
POST /login                         → Employee Login verarbeiten       [guest:employee]
POST /logout                        → Employee Logout                  [auth:employee]

GET  /dashboard                     → Mitarbeitenden-Dashboard         [auth:employee]
GET  /kalender                      → Buchungskalender                 [auth:employee]
GET  /buchen/{timeSlot}             → Buchungs-Bestätigungsseite       [auth:employee]
POST /buchen/{timeSlot}             → Buchung speichern                [auth:employee]
GET  /termin/{booking}              → Termin-Bestätigung               [auth:employee]
GET  /termin/{booking}/ical         → iCal-Download (.ics)             [auth:employee]
GET  /termin/{booking}/konfiguration → Software-Formular               [auth:employee]
POST /termin/{booking}/konfiguration → Software speichern              [auth:employee]
GET  /verschieben                   → Verschieben: Kalender            [auth:employee]
GET  /verschieben/{timeSlot}        → Verschieben: Bestätigung         [auth:employee]
POST /verschieben/{timeSlot}        → Verschieben: Durchführen         [auth:employee]
GET  /api/verfuegbarkeit            → Slot-Verfügbarkeit (JSON)        [auth:employee]
GET  /api/slot/{timeSlot}           → Einzelner Slot-Check (JSON)      [auth:employee]
GET  /dokumente/{downloadFile}      → Datei-Download für Mitarbeitende [auth:employee]

GET  /admin/exports/imaging/booking/{booking} → Imaging-PDF (Single)  [Admin-Check manuell]
GET  /admin/exports/imaging/day               → Imaging-PDF (Tag)      [Admin-Check manuell]
GET  /admin/backups/{filename}/download       → Backup-Download        [Admin-Check manuell]
GET  /admin/downloads/{downloadFile}/download → Dokument-Download      [Admin-Check manuell]

/admin/*                            → Filament Panel (alle Admin-Seiten)
```

> **`route:cache`-Kompatibilität:** Alle Route-Handler sind Controller-Methoden, keine Closures. `Route::redirect()` für die Startseite statt `fn() => redirect()`. Closures in Route-**Gruppen** sind erlaubt, nur Route-**Handler** dürfen keine sein.

### 5.4 Filament v4 — Besonderheiten

#### Resource-Struktur (v4 vs. v3)
In Filament v4 ist jede Resource in Unterordner aufgeteilt:
```
Resources/Bookings/
├── BookingResource.php          # Haupt-Resource (Navigation, Permissions)
├── Pages/ListBookings.php       # Listenansicht
├── Pages/ViewBooking.php        # Detailansicht
├── Schemas/BookingInfolist.php  # Infolist-Schema
└── Tables/BookingsTable.php     # Tabellen-Definitionen
```

#### Panel-Startseite
```php
// Kalender.php — überschreibt die Methode, nicht die Property!
public function getRoutePath(Panel $panel): string
{
    return '/';
}
```

#### Nur-Lesen für Viewer
```php
// Pattern in allen Resources:
public static function canCreate(): bool            { return self::isAdmin(); }
public static function canEdit(Model $r): bool      { return self::isAdmin(); }
public static function canDelete(Model $r): bool    { return self::isAdmin(); }
public static function canDeleteAny(): bool         { return self::isAdmin(); }
public static function canAccess(): bool            { return self::isAdmin(); } // komplette Resource sperren

private static function isAdmin(): bool
{
    return Auth::guard('admin')->user()?->isAdmin() ?? false;
}
```

#### Kein `->requiresConfirmation()` + `->schema()` kombinieren
```php
// FALSCH — Confirmation-Modal unterdrückt die Felder:
Action::make('foo')->requiresConfirmation()->schema([...])

// RICHTIG — nur schema() + modalSubmitActionLabel:
Action::make('foo')
    ->schema([TextInput::make('reason')])
    ->modalSubmitActionLabel('Bestätigen')
```

#### Kein `use` / `@use` in Filament-Blade-Views
```blade
{{-- FALSCH — kompiliert in render-Closure → ParseError: --}}
@use('App\Models\Setting')

{{-- RICHTIG — vollqualifizierter Name inline: --}}
{{ \App\Models\Setting::room() }}
```

#### Admins-Passwort nicht doppelt hashen
```php
// AdminUser hat 'hashed' cast → Laravel hasht automatisch
// FALSCH in Form-Actions:
'password' => Hash::make($data['password'])
// RICHTIG:
'password' => $data['password']  // Cast erledigt das Hashing
// Beim Bearbeiten (leeres Feld = kein Ändern):
TextInput::make('password')->dehydrated(fn ($state) => filled($state))
```

### 5.5 AppServiceProvider

```php
// boot():
Carbon::setLocale('de');  // → Carbon::now()->translatedFormat('l') = 'Montag'
Table::configureUsing(fn (Table $table) => $table->paginationPageOptions([10, 20, 50, 'all']));

// Automatisches Backup vor Migrationen (nur in production):
Event::listen(MigrationsStarted::class, fn() => app(DatabaseBackupService::class)->create());
```

### 5.6 BuildsSlotCalendar-Trait

Wiederverwendbarer Trait in `BookingController` und `RescheduleController`:

```php
// Gibt zurück:
[
    'calendar'    => Collection,  // groupiert: [KW => [Datum => [TimeSlot, ...]]]
    'weeks'       => Collection,  // alle vorhandenen Kalenderwochen
    'selectedKw'  => int|null,    // aktuell gewählte KW
    'availableIds'=> Collection,  // IDs freier Slots (für Alpine)
]
```

---

## 6. Frontend

### 6.1 Layout-Struktur (Mitarbeitende)

```
resources/views/layouts/app.blade.php
├── <head>: Vite CSS + JS, Meta-Tags
├── <nav>: Logo, Benutzername, Abmelden
├── <main>: @yield('content')
└── <footer>: © Jahr + Setting::footer()
```

Alle Mitarbeitenden-Views erweitern dieses Layout: `@extends('layouts.app')`.

### 6.2 Alpine.js

Alpine.js wird für zwei Funktionen verwendet:

**1. Live-Verfügbarkeit im Kalender** (`booking/_calendar.blade.php`):
```js
x-data="{
    available: @js($availableIds),  // Server-seitig vorgebundene Slot-IDs
    isFree(id) { return this.available.includes(id) },
    async refresh() {
        const res = await fetch('/api/verfuegbarkeit?kw=...')
        this.available = (await res.json()).available
    },
    async select(id, url) {
        // Doppelprüfung: ist Slot noch frei? Dann weiterleiten, sonst Meldung
    },
    init() { setInterval(() => this.refresh(), 15000) }  // alle 15s
}"
```

**2. Tag-Input im Software-Formular** (`config/edit.blade.php`):
```js
x-data="{
    tags: @js($selectedNames),    // vorausgewählte Programme
    catalog: @js($suggestions),  // alle genehmigten Programme
    query: '',
    filtered: /* gefilterte Liste */,
    add(name) { ... },
    remove(i)  { ... },
    move(d)    { /* Pfeil-Navigation */ }
}"
```

### 6.3 Tailwind CSS

Tailwind v4 mit dem `@tailwindcss/vite` Plugin — kein `tailwind.config.js` nötig (Konfiguration in `app.css`).

```css
/* resources/css/app.css */
@import "tailwindcss";
```

Im Filament-Admin-Panel wird Tailwinds `.dark`-Klasse an `<html>` gesetzt. Dark-Mode-Anpassungen in Filament-Blade-Views:
```html
<style>
  .dark .my-element { background: #1f2937; }
</style>
```
**Kein Inline `style=""`** für Dark-Mode — Filament-Views haben keinen Scope-Zugriff auf Tailwinds Dark-Mode-Klassen via Utility-Klassen.

### 6.4 Vite-Konfiguration

```js
// vite.config.js
laravel({
    input: ['resources/css/app.css', 'resources/js/app.js'],
    refresh: true,
    fonts: [bunny('Instrument Sans', { weights: [400, 500, 600] })],
})
```

Assets werden unter `public/build/` gebaut. Im Produktions-Bundle bereits enthalten — **kein npm auf dem Server**.

---

## 7. Installation & Lokales Setup

### Voraussetzungen

| Werkzeug | Version | Zweck |
|---|---|---|
| PHP | 8.3+ | Entwicklungsumgebung |
| Composer | 2.x | PHP-Abhängigkeiten |
| Node.js + npm | LTS | Frontend-Assets |
| MariaDB / MySQL | 10.x+ | Datenbank |
| WSL2 (Windows) | — | Empfohlen für Windows-Entwickler |

### Schritt-für-Schritt

**1. Repository klonen**
```bash
git clone <repo-url> laptop-austausch
cd laptop-austausch
```

**2. PHP-Abhängigkeiten installieren**
```bash
composer install
```

**3. Umgebungsdatei anlegen**
```bash
cp .env.example .env
php artisan key:generate
```

**4. `.env` anpassen**
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laptop_austausch
DB_USERNAME=laptop_app
DB_PASSWORD=laptop_dev_pw

APP_LOCALE=de
APP_TIMEZONE=Europe/Berlin
SESSION_DRIVER=database   # oder 'file' — kein E-Mail-Treiber nötig
```

**5. Datenbank anlegen** (MariaDB)
```sql
-- Als root:
CREATE DATABASE laptop_austausch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'laptop_app'@'localhost' IDENTIFIED BY 'laptop_dev_pw';
GRANT ALL PRIVILEGES ON laptop_austausch.* TO 'laptop_app'@'localhost';

-- Test-Datenbank (für phpunit):
CREATE DATABASE laptop_austausch_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON laptop_austausch_test.* TO 'laptop_app'@'localhost';

FLUSH PRIVILEGES;
```

**6. Migrationen & Seeder**
```bash
php artisan migrate --seed
```

**7. Frontend-Assets bauen**
```bash
npm install
npm run build
# Oder im Dev-Modus mit Hot-Reload:
npm run dev
```

**8. Entwicklungsserver starten**
```bash
# Alle Dienste gleichzeitig (empfohlen via Composer-Script):
composer run dev

# Oder einzeln:
php artisan serve        # http://localhost:8000
```

**9. Zugangsdaten (nach Seeding)**

| Typ | URL | Benutzername / E-Mail | Passwort |
|---|---|---|---|
| Admin | `/admin` | `admin@kreisgg.de` | `password` |
| Viewer | `/admin` | `viewer@kreisgg.de` | `password` |
| Mitarbeitende | `/login` | `kvgg1234` (o.ä.) | die PC-Nummer des Seeders |

### Windows + WSL2

Das Projekt ist für die Entwicklung unter Windows + WSL2 ausgelegt. Alle Befehle im WSL-Terminal ausführen. MariaDB in WSL installieren (nicht Windows-seitig). Siehe `SETUP.md` im Projektroot für die vollständige WSL2-Einrichtung.

---

## 8. Wichtige Dependencies

### PHP (composer.json)

| Package | Version | Verwendung |
|---|---|---|
| `laravel/framework` | ^13.8 | Core-Framework |
| `filament/filament` | ^4.0 | Admin-Panel (Resources, Pages, Actions, Tables, Forms) |
| `barryvdh/laravel-dompdf` | ^3.1 | PDF-Generierung für Imaging-Blätter (`ImagingSheetController`) |
| `maatwebsite/excel` | ^3.1 | Excel-Export der Buchungsliste (`BookingsExport`) |
| `laravel/tinker` | ^3.0 | REPL für Debugging |

**Dev-only:**
| Package | Verwendung |
|---|---|
| `fakerphp/faker` | Testdaten-Generierung (de_DE) |
| `laravel/boost` | Entwicklungs-Hilfswerkzeuge |
| `laravel/pail` | Log-Viewer im Terminal |
| `laravel/pint` | Code-Style (PSR-12) |
| `phpunit/phpunit` | ^12 — Test-Framework |
| `nunomaduro/collision` | Bessere Fehlerdarstellung im Terminal |

### JS (package.json)

| Package | Typ | Verwendung |
|---|---|---|
| `alpinejs` | dependency | Reaktive UI-Komponenten (Kalender, Tag-Input) |
| `vite` | devDependency | Build-Tool |
| `laravel-vite-plugin` | devDependency | Vite-Integration für Laravel (Asset-Manifest, HMR) |
| `tailwindcss` | devDependency | CSS-Framework |
| `@tailwindcss/vite` | devDependency | Tailwind v4 Vite-Plugin |
| `concurrently` | devDependency | Mehrere Prozesse parallel starten (`composer run dev`) |

---

## 9. Migrationen & Seeding

### Migrations-Reihenfolge

Die Migrations-Dateien sind chronologisch nummeriert. Kritisch: Fremdschlüssel-Abhängigkeiten.

```
0001_01_01_000000_create_users_table.php           (Laravel-Standard — bleibt ungenutzt)
0001_01_01_000001_create_cache_table.php
0001_01_01_000002_create_jobs_table.php
2026_06_02_100001_create_admin_users_table.php
2026_06_02_100002_create_employees_table.php
2026_06_02_100003_create_time_slots_table.php
2026_06_02_100004_create_bookings_table.php        (FK: employees, time_slots)
2026_06_02_100005_create_software_catalog_table.php
2026_06_02_100006_create_booking_software_table.php (FK: bookings, software_catalog)
2026_06_02_100007_create_laptop_configs_table.php   (FK: bookings)
2026_06_10_..._add_additional_notes_to_laptop_configs_table.php
2026_06_11_..._add_status_and_submitted_by_to_software_catalog_table.php
2026_06_15_..._add_reviewed_at_to_bookings_table.php
2026_06_17_..._create_settings_table.php
2026_06_18_..._create_download_files_table.php
```

### Seeder

```bash
php artisan migrate --seed          # migrate + alle Seeder
php artisan db:seed                 # nur Seeder (keine Migrationen)
php artisan db:seed --class=SoftwareCatalogSeeder  # einzelner Seeder
php artisan migrate:fresh --seed    # Drop-All + neu
```

**Seeder-Reihenfolge** (in `DatabaseSeeder.php`):
1. `AdminUserSeeder` — 2 Admins (admin@kreisgg.de, viewer@kreisgg.de)
2. `SoftwareCatalogSeeder` — Standard-Softwareliste
3. `EmployeeSeeder` — 150 Mitarbeitende (Faker de_DE)
4. `TimeSlotSeeder` — Slots via `SlotGenerator`
5. `BookingSeeder` — 50 Buchungen

### Neue Migration anlegen

```bash
php artisan make:migration add_foo_to_bar_table
# Konvention: Tabellen-Änderungen benennen wie 'add_<spalte>_to_<tabelle>_table'
```

**Wichtig beim Hinzufügen von Spalten zu `bookings` oder `laptop_configs`:**
- Der `BookingManager::move()`-Service überträgt `LaptopConfig` und `BookingSoftware` auf eine neue Buchung — neue Felder ggf. dort ergänzen.
- Der `ImagingSheetExporter::sheetData()` liest `LaptopConfig`-Felder — neue Felder ggf. im PDF-Template ergänzen.

---

## 10. Deployment

### Strategie: Bundle-Only (kein Internet auf dem Server)

Der Produktionsserver hat keinen Composer/npm-Zugang. Alle Abhängigkeiten werden auf dem Entwickler-Laptop gebündelt.

```
Entwickler-Laptop                         Produktionsserver
─────────────────────────────────────     ─────────────────────
1. Änderungen committen (git)             
2. bash deploy/bundle.sh            →     tar.gz übertragen (USB/Cloud)
                                          tar -xzf laptop-austausch-paket.tar.gz
                                          sudo bash deploy/update.sh
```

### `deploy/bundle.sh` — was es macht

1. `git archive HEAD` — versionierten Stand exportieren (ohne `.env`, ohne `.git`)
2. `composer install --no-dev --optimize-autoloader` — PHP-Abhängigkeiten ohne Dev-Pakete
3. `npm ci && npm run build` — Frontend-Assets bauen
4. `rm -rf node_modules` — Dev-Abhängigkeiten entfernen
5. `tar -czf laptop-austausch-paket.tar.gz` — alles in ein ~18 MB-Archiv

### `deploy/setup.sh` — Erstinstallation

Führt aus (als `root` auf dem Server):
1. Debian-Pakete: `apache2`, `mariadb-server`, `mariadb-client`, PHP-Extensions
2. PHP-Extensions: `mysql`, `mbstring`, `xml`, `curl`, `zip`, **`gd`** (Excel), **`intl`** (Filament), `bcmath`
3. Apache `mod_rewrite` aktivieren, VirtualHost einrichten
4. MariaDB: Datenbank + User anlegen
5. `.env` erstellen (DB-Passwort aus vorhandener `.env` wiederverwenden falls vorhanden)
6. `php artisan migrate`, Software-Katalog seeden, Admin-User anlegen
7. Berechtigungen: `storage/` und `bootstrap/cache/` → `www-data:www-data`

### `deploy/update.sh` — Updates

```bash
# Auf dem Server (im entpackten Paket-Verzeichnis):
sudo bash deploy/update.sh
```

Ablauf:
1. `php artisan down` (Wartungsmodus)
2. Prüft ob `vendor/` und `public/build/` im Paket vorhanden sind
3. `php artisan migrate --force` (triggert Auto-Backup via `AppServiceProvider`)
4. `php artisan config:cache && route:cache && view:cache`
5. Berechtigungen setzen
6. `php artisan up` (auch bei Fehler via `trap`)

### Produktions-.env (Pflichtfelder)

```dotenv
APP_ENV=production          # Aktiviert Auto-Backup vor Migrationen
APP_DEBUG=false
APP_KEY=base64:...          # php artisan key:generate
SESSION_DRIVER=file         # PFLICHT! Kein sessions-Table vorhanden
APP_LOCALE=de               # PFLICHT! Default wäre 'en'
DB_*                        # Datenbankverbindung
```

### Server-Anforderungen (Produktion)

| Komponente | Anforderung |
|---|---|
| OS | Debian 13 Trixie (empfohlen) |
| PHP | 8.4 (aus offiziellen Debian-Repos) |
| PHP-Extensions | `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, **`gd`**, **`intl`**, `bcmath` |
| Webserver | Apache 2.x mit `mod_rewrite` |
| Datenbank | MariaDB (mit `mariadb-client` Paket für `mysqldump`) |
| Schreibrechte | `storage/`, `bootstrap/cache/` → `www-data` |

---

## 11. Umgebungsvariablen (.env)

| Variable | Beispielwert | Beschreibung |
|---|---|---|
| `APP_NAME` | `Laptop-Austausch` | App-Name |
| `APP_ENV` | `local` / `production` | Steuert Auto-Backup (nur `production`) |
| `APP_DEBUG` | `true` / `false` | Fehlerausgabe |
| `APP_KEY` | `base64:...` | Verschlüsselungsschlüssel (`php artisan key:generate`) |
| `APP_URL` | `http://localhost` | Basis-URL |
| `APP_LOCALE` | `de` | **Pflicht!** Spracheinstellung |
| `APP_TIMEZONE` | `Europe/Berlin` | Zeitzone |
| `DB_CONNECTION` | `mysql` | Datenbanktyp (MariaDB = mysql) |
| `DB_HOST` | `127.0.0.1` | DB-Host |
| `DB_PORT` | `3306` | DB-Port |
| `DB_DATABASE` | `laptop_austausch` | Datenbankname |
| `DB_USERNAME` | `laptop_app` | DB-Benutzer |
| `DB_PASSWORD` | `...` | DB-Passwort |
| `SESSION_DRIVER` | `file` | **Pflicht in Prod!** `database` würde brechen (kein sessions-Table) |
| `SESSION_LIFETIME` | `120` | Session-Dauer in Minuten |
| `CACHE_STORE` | `database` | Cache-Treiber |
| `QUEUE_CONNECTION` | `sync` | Queue (keine async Queue nötig) |
| `MAIL_MAILER` | `log` / `array` | **Keine echte Mail** — nur Logging oder Test-Array |
| `LOG_CHANNEL` | `daily` | Log-Rotation |
| `LOG_LEVEL` | `debug` / `error` | Log-Verbosität |

### Nicht vorhandene / nicht benötigte .env-Variablen
- Kein `MAIL_HOST`, `MAIL_PORT` etc. — kein E-Mail-Versand
- Kein `REDIS_*` — kein Redis
- Kein `AWS_*` — kein S3-Storage

---

## 12. Composer- & npm-Scripts

### Composer-Scripts (`composer.json`)

```bash
composer run dev      # Startet alle Dev-Dienste parallel:
                      #   php artisan serve
                      #   php artisan queue:listen
                      #   php artisan pail (Logs)
                      #   npm run dev (Vite HMR)

composer run test     # php artisan config:clear && php artisan test

composer run setup    # Einmalig: composer install + key:generate + migrate + npm install + build
```

### npm-Scripts (`package.json`)

```bash
npm run dev      # Vite Dev-Server (HMR)
npm run build    # Produktions-Build nach public/build/
```

### Artisan-Befehle (wichtige)

```bash
php artisan migrate                    # Migrationen ausführen
php artisan migrate:fresh --seed       # Alles löschen + neu + Seeder
php artisan db:seed                    # Nur Seeder
php artisan tinker                     # REPL
php artisan route:list                 # Alle Routen anzeigen
php artisan config:cache               # Config-Cache erstellen (prod)
php artisan route:cache                # Route-Cache erstellen (prod, nur ohne Closures!)
php artisan view:cache                 # View-Cache erstellen (prod)
php artisan config:clear               # Config-Cache löschen
php artisan filament:upgrade           # Filament-Assets aktualisieren
php artisan make:filament-resource Foo # Neue Filament-Resource erstellen
php artisan make:migration add_x_to_y  # Neue Migration
php artisan pint                       # Code-Style automatisch korrigieren
```

---

## 13. Testing

### Konfiguration

Tests laufen gegen eine **separate Datenbank** `laptop_austausch_test` (kein SQLite — PHP hat kein `pdo_sqlite`).

```xml
<!-- phpunit.xml -->
<env name="DB_DATABASE" value="laptop_austausch_test"/>
<env name="DB_USERNAME" value="laptop_app"/>
<env name="DB_PASSWORD" value="laptop_dev_pw"/>
```

Die Test-DB muss manuell angelegt werden (einmalig, als root):
```sql
CREATE DATABASE laptop_austausch_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON laptop_austausch_test.* TO 'laptop_app'@'localhost';
```

### Tests ausführen

```bash
php artisan test                        # alle Tests (188 Feature-Tests)
php artisan test --filter BookingTest   # einzelnen Test-Case
php artisan test --parallel             # parallel (falls konfiguriert)
```

### Wichtige Gotchas

**1. Kein mid-Test User-Switch zwischen Guards**

```php
// FALSCH — AuthenticateSession invalidiert die Session beim Guard-Wechsel:
$this->actingAs($admin, 'admin')->get('/admin/...')
$this->actingAs($viewer, 'admin')->get('/admin/...')  // → 302

// RICHTIG — je eine separate Testmethode:
public function test_admin_can_do_x() { $this->actingAs($admin, 'admin')... }
public function test_viewer_cannot_do_x() { $this->actingAs($viewer, 'admin')... }
```

**2. `DatabaseRestoreTest` — kein `RefreshDatabase`**

Der Restore-Test führt externe `mysql`-Prozesse aus, die nicht in einer Transaktion laufen können:
```php
// DatabaseRestoreTest.php verwendet:
use Illuminate\Foundation\Testing\DatabaseMigrations;  // Drop + Migrate
// NICHT: RefreshDatabase                              // Würde Transaktions-Rollback machen
```

**3. Keine Case-Sensitivity-Assertions bei MariaDB**

MariaDB's Default-Collation ist case-insensitiv. Tests, die prüfen, ob `'Excel'` ≠ `'excel'` sind, würden fälschlich bestehen. Das ist bekannt und dokumentiert.

### Test-Struktur

```
tests/
├── Feature/
│   ├── Auth/               # EmployeeAuthTest (Login, Rate-Limiting, Logout)
│   ├── Booking/            # Buchen, Verschieben, iCal, Software-Formular,
│   │                       #   Ort-Einstellung, Katalog-Resolver, Dashboard-Dokumente
│   ├── Admin/              # Filament-Pages, Buchungsverwaltung, Import, Export,
│   │                       #   Backup/Restore, Bulk-Löschen, Rollen-Zugriff
│   └── ErrorPagesTest.php  # Benutzerdefinierte Fehlerseiten
└── Unit/
    └── ExampleTest.php     # Platzhalter
```

> Die Service-Tests (`BookingManagerTest`, `SlotGeneratorTest`, `EmployeeImporterTest`,
> `SoftwareCatalogMergerTest`, …) liegen unter `tests/Feature/Admin/`, nicht unter `tests/Unit/` —
> sie brauchen die Datenbank und sind daher Feature-Tests.

---

## 14. Tipps für Weiterentwicklung

### Neue Filament-Resource hinzufügen

```bash
php artisan make:filament-resource Foo --view
```

Dann in `FooResource.php`:
- `canAccess()`, `canCreate()`, `canEdit()`, `canDelete()` auf das isAdmin-Pattern setzen
- Navigation-Icon und -Gruppe setzen
- Ggf. Badge für offene Items: `getNavigationBadge()`

### Neues Admin-Panel-Feature (Custom Page)

```bash
php artisan make:filament-page MyPage
```

1. Blade-View erstellen: `resources/views/filament/pages/my-page.blade.php`
2. Kein `@use()` in der View — vollqualifizierte Klassennamen inline verwenden
3. Für "nur Admin"-Pages: `canAccess()` überschreiben

### Software-Katalog um neue Felder erweitern

1. Migration anlegen (`add_x_to_software_catalog_table`)
2. `$fillable` in `SoftwareCatalog.php` ergänzen
3. `SoftwareCatalogForm.php` und `SoftwareCatalogInfolist.php` erweitern
4. `SoftwareCatalogMerger` prüfen — müssen neue Felder beim Merge berücksichtigt werden?
5. `ImagingSheetExporter::sheetData()` prüfen — soll das Feld im PDF erscheinen?

### SCCM-Hardware-Import (noch nicht implementiert)

Die `laptop_configs`-Tabelle hat `old_*` Felder (Hersteller, Seriennummer, CPU, RAM, Storage), die aus einem SCCM-Export befüllt werden sollen. Muster: `EmployeeImporter` als Vorlage nutzen. Verknüpfung über `pc_nummer` (Employee → aktueller Slot → `laptop_configs`).

### Feiertage in SlotGenerator eintragen

```php
// app/Services/SlotGenerator.php
public const HOLIDAYS = [
    '2026-10-03',  // Tag der Deutschen Einheit
    '2026-11-01',  // Allerheiligen (Hessen)
    // ...
];
```

### Verfügbarkeits-API erweitern

```
GET /api/verfuegbarkeit?kw=35
→ {"available": [12, 17, 23, ...]}  // IDs freier Slots

GET /api/slot/{id}
→ {"available": true}
```

Beide Endpunkte sind in `BookingController` und werden von Alpine.js alle 15 Sekunden gepollt.

### Bekannte technische Schulden

| Bereich | Problem | Lösung |
|---|---|---|
| Buchungslogik | `BookingController` und `BookingManager` haben duplizierte Slot-Freigabe-Logik | `BookingController` auf `BookingManager` umstellen |
| `is_custom` Feld | Legacy-Feld in `booking_software` — neue Einträge nutzen es nicht mehr | Migration für Datenbergeinigung + Spalte entfernen |
| Feiertage | `SlotGenerator::HOLIDAYS` ist ein leeres Array — Hessen hat Feiertage | Mit Feiertagsliste befüllen |
| SCCM-Import | `laptop_configs.old_*` Felder sind immer leer | SCCM-Importer implementieren |

### Deployment-Checkliste für neue Features

- [ ] Alle Tests grün (`php artisan test`)
- [ ] Neue Migrationen vorhanden (falls DB-Schema ändert)
- [ ] `php artisan route:cache`-Kompatibilität geprüft (keine Closure-Handler)
- [ ] `deploy/bundle.sh` ausgeführt (baut vendor/ + public/build/)
- [ ] Paket auf Server übertragen
- [ ] `sudo bash deploy/update.sh` ausgeführt (migriert + leert Caches)
- [ ] Browser-Test auf dem Produktionssystem
- [ ] Git-Commit mit deutschem Commit-Message (Projektstil)

---

*Erstellt für das Laptop-Austausch-Projekt · Kreis Groß-Gerau · IT-Center*
