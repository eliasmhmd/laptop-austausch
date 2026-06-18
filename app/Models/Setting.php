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

    // --- Texte im Software-Formular (von Admins editierbar) -----------------

    /** Grauer Einleitungstext unter der Überschrift „Benötigte Software". */
    public const SOFTWARE_INTRO_KEY = 'software_intro_text';

    /** Blauer Infokasten: Hinweis auf das Softwarecenter. */
    public const SOFTWARE_CENTER_TEXT_KEY = 'software_center_text';

    /** Programmliste im Softwarecenter-Kasten (eine Zeile = ein Programm). */
    public const SOFTWARE_CENTER_PROGRAMS_KEY = 'software_center_programs';

    /** Gelber Warnhinweis: nur tatsächlich genutzte Software angeben. */
    public const SOFTWARE_WARNING_KEY = 'software_warning_text';

    public const SOFTWARE_INTRO_FALLBACK = 'Standardprogramme (Office, Browser …) sind bereits auf jedem Gerät. Geben Sie hier nur zusätzlich benötigte Programme an.';

    public const SOFTWARE_CENTER_TEXT_FALLBACK = 'Für alle Mitarbeiter*innen wird über das „Softwarecenter" die folgende Software zum Download bereitgestellt. Hier können Sie nach Bedarf eine Auswahl treffen und sich diese eigenständig auf Ihrem Dienst-Laptop installieren:';

    public const SOFTWARE_WARNING_FALLBACK = 'Bitte beachten: Geben Sie ausschließlich Software an, die Sie aktuell tatsächlich auf Ihrem Laptop besitzen und nutzen. Programme, die Sie nicht verwenden, müssen nicht neu installiert werden.';

    /** @var list<string> */
    public const SOFTWARE_CENTER_PROGRAMS_FALLBACK = ['.NET 8.0', 'Adobe Reader', 'ECM_Start', 'KeePass XC', 'Notepad++', 'Paint.net', 'PDF 24'];

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

    /** Einleitungstext im Software-Formular (oder Standardtext). */
    public static function softwareIntro(): string
    {
        return static::get(self::SOFTWARE_INTRO_KEY, self::SOFTWARE_INTRO_FALLBACK);
    }

    /** Softwarecenter-Hinweis im Software-Formular (oder Standardtext). */
    public static function softwareCenterText(): string
    {
        return static::get(self::SOFTWARE_CENTER_TEXT_KEY, self::SOFTWARE_CENTER_TEXT_FALLBACK);
    }

    /** Gelber Warnhinweis im Software-Formular (oder Standardtext). */
    public static function softwareWarning(): string
    {
        return static::get(self::SOFTWARE_WARNING_KEY, self::SOFTWARE_WARNING_FALLBACK);
    }

    /**
     * Programmliste für den Softwarecenter-Kasten (eine Zeile = ein Programm),
     * oder die Standardliste als Rückfall.
     *
     * @return list<string>
     */
    public static function softwareCenterPrograms(): array
    {
        $raw = static::get(self::SOFTWARE_CENTER_PROGRAMS_KEY);

        if ($raw === null) {
            return self::SOFTWARE_CENTER_PROGRAMS_FALLBACK;
        }

        $items = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $items = array_map('trim', $items);

        return array_values(array_filter($items, static fn (string $line): bool => $line !== ''));
    }
}
