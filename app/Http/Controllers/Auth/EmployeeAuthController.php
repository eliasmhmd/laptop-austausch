<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeAuthController extends Controller
{
    /**
     * Erlaubte Fehlversuche, bevor für {@see self::LOCKOUT_SECONDS} gesperrt wird.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Sperrdauer nach zu vielen Fehlversuchen (10 Minuten).
     */
    private const LOCKOUT_SECONDS = 600;

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

        $this->ensureIsNotRateLimited($request);

        $employee = Employee::where('kvgg_nummer', trim($credentials['kvgg_nummer']))->first();

        if (! $employee || ! hash_equals($employee->pc_nummer, trim($credentials['pc_nummer']))) {
            // Fehlversuch zählen – nach zu vielen Versuchen wird gesperrt.
            RateLimiter::hit($this->throttleKey($request), self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'kvgg_nummer' => 'KVGG-Nummer oder PC-Nummer ist nicht korrekt.',
            ]);
        }

        // Erfolgreiche Anmeldung: Zähler zurücksetzen.
        RateLimiter::clear($this->throttleKey($request));

        Auth::guard('employee')->login($employee, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Bei zu vielen Fehlversuchen die Anmeldung sperren und eine deutsche
     * Fehlermeldung mit der verbleibenden Wartezeit ausgeben.
     */
    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));
        $minutes = (int) ceil($seconds / 60);

        throw ValidationException::withMessages([
            'kvgg_nummer' => $minutes > 1
                ? "Zu viele fehlerhafte Anmeldeversuche. Bitte versuchen Sie es in {$minutes} Minuten erneut."
                : 'Zu viele fehlerhafte Anmeldeversuche. Bitte versuchen Sie es in einer Minute erneut.',
        ]);
    }

    /**
     * Drossel-Schlüssel pro KVGG-Nummer. Bewusst NICHT an die IP gebunden:
     * Die App läuft nur im internen Netz, und so wird genau das betroffene
     * Konto gesperrt – egal von welchem Arbeitsplatz aus getippt wird.
     */
    private function throttleKey(Request $request): string
    {
        return 'employee-login|'.Str::transliterate(Str::lower(trim((string) $request->input('kvgg_nummer'))));
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
