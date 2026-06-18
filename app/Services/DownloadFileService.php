<?php

namespace App\Services;

use App\Models\DownloadFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Speichert und löscht die vom Admin bereitgestellten Dokumente. Die Dateien
 * liegen unter storage/app/downloads (per .gitignore vom Repo ausgeschlossen);
 * gespeichert wird unter einem zufälligen Namen, der Originalname steht in der
 * Datenbank (siehe DownloadFile).
 */
class DownloadFileService
{
    /** Speichert eine hochgeladene Datei und legt den passenden DB-Eintrag an. */
    public function store(UploadedFile $file, ?int $adminId = null): DownloadFile
    {
        $source = $file->getRealPath();

        if ($source === false || ! is_file($source)) {
            throw new RuntimeException('Die hochgeladene Datei konnte nicht gelesen werden.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $destination = DownloadFile::directory().'/'.$storedName;

        if (! copy($source, $destination)) {
            throw new RuntimeException('Die Datei konnte nicht gespeichert werden.');
        }

        return DownloadFile::create([
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getClientMimeType(),
            'size' => (int) filesize($destination),
            'uploaded_by' => $adminId,
        ]);
    }

    /** Löscht Datei und DB-Eintrag. */
    public function delete(DownloadFile $file): void
    {
        $path = $file->path();

        if (is_file($path)) {
            unlink($path);
        }

        $file->delete();
    }
}
