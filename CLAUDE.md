# Laptop-Austausch — Buchungstool Kreis Groß-Gerau

Laravel self-service web app for **Kreis Groß-Gerau** (German local government district)
to manage the exchange of ~150 leased laptops. Employees log in and book a timeslot to
hand in their old laptop and pick up a new one; the IT department manages the process
through a Filament admin panel.

**The application is in German** (locale `de`, faker `de_DE`). There is **no email
functionality** — the production server has no mail access. Confirmations are on-page +
iCal (.ics) download only.

The maintainer is a beginner. Work in small, verified steps; commit after each working
change; surface design decisions and spec mismatches rather than guessing.

---

## Current state (as of 2026-06-15)

**Employee side: complete.** **Admin panel: complete.** Polish (Phase 8) done.
Deployment (Phase 9) **done — app is running on the real server.** **162 feature tests pass.**

> **Pending server update (planned 2026-06-16):** the live server still runs the pre-2026-06-15
> version. The changes below (Warteschlange, Erinnerungen, login hardening + logo, bulk-delete,
> reordered booking flow, move-via-calendar, …) ship with the next bundle. The update **includes a
> DB migration** (`add_reviewed_at_to_bookings_table`) — `update.sh` auto-backs up before migrating.
> See `DEPLOY.md` → "Updates später einspielen".

| Phase | Status | What shipped |
|-------|--------|--------------|
| 1 — DB & models | ✅ | 7 tables, models, factories, seeders (2 admins, 150 employees, 200 slots, 50 bookings) |
| 2 — Employee auth | ✅ | `employee` guard, KVGG-Nr. + PC-Nr. login, dashboard, logout |
| 3 — Slot booking | ✅ | KW-grouped calendar, green/red, Alpine live availability, double-booking guard |
| 4 — Reschedule | ✅ | Atomic cancel-old + book-new; config carries over |
| 5 — Laptop config form | ✅ | Manufacturer + software-for-reimaging (see decision below) |
| 6 — iCal download | ✅ | `.ics` download, owner-only |
| 7 — Admin panel (Filament v4) | ✅ | All resources, slot generator, CSV import, admin accounts, no-show/sick, manual booking mgmt, PDF + Excel export, software catalog |
| 8 — Polish & testing | ✅ | German error pages (404/403/419/500/503/429), fresh-seed flow verified |
| 9 — Deployment | ✅ | Running on real server (Debian 13 Trixie, PHP 8.4). Bundle-only workflow via USB/cloud. |

**Beyond the original spec, also built:**
- **Admin-Kalender as the panel home** — replaces the default Filament dashboard *and* the
  daily-load chart *and* the TimeSlots resource. A week grid (KW switcher) shows each slot
  as frei/belegt; clicking a free slot opens a create-booking dialog, clicking a booked slot
  opens the booking. "Slots generieren" lives here as a header action, plus an admin-only
  **"Alle Buchungen & Mitarbeitenden löschen"** header action (resets all bookings + employees
  for a new exchange round; frees all slots; keeps the software catalog and the slots themselves —
  via `BookingManager::purgeAllBookingsAndEmployees`).
- **Software-Katalog with approval workflow**: admin-managed catalog + employee-form
  autocomplete. Unknown entries an employee types are saved as **`pending`** (visible only to
  that employee + admins); admins **approve** (→ visible to all), **delete** (reject), or
  **merge** duplicates ("excel"+"Excel" → one, repointing all bookings). Catalog rows are
  clickable → a "Verwendet von" list of who requested that software.
- **Datensicherung (database backups)**: admin-only page to create SQL dumps, list/download/
  delete them, and restore from an uploaded `.sql`; auto-backup before migrations in production.
- **Zusätzliche Angaben**: optional free-text field on the laptop form, surfaced on the
  booking confirmation, the admin infolist, and the printed imaging sheet.
- **Warteschlange (review queue)**: admin page where confirmed bookings are worked through one by
  one and marked as confirmed/ready — two tabs (Offen / Bereit), backed by `bookings.reviewed_at`.
