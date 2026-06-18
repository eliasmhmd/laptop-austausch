<?php

namespace App\Http\Controllers;

use App\Models\DownloadFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Download eines bereitgestellten Dokuments durch angemeldete Mitarbeitende
 * (die Anmeldung erzwingt die Route-Middleware auth:employee). Der Download
 * trägt den Originalnamen der Datei.
 */
class DownloadController extends Controller
{
    public function show(DownloadFile $downloadFile): BinaryFileResponse
    {
        abort_unless(is_file($downloadFile->path()), 404);

        return response()->download($downloadFile->path(), $downloadFile->original_name);
    }
}
