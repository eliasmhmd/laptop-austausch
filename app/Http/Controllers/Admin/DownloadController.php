<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Download eines bereitgestellten Dokuments im Admin-Bereich (zum Prüfen).
 * Eigene Anmelde-Prüfung, damit Gäste zur Admin-Anmeldung geleitet werden
 * (analog zum Backup-/Imaging-Download).
 */
class DownloadController extends Controller
{
    public function download(DownloadFile $downloadFile): BinaryFileResponse|RedirectResponse
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }

        abort_unless(Auth::guard('admin')->user()?->isAdmin() ?? false, 403);
        abort_unless(is_file($downloadFile->path()), 404);

        return response()->download($downloadFile->path(), $downloadFile->original_name);
    }
}
