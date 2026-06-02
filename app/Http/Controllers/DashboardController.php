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
        return view('dashboard', [
            'employee' => $request->user('employee'),
        ]);
    }
}