- **Erinnerungen (reminders)**: admin page listing employees **without a termin**, each with a
  `mailto:` reminder button (opens the admin's own mail client — the server sends no mail).
- **Bulk-delete** on Buchungen and Software-Katalog; admin can also **remove individual software**
  from a booking via a pen icon on the booking detail.
- **Login hardening**: case-insensitive KVGG-/PC-Nummer, 5-tries→10-min lockout per KVGG-Nummer
  (`RateLimiter`), plus the Kreis-Groß-Gerau logo + purpose text on the login page.
- **Booking flow**: after "Termin verbindlich buchen" the employee goes straight to the software
  form, then the confirmation page. **Termin verschieben** redirects to the Kalender in move mode.

**Login credentials (seeded dummy data):**
`admin@kreisgg.de` / `password` (role admin) · `viewer@kreisgg.de` / `password` (role viewer).

---

## Tech stack (as built — deviates from the original spec)

The original spec said Laravel 11 + Filament v3. That was wrong and was overridden:

- **Laravel 13** + **Filament v4** — Filament v3 cannot run on Laravel 13. (Laravel Boost is
  installed; see `.mcp.json`.)
- **PHP 8.3** (dev) / **PHP 8.4** (production), Composer, Node.js. Frontend: Blade + Tailwind CSS + Alpine.js (Vite build).
- **MariaDB** locally (matches MySQL in production). App DB `laptop_austausch`, app user
  `laptop_app`. `root` uses socket auth (needs sudo).
- **Excel**: `maatwebsite/excel`. **PDF**: `barryvdh/laravel-dompdf`.
- Production server: **Debian 13 (Trixie), Apache, MariaDB, PHP 8.4** (from official Debian repos).
  Needs PHP extensions **`php-intl`** AND **`php-gd`** (Filament needs intl; Excel/image writing
  needs gd), plus the **`mariadb-client`** package (`mysqldump`/`mysql` in PATH — for the
  backup/restore system). **Production `.env` must set `SESSION_DRIVER=file`** (there is no
  sessions table — the default `database` driver would break login) **and `APP_LOCALE=de`**
  (default is `en`). Assets are pre-built in the bundle — `npm run build` is NOT run on the server.

### Two auth guards (`config/auth.php`)
- `employee` — public side. Login = `kvgg_nummer` (username, case-insensitive) +
  `pc_nummer` (password, case-insensitive — both compared lowercased in
  `EmployeeAuthController`). Login is throttled: 5 wrong tries per KVGG-Nummer →
  10-minute lockout (Laravel `RateLimiter`).
- `admin` — Filament panel at `/admin`. `AdminUser` implements `FilamentUser`; both `admin`
  and `viewer` roles may enter, but viewers are read-only everywhere.

### Key design decisions (overrides of the spec — keep these in mind)
- **`pc_nummer` is stored PLAINTEXT, not hashed.** It's a device-sticker number, not a real
  secret, and the laptop form must auto-fill it — hashing would block that. It doubles as the
  employee login password.
- **Employees do NOT enter hardware specs.** A browser can't read serial/CPU/RAM/storage and
  employees don't know them. The employee form is just: manufacturer dropdown + the software
  currently on their laptop (so IT can reimage the new device correctly). Old-device hardware
  specs are meant to come from an **SCCM hardware-inventory CSV** (importer not yet built).
- `laptop_configs` / `booking_software` are tied to `booking_id`, so a reschedule (which
  creates a new booking row) must MOVE the config + software to the new booking. This is
  handled by `BookingManager` and the reschedule flow.

---

## Architecture

### Services (`app/Services/`) — business logic lives here, not in controllers
- **`BookingManager`** — admin-side create/cancel/move. Each runs in `DB::transaction` +
  `lockForUpdate`; throws `RuntimeException` (German message) on conflict, caught by the
  Filament action → danger Notification. Enforces slot-available + one-active-booking-per-employee.
  Also `releaseSlotsForEmployees($ids|$employees)`: frees the time slots held by those
  employees' non-cancelled bookings — called by the Employees bulk-delete **before** deleting,
  because the FK cascade removes the bookings but does NOT reset the slot `booked_count`/`status`.
  `releaseSlotsForBookings($ids|$bookings)`: same idea for the Bookings bulk-delete.
  `purgeAllBookingsAndEmployees()`: wipes ALL bookings + employees, frees all (non-blocked)
  slots, keeps the software catalog + slots — backs the Kalender "Alle … löschen" button.
- **`SlotGenerator`** — generates weekday slots (8/day: 08:00–15:00), idempotent
  (`firstOrCreate`), with a `HOLIDAYS` hook. Used by the seeder and the "Slots generieren" action.
- **`EmployeeImporter`** — CSV import: auto-detects delimiter (`;`/`,`/tab), Windows-1252→UTF-8
  for umlauts, order-independent header mapping, upsert on `kvgg_nummer`, per-row error collection.
- **`ImagingSheetExporter`** — builds the printable "Imaging-Blatt" PDF (the checklist the
  technician lays next to the new laptop). `sheetData()` is separately testable.
- **`SoftwareCatalogResolver`** — `resolve($name, $employeeId)`: reuses an **approved** entry
  (case-insensitive) → else the submitter's **own pending** entry → else creates a new
  `pending` entry attributed to that employee. `normalizeNames()` trims/dedupes.
- **`SoftwareCatalogMerger`** — `merge($loser, $winner)`: repoints all `booking_software` from
  loser → winner (dedupes when a booking has both), then deletes the loser. Used by the merge action.
- **`DatabaseBackupService`** — SQL backups via `mysqldump` to
  `storage/app/backups/backup_YYYY-MM-DD_HH-MM-SS.sql` (gitignored; contain personal data).
  Password passed via `MYSQL_PWD` env (not the command line); filenames pattern-validated
  against path traversal. `create/all/path/exists/delete/restore`. Chosen over
  `spatie/laravel-backup` (which makes zips and has no restore).

### Filament resources & pages (`app/Filament/`) — v4 splits each resource into `*Resource.php` + `Tables/` + `Schemas/` + `Pages/`
- **`Pages/Kalender.php`** — the panel home (`getRoutePath()` returns `/`). Week grid; create-booking
  dialog on free slots; "Slots generieren" + admin-only "Alle Buchungen & Mitarbeitenden löschen"
  header actions. **Verschieben-Modus**: a `#[Url] $verschieben` property (booking id, passed from the
  booking detail's "Termin verschieben" button) puts the calendar in move mode — a banner names the
  person, free slots turn amber ("hierher →"), and clicking one runs `verschiebenAction`
  (`BookingManager::move`, with a confirmation) then exits the mode. View:
  `resources/views/filament/pages/kalender.blade.php`.
- **`Pages/Warteschlange.php`** ("Warteschlange") — custom table page (own nav item, badge = open
  count). The admin works through confirmed bookings one by one. Two tabs via a `$activeTab`
  Livewire property + `<x-filament::tabs>`: **Offen** (`reviewed_at` NULL) and **Bereit**
  (`reviewed_at` set). Row actions (admin-only): **Bestätigen** (sets `reviewed_at = now()`) and
  **Zurück in Warteschlange** (clears it); plus **Ansehen** → BookingResource view. View:
  `resources/views/filament/pages/warteschlange.blade.php`.
- **`Pages/SystemBackups.php`** ("Datensicherung") — **admin-only** (`canAccess()` → isAdmin).
  Header actions: create backup + restore (FileUpload `.sql`, extension-checked, destructive
  modal). Blade table of backups (name/date/size) with download + delete (`wire:confirm`).
- **Bookings** — read-only list + detail infolist. Detail page has admin actions: mark
  no-show / sick (with reason), reset to confirmed, **move (→ redirects to the Kalender in
  Verschieben-Modus, see above)**, cancel, print imaging PDF.
  Admin-only **bulk-delete** toolbar action (select rows → "Löschen"): frees the selected
  bookings' slots via `BookingManager::releaseSlotsForBookings` first, then deletes them.
- **Employees** — read-only list + detail; "Mitarbeitende importieren" header action (CSV upload).
  Admin-only **bulk-delete** toolbar action (select rows → "Löschen"): frees their booked slots
  via `BookingManager::releaseSlotsForEmployees` first, then deletes (cascade removes bookings,
  configs, software links). Hidden for viewers.
- **`Pages/Erinnerungen.php`** ("Erinnerungen") — a custom table page (own nav item) listing only
  employees **without a termin** (`Employee::scopeOhneTermin` → no confirmed/completed booking,
  see `SETTLED_BOOKING_STATUSES`). Each row has an **"Erinnern"** action: a `mailto:` link with a
  prefilled German reminder that opens the admin's own mail client (the server sends no mail).
  `Erinnerungen::reminderMailto()` builds the link. Shown only for people who have an email.
- **AdminUsers** — full CRUD, **admin-only** (`canAccess()` → isAdmin; 403s viewers).
- **SoftwareCatalogs** — CRUD (viewer read-only). List page has tabs (Alle / Wartet auf
  Freigabe / Freigegeben) + a navigation badge for the pending count; table has a status
  column/filter and admin-only **Freigeben** (approve) and **Zusammenführen** (merge) actions.
  Per-row actions are grouped in an `ActionGroup` (⋮ dropdown) so the table fits; an admin-only
  **bulk-delete** toolbar action deletes selected entries (`nullOnDelete` clears the link on any
  bookings that referenced them). View page has a "Verwendet von" relation manager
  (`UsageRelationManager`) listing who requested each software.

### Read-only-for-viewers pattern
`canCreate/canEdit/canDelete/canDeleteAny` → `isAdmin()`; full-resource block via
`canAccess()`. Role check: `Auth::guard('admin')->user()?->isAdmin() ?? false`.

### Exports
- **PDF**: `admin/exports/imaging/{booking}` and `/day?date=` — **plain controller routes**
  (`ImagingSheetController`), NOT Filament-owned, guarded via `Auth::guard('admin')->check()`.
  (Can't use `auth:admin` middleware — it would redirect to the *employee* login route named `login`.)
- **Excel**: `BookingsExport` (FromQuery + WithHeadings + WithMapping). The ListBookings
  export action passes `$this->getFilteredSortedTableQuery()`, so the export matches exactly
  the filtered/sorted on-screen list.
- **Backup download**: `admin/backups/{filename}/download` — plain controller route
  (`BackupController`), admin-checked (mirrors the imaging-PDF pattern).

---

## Database schema

Migrations in `database/migrations/` (created in this order):
`admin_users`, `employees`, `time_slots`, `bookings`, `software_catalog`, `booking_software`,
`laptop_configs`, then `add_additional_notes_to_laptop_configs_table`,
`add_status_and_submitted_by_to_software_catalog_table` and `add_reviewed_at_to_bookings_table`.

- **admin_users**: id, name, email (unique), password (hashed), role (enum: admin, viewer), timestamps
- **employees**: id, kvgg_nummer (unique, login username), vorname, nachname, email, abteilung,
  pc_nummer (login password, **plaintext**), last_laptop_exchange (nullable date), timestamps
- **time_slots**: id, slot_date, start_time, end_time, calendar_week, status (enum: available,
  booked, blocked), capacity (default 1), booked_count (default 0), created_by (FK admin_users), timestamps
- **bookings**: id, employee_id (FK), time_slot_id (FK, unique), status (enum: confirmed,
  cancelled, completed, no_show, sick), cancellation_reason, unplanned_note, booked_at,
  **reviewed_at** (nullable — Warteschlange: NULL = noch offen, gesetzt = von Admin bestätigt), timestamps
- **software_catalog**: id, name, version, publisher, is_standard (bool, default true),
  status (enum: pending, approved — default `approved`, so existing rows stay visible),
  submitted_by (nullable FK employees, nullOnDelete — who suggested a pending entry), timestamps
- **booking_software**: id, booking_id (FK), software_catalog_id (nullable FK),
  custom_software_name (nullable), is_custom (bool, default false), timestamps
- **laptop_configs**: id, booking_id (FK, unique), old_* hardware fields, new_* fields,
  **additional_notes** (nullable text — added later), timestamps

Note: new employee-form submissions link software via the resolver and no longer write
`is_custom` rows; the column remains for legacy data and the imaging sheet's "(Spezial)" marker.

---

## Backups

- `storage/app/backups/*.sql` — already gitignored (`storage/app/.gitignore` ignores `*`).
  Backups contain employee data and must **never** reach Git.
- Created from the **Datensicherung** admin page (admin-only) or automatically: an
  `AppServiceProvider` listener on `MigrationsStarted` runs a backup **only when
  `APP_ENV=production`**, and logs a warning (never blocks) if it fails.
- `deploy/update.sh` runs `php artisan migrate`, so production updates auto-backup first.

---

## Testing

- PHP here has **no `pdo_sqlite`** (only `pdo_mysql`), so tests run against **MariaDB**, not
  SQLite. `phpunit.xml` points at a separate DB **`laptop_austausch_test`** (user `laptop_app`).
  This must NEVER point at the dev DB `laptop_austausch`. Creating it / granting needs sudo.
- Run: `php artisan test` (135 tests). Rebuild assets after view/CSS/JS edits: `npm run build`.
- **Gotcha:** a single test that does `actingAs(admin)->get` THEN `actingAs(viewer)->get`
  fails — Filament's `AuthenticateSession` invalidates the session on a mid-test user switch
  (302). Split into one test per user.
- **Gotcha:** the restore test can't run inside a transaction (restore does DROP/CREATE via a
  separate `mysql` process), so `DatabaseRestoreTest` uses the `DatabaseMigrations` trait
  instead of `RefreshDatabase`.

---

## Conventions & gotchas

- **No `use` / `@use` in a Filament page Blade view** — it compiles inside a render closure →
  `ParseError`. Reference classes fully-qualified inline.
- **Filament v4 modal form fields** use `->schema([...])` (not `->form()`).
- Don't combine `->requiresConfirmation()` with a `->schema([...])` form on an action — the
  confirmation modal suppresses the fields. Use `modalSubmitActionLabel` instead.
- AdminUser password: model has the `'hashed'` cast — do NOT also `Hash::make` in the form, or
  it double-hashes. On edit use `->dehydrated(filled)` so an empty password field is ignored.
- A custom Filament page becomes the panel home by overriding the **method**
  `getRoutePath(Panel $panel): string { return '/'; }` — the `$routePath` property is ignored
  by the base `Page`.
- MariaDB's default collation is case-insensitive, so `where('name', 'lowercase')` still
  matches — don't assert case-sensitivity in software-name tests.
- **No closure route handlers** — `php artisan route:cache` (run during deploy) aborts on any
  `Route::get('/', fn () => ...)`. The home route uses `Route::redirect('/', '/dashboard')`
  for this reason. Route *groups* with a closure are fine; only route *handlers* break it.
- `<x-filament::input.select>` is `w-full` by default — add an inline `style="width:auto"` to
  keep it inline (e.g. the KW switcher). Inline styles in a Filament page Blade can't do dark
  mode; use a `<style>` block with `.dark` selectors (Filament toggles `.dark` on `<html>`).

---

## Real data (for Phase 9 import — not yet wired to real files)

**Employee CSV** header row (exact strings, German-Excel export — likely semicolon-delimited,
Windows-1252/UTF-8-BOM):
`PC-Nummer`→pc_nummer · `Login`→kvgg_nummer · `Vorname`→vorname · `Nachname`→nachname ·
`eMail-Adresse`→email · `Fachabteilung`→abteilung. (Note lowercase `e` in `eMail-Adresse`.)
Real value formats: PC like `PC7438` (no dash), KVGG like `kvgg1111` (lowercase) — differs
from the seeder's dummy format, but real values come from the CSV. The `EmployeeImporter`
already handles these headers and encodings.

**Open:** still need a few sample rows of the real employee export (confirm `.csv` vs `.xlsx`)
and of the SCCM hardware export (to map headers → `laptop_configs` old_* fields) before
finalizing those importers.

---

## Rules

- **Never put real employee data in Git.** `.env` must stay gitignored — verify with
  `git check-ignore .env` before any commit involving data.
- Test before moving on; fix all errors before continuing.
- Commit after every working change, German commit messages matching existing style.
- Verify UI changes in the browser before committing when practical.

---

## Deployment (Phase 9) — complete

The app runs on the real server (Debian 13 Trixie, PHP 8.4, Apache, MariaDB).

**Workflow — always use the bundle** (server has no Composer/Node/git):
1. Make and commit changes on the dev laptop
2. `bash deploy/bundle.sh` → produces `laptop-austausch-paket.tar.gz` (18 MB, includes `vendor/` + `public/build/`)
3. Upload the `.tar.gz` to cloud/USB and transfer to server

**First install** (once):
```bash
tar -xzf laptop-austausch-paket.tar.gz
cd laptop-austausch
sudo bash deploy/setup.sh   # asks for server address, admin name/email/password
```
`setup.sh` auto-detects the PHP version (8.4→8.3→8.2), installs only missing PHP extensions
from official Debian repos (no Sury, no GitHub, no nodesource), creates the DB/user, writes
`.env`, migrates, seeds the software catalog, creates the admin login, sets permissions, and
configures the Apache vhost. DB password is preserved across re-runs (read from existing `.env`).

**Updates** (after first install):
```bash
# On server, in the parent dir of the installation:
tar -xzf laptop-austausch-paket.tar.gz   # overwrites files; .env and storage/ are preserved
cd laptop-austausch
sudo bash deploy/update.sh               # maintenance mode → migrate → caches → back online
```

- `deploy/setup.sh` — first install. `deploy/bundle.sh` — builds the bundle on the dev laptop.
  `deploy/update.sh` — updates an existing install (bundle-aware, PHP auto-detected).
- `deploy/.env.production.example`, `deploy/apache-vhost.conf` — templates filled in by setup.sh.
- `DEPLOY.md` — German step-by-step guide.
- After first install: import employee CSV, generate slots, review software catalog in admin panel.

## Remaining / optional work

- **Optional**: SCCM hardware CSV importer → `laptop_configs` old_* fields (need sample rows).
- **Open**: confirm the real employee export is `.csv` vs `.xlsx` (the importer reads CSV text).

See also `SETUP.md` for the local dev machine setup (Windows + WSL2).
