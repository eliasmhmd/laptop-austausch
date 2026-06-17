<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Globale Schlüssel-Wert-Einstellungen, die für alle Mitarbeitenden gleich sind.
 * Aktuell: die Raumnummer für den Laptop-Austausch (siehe Einstellungen-Seite).
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Schlüssel der Raumangabe (z. B. „Raum 345"). */
    public const ROOM_KEY = 'austausch_raum';

    /** Fällt zurück, solange noch kein Raum gepflegt wurde. */
    public const ROOM_FALLBACK = 'IT-Center, Kreis Groß-Gerau';

    /** Liest einen Einstellungswert (oder den Standard, falls nicht gesetzt). */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::query()->where('key', $key)->value('value');

        return ($value === null || $value === '') ? $default : $value;
    }

    /** Speichert einen Einstellungswert (legt ihn bei Bedarf an). */
    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Die gepflegte Raumangabe, oder den Standardtext als Rückfall. */
    public static function room(): string
    {
        return static::get(self::ROOM_KEY, self::ROOM_FALLBACK);
    }
}
