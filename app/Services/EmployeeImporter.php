<?php

namespace App\Services;

use App\Models\Employee;

/**
 * Importiert Mitarbeitende aus einer CSV-Datei (z. B. Export aus Excel).
 *
 * Robust gegenüber typischen Stolperfallen deutscher Excel-Exporte:
 * - Trennzeichen wird automatisch erkannt (;  ,  oder Tab)
 * - Kodierung wird nach UTF-8 konvertiert (Windows-1252 → UTF-8 für Umlaute)
 * - Spalten werden über die Überschrift zugeordnet (Reihenfolge egal)
 *
 * Bestehende Mitarbeitende (gleiche KVGG-Nummer) werden aktualisiert, nicht doppelt angelegt.
 */
class EmployeeImporter
{
    /**
     * Bekannte Spaltenüberschriften → Datenbankfeld. Schlüssel sind klein
     * geschrieben und ohne umgebende Leerzeichen.
     *
     * @var array<string, string>
     */
    private const HEADER_MAP = [
        'pc-nummer' => 'pc_nummer',
        'pc_nummer' => 'pc_nummer',
        'login' => 'kvgg_nummer',
        'kvgg-nummer' => 'kvgg_nummer',
        'kvgg_nummer' => 'kvgg_nummer',
        'vorname' => 'vorname',
        'nachname' => 'nachname',
        'email-adresse' => 'email',
        'e-mail-adresse' => 'email',
        'emailadresse' => 'email',
        'email' => 'email',
        'fachabteilung' => 'abteilung',
        'abteilung' => 'abteilung',
    ];

    /**
     * Importiert direkt aus einer Datei.
     *
     * @return array{created: int, updated: int, errors: list<string>}
     */
    public function importFile(string $path): array
    {
        return $this->import((string) file_get_contents($path));
    }

    /**
     * Importiert aus dem CSV-Inhalt (String).
     *
     * @return array{created: int, updated: int, errors: list<string>}
     */
    public function import(string $content): array
    {
        $content = $this->normalizeEncoding($content);
        $lines = $this->splitLines($content);

        $created = 0;
        $updated = 0;
        $errors = [];

        if ($lines === []) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['Die Datei ist leer.']];
        }

        $delimiter = $this->detectDelimiter($lines[0]);
        $header = $this->mapHeader(str_getcsv(array_shift($lines), $delimiter));

        if (! in_array('kvgg_nummer', $header, true) || ! in_array('pc_nummer', $header, true)) {
            return ['created' => 0, 'updated' => 0, 'errors' => [
                'Pflichtspalten "Login" (KVGG-Nummer) und "PC-Nummer" wurden in der Kopfzeile nicht gefunden.',
            ]];
        }

        foreach ($lines as $i => $line) {
            $rowNumber = $i + 2; // +1 für 0-Index, +1 für die übersprungene Kopfzeile
            $values = str_getcsv($line, $delimiter);

            // Werte den Feldnamen zuordnen.
            $row = [];
            foreach ($header as $col => $field) {
                if ($field !== null) {
                    $row[$field] = isset($values[$col]) ? trim((string) $values[$col]) : '';
                }
            }

            if (($row['kvgg_nummer'] ?? '') === '' || ($row['pc_nummer'] ?? '') === '') {
                $errors[] = "Zeile $rowNumber: KVGG-Nummer oder PC-Nummer fehlt – übersprungen.";

                continue;
            }

            $employee = Employee::updateOrCreate(
                ['kvgg_nummer' => $row['kvgg_nummer']],
                [
                    'pc_nummer' => $row['pc_nummer'],
                    'vorname' => $row['vorname'] ?? '',
                    'nachname' => $row['nachname'] ?? '',
                    'email' => $row['email'] ?? '',
                    'abteilung' => $row['abteilung'] ?? '',
                ],
            );

            $employee->wasRecentlyCreated ? $created++ : $updated++;
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    private function normalizeEncoding(string $content): string
    {
        // UTF-8 BOM entfernen.
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        return $content;
    }

    /**
     * @return list<string>
     */
    private function splitLines(string $content): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        return array_values(array_filter(
            explode("\n", $content),
            static fn (string $line): bool => trim($line) !== '',
        ));
    }

    private function detectDelimiter(string $headerLine): string
    {
        $candidates = [';' => substr_count($headerLine, ';'), ',' => substr_count($headerLine, ','), "\t" => substr_count($headerLine, "\t")];
        arsort($candidates);
        $best = array_key_first($candidates);

        return $candidates[$best] > 0 ? $best : ';';
    }

    /**
     * @param  list<string>  $rawHeader
     * @return array<int, string|null>  Spaltenindex → Feldname (oder null, wenn unbekannt)
     */
    private function mapHeader(array $rawHeader): array
    {
        $mapped = [];
        foreach ($rawHeader as $col => $name) {
            $key = mb_strtolower(trim($name));
            $mapped[$col] = self::HEADER_MAP[$key] ?? null;
        }

        return $mapped;
    }
}
