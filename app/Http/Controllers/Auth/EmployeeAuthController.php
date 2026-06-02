<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeAuthController extends Controller
{
    /**
     * Login-Formular für Mitarbeitende anzeigen.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Anmeldung prüfen: KVGG-Nummer (Benutzername) + PC-Nummer (Passwort).
     *
     * Die PC-Nummer liegt im Klartext vor (siehe Migration), daher vergleichen
     * wir sie direkt – zeitkonstant via hash_equals – statt über einen Hash.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(
            [
                'kvgg_nummer' => ['required', 'string'],
                'pc_nummer' => ['required', 'string'],
            ],
            attributes: [
                'kvgg_nummer' => 'KVGG-Nummer',
                'pc_nummer' => 'PC-Nummer',
            ],
        );

        $employee = Employee::where('kvgg_nummer', trim($credentials['kvgg_nummer']))->first();

        if (! $employee || ! hash_equals($employee->pc_nummer, trim($credentials['pc_nummer']))) {
            throw ValidationException::withMessages([
                'kvgg_nummer' => 'KVGG-Nummer oder PC-Nummer ist nicht korrekt.',
            ]);
        }

        Auth::guard('employee')->login($employee, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Mitarbeitende abmelden.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('employee')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
