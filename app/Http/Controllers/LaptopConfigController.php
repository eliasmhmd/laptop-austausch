<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSoftware;
use App\Models\LaptopConfig;
use App\Models\SoftwareCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LaptopConfigController extends Controller
{
    /**
     * Auswählbare Hersteller des Altgeräts.
     *
     * @var list<string>
     */
    public const MANUFACTURERS = ['Dell', 'Lenovo', 'HP', 'Fujitsu', 'Microsoft', 'Acer', 'Sonstige'];

    /**
     * Formular: Hersteller + aktuell genutzte Software (für die Neu-Imaging des
     * Ersatzgeräts). Nur die/der Buchende darf die eigene Buchung bearbeiten.
     */
    public function edit(Request $request, Booking $booking): View|RedirectResponse
    {
        $this->authorizeBooking($request, $booking);

        if (! $booking->isActive()) {
            return redirect()->route('booking.show', $booking);
        }

        $booking->load('laptopConfig', 'software');

        return view('config.edit', [
            'booking' => $booking,
            'manufacturers' => self::MANUFACTURERS,
            'catalog' => SoftwareCatalog::orderBy('name')->get(),
            'selectedCatalogIds' => $booking->software->whereNotNull('software_catalog_id')->pluck('software_catalog_id')->all(),
            'customSoftware' => $booking->software->where('is_custom', true)->pluck('custom_software_name')->implode("\n"),
        ]);
    }

    /**
     * Speichern: Hersteller im laptop_config (mit automatisch übernommener
     * alter PC-Nummer) und die Softwareauswahl in booking_software.
     */
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($request, $booking);
        abort_unless($booking->isActive(), 403);

        $validated = $request->validate([
            'manufacturer' => ['nullable', Rule::in(self::MANUFACTURERS)],
            'software' => ['array'],
            'software.*' => ['integer', Rule::exists('software_catalog', 'id')],
            'custom_software' => ['nullable', 'string', 'max:2000'],
        ]);

        $customNames = collect(preg_split('/\r\n|\r|\n/', $validated['custom_software'] ?? ''))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->unique()
            ->values();

        DB::transaction(function () use ($booking, $request, $validated, $customNames): void {
            LaptopConfig::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    // Alte PC-Nummer automatisch aus dem Mitarbeiterdatensatz.
                    'old_pc_nummer' => $request->user('employee')->pc_nummer,
                    'old_manufacturer' => $validated['manufacturer'] ?? null,
                ],
            );

            // Softwareauswahl komplett neu setzen (vermeidet Duplikate).
            BookingSoftware::where('booking_id', $booking->id)->delete();

            foreach ($validated['software'] ?? [] as $catalogId) {
                BookingSoftware::create([
                    'booking_id' => $booking->id,
                    'software_catalog_id' => $catalogId,
                    'is_custom' => false,
                ]);
            }

            foreach ($customNames as $name) {
                BookingSoftware::create([
                    'booking_id' => $booking->id,
                    'custom_software_name' => $name,
                    'is_custom' => true,
                ]);
            }
        });

        return redirect()->route('booking.show', $booking)
            ->with('status', 'Ihre Angaben wurden gespeichert.');
    }

    /**
     * Stellt sicher, dass die Buchung der/dem angemeldeten Mitarbeitenden gehört.
     */
    private function authorizeBooking(Request $request, Booking $booking): void
    {
        abort_unless($booking->employee_id === $request->user('employee')->id, 403);
    }
}
