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

    /** Fußzeilentext (nach „© <Jahr>") auf allen Mitarbeitenden-Seiten. */
    public const FOOTER_KEY = 'footer_text';

    public const FOOTER_FALLBACK = 'Kreis Groß-Gerau · IT-Center';

    // --- Texte im Software-Formular (von Admins editierbar) -----------------
    // Jeder Text: erste Zeile = Überschrift (fett), Rest = Fließtext.

    /** Grüner Kasten: bereits vorinstallierte Standard-Software. */
    public const SOFTWARE_INTRO_KEY = 'software_intro_text';

    /** Blauer Infokasten: Hinweis auf das Softwarecenter. */
    public const SOFTWARE_CENTER_TEXT_KEY = 'software_center_text';

    /** Programmliste im Standard-Software-Kasten (eine Zeile = ein Programm). */
    public const SOFTWARE_STANDARD_PROGRAMS_KEY = 'software_standard_programs';

    /** Programmliste im Softwarecenter-Kasten (eine Zeile = ein Programm). */
    public const SOFTWARE_CENTER_PROGRAMS_KEY = 'software_center_programs';

    /** Einleitung „Ihre Arbeitsumgebung" direkt über dem Eingabefeld. */
    public const SOFTWARE_WARNING_KEY = 'software_warning_text';

    public const SOFTWARE_INTRO_FALLBACK = "Standard-Software – Auf jedem Laptop enthalten\n\nFolgende Software wird standardmäßig auf dem neuen Laptop installiert und muss nicht zusätzlich angegeben werden:";

    public const SOFTWARE_CENTER_TEXT_FALLBACK = "Softwarecenter – Eigenständige Installation nach Bedarf\n\nFür alle Mitarbeiter*innen wird über das „Softwarecenter“ die folgende Software zum Download bereitgestellt. Hier können Sie nach Bedarf selbst eine Auswahl treffen und sich diese eigenständig auf Ihrem Dienst-Laptop installieren:";

    public const SOFTWARE_WARNING_FALLBACK = "Ihre Arbeitsumgebung\n\nBitte geben Sie nun die spezifische Software an, die Sie an Ihrem Arbeitsplatz benötigen. Bei der Texteingabe werden Ihnen Ausfüll-Vorschläge angezeigt. Software, die Sie künftig nicht mehr benötigen, können Sie in dieser Auflistung weglassen. Diese wird dann auf Ihrem neuen Laptop nicht installiert.";

    /** @var list<string> */
    public const SOFTWARE_STANDARD_PROGRAMS_FALLBACK = ['Microsoft Office Standard (Outlook, Word, Excel, Powerpoint, OneNote)', 'Microsoft Edge', 'Firefox', 'ProCall', 'VPN Forty Client', 'WebEx', '7zip'];

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

    /** Der gepflegte Fußzeilentext, oder den Standardtext als Rückfall. */
    public static function footer(): string
    {
        return static::get(self::FOOTER_KEY, self::FOOTER_FALLBACK);
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
     * Programmliste für den Standard-Software-Kasten (eine Zeile = ein Programm),
     * oder die Standardliste als Rückfall.
     *
     * @return list<string>
     */
    public static function standardPrograms(): array
    {
        return static::programList(self::SOFTWARE_STANDARD_PROGRAMS_KEY, self::SOFTWARE_STANDARD_PROGRAMS_FALLBACK);
    }

    /**
     * Programmliste für den Softwarecenter-Kasten (eine Zeile = ein Programm),
     * oder die Standardliste als Rückfall.
     *
     * @return list<string>
     */
    public static function softwareCenterPrograms(): array
    {
        return static::programList(self::SOFTWARE_CENTER_PROGRAMS_KEY, self::SOFTWARE_CENTER_PROGRAMS_FALLBACK);
    }

    /**
     * Zerlegt einen mehrzeiligen Einstellungswert in eine Liste (eine Zeile =
     * ein Eintrag, Leerzeilen werden verworfen); Rückfall auf $fallback.
     *
     * @param  list<string>  $fallback
     * @return list<string>
     */
    protected static function programList(string $key, array $fallback): array
    {
        $raw = static::get($key);

        if ($raw === null) {
            return $fallback;
        }

        $items = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $items = array_map('trim', $items);

        return array_values(array_filter($items, static fn (string $line): bool => $line !== ''));
    }

    /**
     * Zerlegt einen Formulartext in Überschrift (erste Zeile) und Fließtext
     * (Rest). So lassen sich Überschrift + Text in einem einzigen Feld pflegen.
     *
     * @return array{0: string, 1: string} [Überschrift, Fließtext]
     */
    public static function splitHeading(?string $text): array
    {
        $text = trim((string) $text);

        if ($text === '') {
            return ['', ''];
        }

        $parts = preg_split('/\r\n|\r|\n/', $text, 2);

        return [trim($parts[0]), trim($parts[1] ?? '')];
    }
}
