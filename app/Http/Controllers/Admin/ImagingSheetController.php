<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\ImagingSheetExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Liefert die druckbaren Imaging-Blätter (PDF) für das Admin-Panel.
 * Lesen/Drucken ist für beide Admin-Rollen erlaubt (admin + viewer).
 */
class ImagingSheetController extends Controller
{
    public function __construct(private readonly ImagingSheetExporter $exporter) {}

    public function single(Booking $booking): Response|RedirectResponse
    {
        if ($redirect = $this->guardAdmin()) {
            return $redirect;
        }

        return $this->download(
            $this->exporter->pdfForBooking($booking),
            'imaging-blatt-'.$booking->id.'.pdf',
        );
    }

    public function day(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->guardAdmin()) {
            return $redirect;
        }

        $date = $request->validate([
            'date' => ['required', 'date'],
        ])['date'];

        $bookings = $this->exporter->bookingsForDate($date);

        abort_if($bookings->isEmpty(), 404, 'Für diesen Tag gibt es keine Buchungen.');

        return $this->download(
            $this->exporter->pdfForDate($date),
            'imaging-blaetter-'.$date.'.pdf',
        );
    }

    /**
     * Nicht angemeldete Aufrufe zur Admin-Anmeldung umleiten.
     */
    private function guardAdmin(): ?RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return null;
        }

        return redirect()->guest(route('filament.admin.auth.login'));
    }

    private function download(string $pdf, string $filename): Response
    {
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
