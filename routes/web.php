<?php

use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

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

    // Live-Verfügbarkeit (Alpine.js)
    Route::get('/api/verfuegbarkeit', [BookingController::class, 'availability'])->name('booking.availability');
    Route::get('/api/slot/{timeSlot}', [BookingController::class, 'slotCheck'])->name('booking.slot-check');
});
