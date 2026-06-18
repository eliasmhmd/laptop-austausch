<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Mitarbeiter-Dashboard nach dem Login. Die beiden Aktionen
     * ("Termin buchen" / "Termin verschieben") werden in Phase 3 und 4 aktiviert.
     */
    public function index(Request $request): View
    {
        $employee = $request->user('employee');

        return view('dashboard', [
            'employee' => $employee,
            'activeBooking' => $employee->activeBookings()->with('timeSlot')->first(),
            'room' => \App\Models\Setting::room(),
            // Vom Admin bereitgestellte Dokumente – der Download-Bereich erscheint
            // nur, wenn überhaupt eine Datei vorhanden ist.
            'downloads' => \App\Models\DownloadFile::query()->latest()->get(),
        ]);
    }
}
