<?php

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\ImagingSheetController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaptopConfigController;
use App\Http\Controllers\RescheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// Admin-PDF-Export (Imaging-Blätter). Eigene Anmelde-Prüfung im Controller,
// damit Gäste zur Admin-Anmeldung (nicht zur Mitarbeiter-Anmeldung) gelangen.
Route::prefix('admin/exports/imaging')->name('admin.exports.imaging.')->group(function () {
    Route::get('/booking/{booking}', [ImagingSheetController::class, 'single'])->name('single');
    Route::get('/day', [ImagingSheetController::class, 'day'])->name('day');
});

// Download von Datenbank-Sicherungen (nur Admin – Prüfung im Controller).
Route::get('admin/backups/{filename}/download', [BackupController::class, 'download'])
    ->name('admin.backups.download');

// Öffentlicher Bereich für Mitarbeitende (nur für nicht angemeldete Nutzer).
Route::middleware('guest:employee')->group(function () {
    Route::get('/login', [EmployeeAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [EmployeeAuthController::class, 'login'])->name('login.store');
});

// Geschützter Bereich für angemeldete Mitarbeitende.
Route::middleware('auth:employee')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [EmployeeAuthController::class, 'logout'])->name('logout');

    // Terminbuchung (Phase 3)
    Route::get('/kalender', [BookingController::class, 'calendar'])->name('booking.calendar');
    Route::get('/buchen/{timeSlot}', [BookingController::class, 'create'])->name('booking.create');
    Route::post('/buchen/{timeSlot}', [BookingController::class, 'store'])->name('booking.store');
    Route::get('/termin/{booking}', [BookingController::class, 'show'])->name('booking.show');
    Route::get('/termin/{booking}/ical', [BookingController::class, 'ical'])->name('booking.ical');

    // Laptop-/Software-Angaben (Phase 5)
    Route::get('/termin/{booking}/konfiguration', [LaptopConfigController::class, 'edit'])->name('config.edit');
    Route::post('/termin/{booking}/konfiguration', [LaptopConfigController::class, 'update'])->name('config.update');

    // Termin verschieben (Phase 4)
    Route::get('/verschieben', [RescheduleController::class, 'edit'])->name('reschedule.edit');
    Route::get('/verschieben/{timeSlot}', [RescheduleController::class, 'confirm'])->name('reschedule.confirm');
    Route::post('/verschieben/{timeSlot}', [RescheduleController::class, 'update'])->name('reschedule.update');

    // Live-Verfügbarkeit (Alpine.js)
    Route::get('/api/verfuegbarkeit', [BookingController::class, 'availability'])->name('booking.availability');
    Route::get('/api/slot/{timeSlot}', [BookingController::class, 'slotCheck'])->name('booking.slot-check');
});
