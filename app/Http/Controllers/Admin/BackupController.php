<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Download von Datenbank-Sicherungen. Nur für angemeldete Admins (nicht
 * Betrachter:innen). Eigene Anmelde-Prüfung, damit Gäste zur Admin-Anmeldung
 * geleitet werden (analog zum Imaging-PDF-Export).
 */
class BackupController extends Controller
{
    public function __construct(private readonly DatabaseBackupService $backups) {}

    public function download(string $filename): BinaryFileResponse|RedirectResponse
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }

        abort_unless(Auth::guard('admin')->user()?->isAdmin() ?? false, 403);
        abort_unless($this->backups->exists($filename), 404);

        return response()->download($this->backups->path($filename));
    }
}
