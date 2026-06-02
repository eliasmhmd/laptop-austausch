\# Claude Code Prompt — Laptop Austausch Buchungstool

\# Kreis Groß-Gerau



\---



the application is in german.

You are helping me build a Laravel web application for Kreis Groß-Gerau, a German local government district. The application manages the exchange of 150 leased laptops through a self-service booking system. I am a complete beginner. Work in small steps and always verify each step works before moving to the next.



\## My Current Setup

\- Laravel 11 is already installed in this project folder

\- MariaDB is installed and running locally

\- Git is configured and connected to GitHub

\- PHP 8.3, Composer, and Node.js are installed

\- The final server runs Linux + Apache + MySQL

\- There will be NO email functionality (server has no mail access)

\- I will work with dummy/fake data during development but the real data is a csv with the pc number, kvgg number, email, Vorname, Nachname and Abteilung.



\## Project Description

Employees of Kreis Groß-Gerau log in and book a timeslot to hand in their old laptop and pick up their new one. The IT department manages the process through an admin panel.



\---



\## Full Requirements



\### Employee Side (Public Facing)

1\. \*\*Login\*\*: Employee enters their KVGG number + PC number as password

2\. \*\*Dashboard\*\*: Shows two buttons — "Termin buchen" and "Termin verschieben"

3\. \*\*Slot Selection\*\*:

&#x20;  - Show 6-week calendar: August 10, 2026 to September 11, 2026

&#x20;  - Weekdays only (Monday-Friday, no weekends, no public holidays)

&#x20;  - 8 slots per day: 08:00, 09:00, 10:00, 11:00, 12:00, 13:00, 14:00, 15:00

&#x20;  - Each slot has capacity of 1 (one employee per hour)

&#x20;  - Color coded: green = available, red = booked (not selectable)

&#x20;  - Filter by calendar week (KW)

&#x20;  - Real-time availability check when selecting a slot

4\. \*\*Laptop Configuration Form\*\* (after slot selected):

&#x20;  - PC number: automatically filled from KVGG number lookup

&#x20;  - Hardware details: manufacturer, model, serial number, CPU, RAM, storage, OS, inventory number

&#x20;  - Software list: employee selects from known software catalog

&#x20;  - Special/custom software: manual text input field

5\. \*\*Booking Confirmation\*\*:

&#x20;  - Slot is set to "booked"

&#x20;  - Entry appears in admin overview

&#x20;  - Show confirmation page with booking details

&#x20;  - Optional: iCal (.ics) file download button (no email needed)

6\. \*\*Reschedule\*\*: Employee can cancel current booking and select a new slot



\### Admin Side

Admin users are completely separate from employees (different login, different table).



\*\*Two admin roles:\*\*

\- `viewer`: read-only access

\- `admin`: full read + write access



\*\*Admin Features:\*\*

1\. Dashboard with daily load overview (how many slots booked per day)

2\. All bookings overview with employee info + PC details

3\. Manual booking management (create, edit, cancel bookings)

4\. Mark unplanned events: No-Show or Sick (with reason field)

5\. Slot generation: button to generate all slots for a date range (e.g. KW 28-35)

6\. Employee import via CSV upload

7\. Export all bookings to Excel (.xlsx) and PDF

8\. Create and manage admin users (admin role only)



\### Nice to Have (build after MVP works)

\- SCCM sync: auto-populate software list from SCCM

\- Reporting dashboard with charts (bookings per day, no-show rate, most common software)



\---



\## Database Schema

Build these migrations in this exact order:



1\. admin\_users

2\. employees

3\. time\_slots

4\. bookings

5\. software\_catalog

6\. booking\_software

7\. laptop\_configs



\### Table Definitions:



\*\*admin\_users\*\*

\- id, name, email (unique), password, role (enum: admin, viewer), timestamps



\*\*employees\*\*

\- id, kvgg\_nummer (unique, used as login username)

\- vorname, nachname, email, abteilung

\- pc\_nummer (used as login password — store hashed)

\- last\_laptop\_exchange (nullable date)

\- timestamps



\*\*time\_slots\*\*

\- id, slot\_date (date), start\_time (time), end\_time (time)

\- calendar\_week (integer)

\- status (enum: available, booked, blocked) default: available

\- capacity (integer) default: 1

\- booked\_count (integer) default: 0

\- created\_by (nullable FK to admin\_users)

\- timestamps



\*\*bookings\*\*

\- id, employee\_id (FK), time\_slot\_id (FK unique — one booking per slot)

\- status (enum: confirmed, cancelled, completed, no\_show, sick) default: confirmed

\- cancellation\_reason (nullable text)

\- unplanned\_note (nullable text)

\- booked\_at (timestamp)

\- timestamps



\*\*software\_catalog\*\*

\- id, name, version (nullable), publisher (nullable), is\_standard (boolean default true)

\- timestamps



\*\*booking\_software\*\*

