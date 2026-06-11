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

## Current state (as of 2026-06-11)

**Employee side: complete.** **Admin panel: complete.** Polish (Phase 8) essentially done.
Only deployment (Phase 9) and two optional importers remain. ~107 feature tests pass.

| Phase | Status | What shipped |
|-------|--------|--------------|
| 1 — DB & models | ✅ | 7 tables, models, factories, seeders (2 admins, 150 employees, 200 slots, 50 bookings) |
| 2 — Employee auth | ✅ | `employee` guard, KVGG-Nr. + PC-Nr. login, dashboard, logout |
| 3 — Slot booking | ✅ | KW-grouped calendar, green/red, Alpine live availability, double-booking guard |
| 4 — Reschedule | ✅ | Atomic cancel-old + book-new; config carries over |
| 5 — Laptop config form | ✅ | Manufacturer + software-for-reimaging (see decision below) |
| 6 — iCal download | ✅ | `.ics` download, owner-only |
| 7 — Admin panel (Filament v4) | ✅ | All resources, slot generator, CSV import, admin accounts, no-show/sick, manual booking mgmt, PDF + Excel export, software catalog |
| 8 — Polish & testing | ✅ (mostly) | German error pages (404/403/419/500/503/429), fresh-seed flow verified |
| 9 — Deployment | ⬜ | Not started |

**Beyond the original spec, also built:**
- **Admin-Kalender as the panel home** — replaces the default Filament dashboard *and* the
  daily-load chart *and* the TimeSlots resource. A week grid (KW switcher) shows each slot
  as frei/belegt; clicking a free slot opens a create-booking dialog, clicking a booked slot
  opens the booking. "Slots generieren" lives here as a header action.
- **Software-Katalog**: admin-managed catalog + employee-form autocomplete that self-heals
  (unknown entries are auto-added as non-standard). Catalog rows are clickable → a "Verwendet
  von" list of who requested that software.
- **Zusätzliche Angaben**: optional free-text field on the laptop form, surfaced on the
  booking confirmation, the admin infolist, and the printed imaging sheet.

**Login credentials (seeded dummy data):**
`admin@kreisgg.de` / `password` (role admin) · `viewer@kreisgg.de` / `password` (role viewer).

---

## Tech stack (as built — deviates from the original spec)

The original spec said Laravel 11 + Filament v3. That was wrong and was overridden:

- **Laravel 13** + **Filament v4** — Filament v3 cannot run on Laravel 13. (Laravel Boost is
  installed; see `.mcp.json`.)
- **PHP 8.3**, Composer, Node.js. Frontend: Blade + Tailwind CSS + Alpine.js (Vite build).
- **MariaDB** locally (matches MySQL in production). App DB `laptop_austausch`, app user
  `laptop_app`. `root` uses socket auth (needs sudo).
- **Excel**: `maatwebsite/excel`. **PDF**: `barryvdh/laravel-dompdf`.
- Production server: **Linux + Apache + MySQL**. Needs system extensions **`php-intl`** AND
  **`php-gd`** (Filament needs intl; Excel/image writing needs gd). Run `npm run build` after
  every deploy/pull.

### Two auth guards (`config/auth.php`)
- `employee` — public side. Login = `kvgg_nummer` (username, case-insensitive) +
  `pc_nummer` (password, exact/case-sensitive).
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
- **`SlotGenerator`** — generates weekday slots (8/day: 08:00–15:00), idempotent
  (`firstOrCreate`), with a `HOLIDAYS` hook. Used by the seeder and the "Slots generieren" action.
- **`EmployeeImporter`** — CSV import: auto-detects delimiter (`;`/`,`/tab), Windows-1252→UTF-8
  for umlauts, order-independent header mapping, upsert on `kvgg_nummer`, per-row error collection.
- **`ImagingSheetExporter`** — builds the printable "Imaging-Blatt" PDF (the checklist the
  technician lays next to the new laptop). `sheetData()` is separately testable.
- **`SoftwareCatalogResolver`** — case-insensitive `firstOrCreate` for software names
  (self-healing catalog); `normalizeNames()` trims/dedupes.

### Filament resources (`app/Filament/`) — v4 splits each resource into `*Resource.php` + `Tables/` + `Schemas/` + `Pages/`
- **`Pages/Kalender.php`** — the panel home (`getRoutePath()` returns `/`). Week grid; create-booking
  dialog on free slots; "Slots generieren" header action. View: `resources/views/filament/pages/kalender.blade.php`.
- **Bookings** — read-only list + detail infolist. Detail page has admin actions: mark
  no-show / sick (with reason), reset to confirmed, move, cancel, print imaging PDF.
- **Employees** — read-only list + detail; "Mitarbeitende importieren" header action (CSV upload).
- **AdminUsers** — full CRUD, **admin-only** (`canAccess()` → isAdmin; 403s viewers).
- **SoftwareCatalogs** — CRUD (viewer read-only); View page with "Verwendet von" relation
  manager (`UsageRelationManager`) listing who requested each software.

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

---

## Database schema

Migrations in `database/migrations/` (created in this order):
`admin_users`, `employees`, `time_slots`, `bookings`, `software_catalog`, `booking_software`,
`laptop_configs`, plus `add_additional_notes_to_laptop_configs_table`.

- **admin_users**: id, name, email (unique), password (hashed), role (enum: admin, viewer), timestamps
- **employees**: id, kvgg_nummer (unique, login username), vorname, nachname, email, abteilung,
  pc_nummer (login password, **plaintext**), last_laptop_exchange (nullable date), timestamps
- **time_slots**: id, slot_date, start_time, end_time, calendar_week, status (enum: available,
  booked, blocked), capacity (default 1), booked_count (default 0), created_by (FK admin_users), timestamps
- **bookings**: id, employee_id (FK), time_slot_id (FK, unique), status (enum: confirmed,
  cancelled, completed, no_show, sick), cancellation_reason, unplanned_note, booked_at, timestamps
- **software_catalog**: id, name, version, publisher, is_standard (bool, default true), timestamps
- **booking_software**: id, booking_id (FK), software_catalog_id (nullable FK),
  custom_software_name (nullable), is_custom (bool, default false), timestamps
- **laptop_configs**: id, booking_id (FK, unique), old_* hardware fields, new_* fields,
  **additional_notes** (nullable text — added later), timestamps

Note: new employee-form submissions link software via the resolver and no longer write
`is_custom` rows; the column remains for legacy data and the imaging sheet's "(Spezial)" marker.

---

## Testing

- PHP here has **no `pdo_sqlite`** (only `pdo_mysql`), so tests run against **MariaDB**, not
  SQLite. `phpunit.xml` points at a separate DB **`laptop_austausch_test`** (user `laptop_app`).
  This must NEVER point at the dev DB `laptop_austausch`. Creating it / granting needs sudo.
- Run: `php artisan test`. Rebuild assets after view/CSS/JS edits: `npm run build`.
- **Gotcha:** a single test that does `actingAs(admin)->get` THEN `actingAs(viewer)->get`
  fails — Filament's `AuthenticateSession` invalidates the session on a mid-test user switch
  (302). Split into one test per user.

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

## Remaining work

- **Phase 9 — Deployment**: Apache config for Laravel, `git pull`, `composer install --no-dev`,
  production `.env`, `php artisan migrate`, install `php-intl` + `php-gd`, `npm run build`,
  import real employee CSV, generate real slots, final test.
- **Optional**: SCCM hardware CSV importer → `laptop_configs` old_* fields. Deeper
  responsive/mobile review of the calendar.

See `SETUP.md` for fresh-machine setup (Windows + WSL2).
