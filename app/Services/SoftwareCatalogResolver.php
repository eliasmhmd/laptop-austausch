<?php

namespace App\Services;

use App\Models\SoftwareCatalog;

/**
 * Wandelt von Mitarbeitenden eingegebene Software-Namen in Katalogeinträge um.
 * Freigegebene Namen werden (unabhängig von Groß-/Kleinschreibung) wiederverwendet;
 * unbekannte Namen werden als „pending" (zur Prüfung) angelegt – nur für die
 * einreichende Person und Admins sichtbar, bis ein Admin sie freigibt.
 */
class SoftwareCatalogResolver
{
    /**
     * @param  int|null  $employeeId  Wer den Namen einträgt – steuert die Sichtbarkeit
     *                                neu angelegter „pending"-Einträge (submitted_by).
     */
    public function resolve(string $name, ?int $employeeId = null): SoftwareCatalog
    {
        $name = trim($name);
        $lower = mb_strtolower($name);

        // 1. Freigegebene Software hat Vorrang – für alle sichtbar.
        $approved = SoftwareCatalog::query()
            ->approved()
            ->whereRaw('LOWER(name) = ?', [$lower])
            ->first();

        if ($approved) {
            return $approved;
        }

        // 2. Eigener, bereits eingereichter (noch nicht freigegebener) Eintrag.
        if ($employeeId !== null) {
            $ownPending = SoftwareCatalog::query()
                ->pending()
                ->where('submitted_by', $employeeId)
                ->whereRaw('LOWER(name) = ?', [$lower])
                ->first();

            if ($ownPending) {
                return $ownPending;
            }
        }

        // 3. Neu zur Prüfung anlegen – sichtbar nur für die einreichende Person + Admins.
        return SoftwareCatalog::create([
            'name' => $name,
            'is_standard' => false,
            'status' => SoftwareCatalog::STATUS_PENDING,
            'submitted_by' => $employeeId,
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