\- id, booking\_id (FK), software\_catalog\_id (nullable FK)

\- custom\_software\_name (nullable — for manual/special software)

\- is\_custom (boolean default false)

\- timestamps



\*\*laptop\_configs\*\*

\- id, booking\_id (FK unique)

\- old\_pc\_nummer, old\_serial\_number, old\_manufacturer, old\_model

\- old\_cpu, old\_ram\_gb, old\_storage\_gb, old\_storage\_type (enum: SSD, HDD)

\- old\_operating\_system, old\_inventory\_number

\- new\_pc\_nummer (nullable), new\_serial\_number (nullable), new\_inventory\_number (nullable)

\- timestamps



\---



\## Tech Stack

\- Framework: Laravel 11

\- Database: MariaDB locally / MySQL on production server

\- Frontend: Blade templates + Tailwind CSS + Alpine.js

\- Admin Panel: Filament v3

\- Auth: Two separate guards — employees and admins

\- Excel Export: Maatwebsite/Laravel-Excel

\- PDF Export: barryvdh/laravel-dompdf

\- Design: Simple, modern, clean — mobile responsive via Tailwind



\---



\## Development Phases



\### PHASE 1 — Database \& Models

1\. Set up .env with MariaDB connection (database: laptop\_austausch)

2\. Create the database in MariaDB

3\. Create all migrations in the order listed above

4\. Create Eloquent models with relationships

5\. Create Factories and Seeders with fake data:

&#x20;  - 150 fake employees with German names

&#x20;  - All time slots for Aug 10 to Sep 11, 2026 (weekdays only, 8 slots/day)

&#x20;  - 2 admin users (one admin role, one viewer role)

&#x20;  - 50 random bookings to test with

6\. Run: php artisan migrate:fresh --seed

7\. STOP and verify: Check that all tables exist and data is correct



\### PHASE 2 — Employee Authentication

1\. Custom employee guard (separate from admin auth)

2\. Login page: KVGG number + PC number as password

3\. Middleware to protect employee routes

4\. Dashboard placeholder after login

5\. Logout functionality

6\. STOP and verify: Login works, wrong credentials rejected



\### PHASE 3 — Slot Booking (Core Feature)

1\. Calendar view: 6 weeks, grouped by KW, filter by KW

2\. Color code: green = available, red = booked

3\. Real-time availability check with Alpine.js + Laravel API

4\. Booking form and confirmation

5\. Update slot status to booked

6\. Prevent double booking

7\. Confirmation page

8\. STOP and verify: Full booking flow works end to end



\### PHASE 4 — Reschedule

1\. "Termin verschieben" on dashboard

2\. Show current booking

3\. Cancel old slot, select new slot

4\. STOP and verify: Old slot turns green, new slot turns red



\### PHASE 5 — Laptop Configuration Form

1\. After booking confirmed, show hardware form

2\. Auto-fill old PC number from employee record

3\. Hardware fields (all listed above)

4\. Software selection from catalog

5\. Special software free text input

6\. Save to laptop\_configs and booking\_software

7\. STOP and verify: Config saved and linked to booking



\### PHASE 6 — iCal Download

1\. After booking, show "Zum Kalender hinzufügen" button

2\. Generate .ics file for download

3\. No email needed — browser download only

4\. STOP and verify: .ics opens correctly in Outlook



\### PHASE 7 — Admin Panel (Filament v3)

Install Filament, then build one resource at a time:

1\. Filament install, admin guard, /admin login

2\. Bookings Resource: list, filter, view details

3\. TimeSlots Resource: view slots, generate by date range

4\. Employees Resource: list + CSV import

5\. AdminUsers Resource: manage accounts (admin role only)

6\. Daily load chart widget

7\. Mark no-show/sick with reason

8\. Export Excel + PDF

9\. STOP and verify: All admin features work, viewer cannot edit



\### PHASE 8 — Polish \& Testing

1\. Responsive design check

2\. Error handling (slot taken, session timeout)

3\. Full flow test with fresh seed data

4\. Bug fixes



\### PHASE 9 — Deployment

1\. Apache .htaccess for Laravel

2\. Git pull on server

3\. composer install --no-dev

4\. Production .env setup

5\. php artisan migrate

6\. Import real employee CSV

7\. Generate real slots

8\. Final test



\---



\## Rules

\- Test after every phase before moving on

\- Commit after every working phase: git add . \&\& git commit -m "Phase X done"

\- Never put real employee data in Git

\- Fix all errors before continuing to next phase



\---



\## START NOW — PHASE 1 ONLY



Please start with Phase 1 only. Do not work on Phase 2 yet.



First show me the current .env database section, then:

1\. Update .env for MariaDB

2\. Create the database in MariaDB

3\. Create all migrations

4\. Create models with relationships

5\. Create seeders with realistic German fake data

6\. Run migrate:fresh --seed

7\. Verify everything is correct



Ask me before starting Phase 2.



