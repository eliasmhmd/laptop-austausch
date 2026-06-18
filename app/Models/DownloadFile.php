<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein vom Admin bereitgestelltes Dokument (z. B. eine PDF-Anleitung). Die
 * eigentliche Datei liegt unter storage/app/downloads/<stored_name>; der
 * Originalname dient zur Anzeige und beim Download. Wird Mitarbeitenden auf
 * dem Dashboard angeboten – aber nur, solange überhaupt eine Datei existiert.
 */
class DownloadFile extends Model
{
    protected $fillable = ['original_name', 'stored_name', 'mime_type', 'size', 'uploaded_by'];

    protected $casts = ['size' => 'integer'];

    /** Verzeichnis der bereitgestellten Dateien (wird bei Bedarf angelegt). */
    public static function directory(): string
    {
        $dir = storage_path('app/downloads');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /** Vollständiger Pfad zur abgelegten Datei. */
    public function path(): string
    {
        return static::directory().'/'.$this->stored_name;
    }

    /** Menschenlesbare Dateigröße (z. B. „2,3 MB"). */
    public function sizeHuman(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return ($i === 0 ? $bytes : number_format($bytes, 1)).' '.$units[$i];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'uploaded_by');
    }
}
