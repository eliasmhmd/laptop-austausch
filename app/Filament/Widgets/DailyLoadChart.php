<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\TimeSlot;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DailyLoadChart extends ChartWidget
{
    protected ?string $heading = 'Auslastung pro Tag';

    protected ?string $description = 'Aktive Termine je Werktag im Austausch-Zeitraum (ohne Stornierungen).';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        // Alle erzeugten Termintage (chronologisch) als Grundgerüst, damit
        // auch leere Tage als Null-Balken sichtbar bleiben.
        $dates = TimeSlot::query()
            ->select('slot_date')
            ->distinct()
            ->orderBy('slot_date')
            ->pluck('slot_date');

        // Anzahl aktiver Buchungen je Termindatum (alles außer storniert).
        $counts = Booking::query()
            ->join('time_slots', 'time_slots.id', '=', 'bookings.time_slot_id')
            ->where('bookings.status', '!=', 'cancelled')
            ->groupBy('time_slots.slot_date')
            ->selectRaw('time_slots.slot_date as d, COUNT(*) as c')
            ->pluck('c', 'd');

        $labels = [];
        $data = [];

        foreach ($dates as $date) {
            $carbon = Carbon::parse($date);
            $key = $carbon->format('Y-m-d');

            $labels[] = $carbon->translatedFormat('D d.m.'); // z. B. "Mo 10.08."
            $data[] = (int) ($counts[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Gebuchte Termine',
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
