<?php

namespace App\Services;

use App\Models\BookingSoftware;
use App\Models\SoftwareCatalog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Führt zwei Katalogeinträge zusammen (z. B. „excel" und „Excel"). Alle
 * Buchungs-Verknüpfungen des aufzulösenden Eintrags wandern auf den Ziel-Eintrag,
 * danach wird der aufzulösende Eintrag gelöscht – ohne Doppel-Verknüpfungen.
 */
class SoftwareCatalogMerger
{
    /**
     * @param  SoftwareCatalog  $loser   wird aufgelöst und gelöscht
     * @param  SoftwareCatalog  $winner  bleibt bestehen und erhält alle Verknüpfungen
     */
    public function merge(SoftwareCatalog $loser, SoftwareCatalog $winner): void
    {
        if ($loser->is($winner)) {
            throw new InvalidArgumentException('Ein Eintrag kann nicht mit sich selbst zusammengeführt werden.');
        }

        DB::transaction(function () use ($loser, $winner): void {
            // Buchungen, die den Ziel-Eintrag bereits haben → doppelte Verknüpfung
            // des aufzulösenden Eintrags entfernen.
            $winnerBookingIds = BookingSoftware::query()
                ->where('software_catalog_id', $winner->id)
                ->pluck('booking_id');

            BookingSoftware::query()
                ->where('software_catalog_id', $loser->id)
                ->whereIn('booking_id', $winnerBookingIds)
                ->delete();

            // Restliche Verknüpfungen auf den Ziel-Eintrag umhängen.
            BookingSoftware::query()
                ->where('software_catalog_id', $loser->id)
                ->update(['software_catalog_id' => $winner->id]);

            $loser->delete();
        });
    }
}
