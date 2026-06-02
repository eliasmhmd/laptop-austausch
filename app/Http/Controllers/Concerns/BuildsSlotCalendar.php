<?php

namespace App\Http\Controllers\Concerns;

use App\Models\TimeSlot;
use Illuminate\Support\Collection;

trait BuildsSlotCalendar
{
    /**
     * Liefert die Daten für die Kalenderansicht: Zeitfenster nach
     * Kalenderwoche und Tag gruppiert, die Liste aller Wochen (für den Filter)
     * und die IDs der aktuell freien Fenster (für die Live-Färbung via Alpine).
     *
     * @return array{calendar: Collection, weeks: Collection, selectedKw: int|null, availableIds: Collection}
     */
    protected function slotCalendar(?int $kw): array
    {
        $slots = TimeSlot::query()
            ->when($kw, fn ($q) => $q->where('calendar_week', $kw))
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        return [
            'calendar' => $slots->groupBy([
                'calendar_week',
                fn (TimeSlot $slot): string => $slot->slot_date->format('Y-m-d'),
            ]),
            'weeks' => TimeSlot::query()->distinct()->orderBy('calendar_week')->pluck('calendar_week'),
            'selectedKw' => $kw,
            'availableIds' => $slots->filter->isAvailable()->pluck('id')->values(),
        ];
    }
}
