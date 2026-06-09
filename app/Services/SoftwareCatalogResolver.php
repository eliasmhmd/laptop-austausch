<?php

namespace App\Services;

use App\Models\SoftwareCatalog;

/**
 * Wandelt von Mitarbeitenden eingegebene Software-Namen in Katalogeinträge um.
 * Bekannte Namen werden (unabhängig von Groß-/Kleinschreibung) wiederverwendet,
 * neue Namen werden als Zusatzsoftware (is_standard = false) angelegt – so wächst
 * der Katalog selbstständig und Tippvarianten werden vermieden.
 */
class SoftwareCatalogResolver
{
    public function resolve(string $name): SoftwareCatalog
    {
        $name = trim($name);

        $existing = SoftwareCatalog::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        return $existing ?? SoftwareCatalog::create([
            'name' => $name,
            'is_standard' => false,
        ]);
    }

    /**
     * Normalisiert eine Liste eingegebener Namen: trimmen, leere entfernen,
     * Dubletten (case-insensitive) zusammenführen – erste Schreibweise gewinnt.
     *
     * @param  iterable<string>  $names
     * @return list<string>
     */
    public function normalizeNames(iterable $names): array
    {
        $seen = [];

        foreach ($names as $name) {
            $name = trim((string) $name);

            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);

            if (! array_key_exists($key, $seen)) {
                $seen[$key] = $name;
            }
        }

        return array_values($seen);
    }
}
