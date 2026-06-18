<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSoftware;
use App\Models\LaptopConfig;
use App\Models\Setting;
use App\Models\SoftwareCatalog;
use App\Services\SoftwareCatalogResolver;
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

        $booking->load('laptopConfig', 'software.softwareCatalog');

        // Vorschläge: freigegebene Zusatzsoftware (Standardsoftware ist ohnehin auf
        // jedem Gerät) PLUS die eigenen, noch nicht freigegebenen Einträge. Fremde
        // „pending"-Einträge bleiben verborgen.
        $employeeId = $request->user('employee')->id;
        $suggestions = SoftwareCatalog::query()
            ->where('is_standard', false)
            ->where(function ($query) use ($employeeId): void {
                $query->approved()
                    ->orWhere(fn ($q) => $q->pending()->where('submitted_by', $employeeId));
            })
            ->orderBy('name')
            ->pluck('name')
            ->values();

        // Bereits erfasste Software dieser Buchung als Namen (Katalog + Altdaten).
        $selectedNames = $booking->software
            ->map(fn (BookingSoftware $sw): ?string => $sw->softwareCatalog?->name ?: $sw->custom_software_name)
            ->filter()
            ->unique()
            ->values();

        return view('config.edit', [
            'booking' => $booking,
            'manufacturers' => self::MANUFACTURERS,
            'suggestions' => $suggestions,
            'selectedNames' => $selectedNames,
            // Von Admins über die Einstellungen pflegbare Texte.
            'softwareIntro' => Setting::softwareIntro(),
            'standardPrograms' => Setting::standardPrograms(),
            'softwareCenterText' => Setting::softwareCenterText(),
            'softwareCenterPrograms' => Setting::softwareCenterPrograms(),
            'softwareWarning' => Setting::softwareWarning(),
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
            'software_names' => ['array', 'max:100'],
            'software_names.*' => ['string', 'max:255'],
            'additional_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $resolver = app(SoftwareCatalogResolver::class);
        $names = $resolver->normalizeNames($validated['software_names'] ?? []);
        $employeeId = $request->user('employee')->id;

        DB::transaction(function () use ($booking, $request, $validated, $names, $resolver, $employeeId): void {
            LaptopConfig::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    // Alte PC-Nummer automatisch aus dem Mitarbeiterdatensatz.
                    'old_pc_nummer' => $request->user('employee')->pc_nummer,
                    'old_manufacturer' => $validated['manufacturer'] ?? null,
                    'additional_notes' => $validated['additional_notes'] ?? null,
                ],
            );

            // Softwareauswahl komplett neu setzen (vermeidet Duplikate). Jeder Name
            // wird auf einen Katalogeintrag abgebildet; unbekannte Namen werden als
            // Zusatzsoftware neu angelegt (selbstpflegender Katalog).
            BookingSoftware::where('booking_id', $booking->id)->delete();

            foreach ($names as $name) {
                $catalog = $resolver->resolve($name, $employeeId);

                BookingSoftware::create([
                    'booking_id' => $booking->id,
                    'software_catalog_id' => $catalog->id,
                    'is_custom' => false,
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
