<x-filament-panels::page>
    {{-- Hilfsklassen mit Hell-/Dunkelmodus-Varianten (Inline-Styles können kein
         dark: – Filament setzt die Klasse .dark auf <html>). --}}
    <style>
        .kal-head  { color:#374151; }
        .kal-sub   { color:#6b7280; }
        .kal-time  { color:#6b7280; }
        .kal-muted { color:#6b7280; }
        .kal-empty { background:#f9fafb; }
        .dark .kal-head  { color:#e5e7eb; }
        .dark .kal-sub   { color:#9ca3af; }
        .dark .kal-time  { color:#9ca3af; }
        .dark .kal-muted { color:#9ca3af; }
        .dark .kal-empty { background:#1f2937; }
    </style>

    @php
        $statusLabels = \App\Filament\Resources\Bookings\Tables\BookingsTable::STATUS_LABELS;

        // Hintergrund-/Textfarbe je Buchungsstatus (belegte Felder).
        $statusStyle = [
            'confirmed' => 'background:#dbeafe;color:#1e3a8a;',
            'completed' => 'background:#e0e7ff;color:#3730a3;',
            'no_show'   => 'background:#fee2e2;color:#991b1b;',
            'sick'      => 'background:#fef3c7;color:#92400e;',
        ];

        // Vorherige / nächste Kalenderwoche für die Pfeil-Navigation.
        $weekList = $weeks->values();
        $pos = $weekList->search($currentKw);
        $prevKw = ($pos !== false && $pos > 0) ? $weekList[$pos - 1] : null;
        $nextKw = ($pos !== false && $pos < $weekList->count() - 1) ? $weekList[$pos + 1] : null;
    @endphp

    <x-filament::section>
        <x-slot name="heading">Kalenderwoche {{ $currentKw }}</x-slot>

        <x-slot name="afterHeader">
            <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                <x-filament::button
                    size="sm" color="gray" icon="heroicon-m-chevron-left"
                    :disabled="$prevKw === null"
                    wire:click="$set('kw', {{ $prevKw ?? 'null' }})"
                >KW {{ $prevKw ?? $currentKw }}</x-filament::button>

                <x-filament::input.select wire:model.live="kw" style="width:auto;min-width:7rem;">
                    @foreach ($weeks as $week)
                        <option value="{{ $week }}">KW {{ $week }}</option>
                    @endforeach
                </x-filament::input.select>

                <x-filament::button
                    size="sm" color="gray" icon="heroicon-m-chevron-right" icon-position="after"
                    :disabled="$nextKw === null"
                    wire:click="$set('kw', {{ $nextKw ?? 'null' }})"
                >KW {{ $nextKw ?? $currentKw }}</x-filament::button>
            </div>
        </x-slot>

        @if ($days->isEmpty())
            <p class="kal-muted" style="text-align:center;padding:2rem 0;">
                Für diese Kalenderwoche gibt es noch keine Zeitfenster.
            </p>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:separate;border-spacing:4px;font-size:.875rem;">
                    <thead>
                        <tr>
                            <th style="width:64px;"></th>
                            @foreach ($days as $day)
                                <th class="kal-head" style="text-align:center;font-weight:600;padding:.25rem;">
                                    {{ $day->translatedFormat('D') }}<br>
                                    <span class="kal-sub" style="font-weight:400;">{{ $day->format('d.m.') }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($times as $time)
                            <tr>
                                <td class="kal-time" style="font-weight:600;white-space:nowrap;padding-right:.25rem;">
                                    {{ $time }}
                                </td>

                                @foreach ($days as $day)
                                    @php
                                        $slot = $grid[$day->format('Y-m-d')][$time] ?? null;
                                        $booking = $slot?->bookings->first();
                                    @endphp

                                    <td style="height:56px;text-align:center;vertical-align:middle;border-radius:.5rem;padding:0;">
                                        @if (! $slot)
                                            {{-- Kein Zeitfenster an diesem Tag/Uhrzeit --}}
                                            <div class="kal-empty" style="height:100%;border-radius:.5rem;"></div>
                                        @elseif ($booking)
                                            {{-- Belegt: Name + Status, verlinkt auf die Buchungsdetails --}}
                                            <a
                                                href="{{ \App\Filament\Resources\Bookings\BookingResource::getUrl('view', ['record' => $booking->getKey()]) }}"
                                                style="display:block;height:100%;border-radius:.5rem;padding:.4rem;text-decoration:none;line-height:1.2;{{ $statusStyle[$booking->status] ?? 'background:#e5e7eb;color:#374151;' }}"
                                                title="{{ $statusLabels[$booking->status] ?? $booking->status }}"
                                            >
                                                <strong style="display:block;">{{ trim($booking->employee?->vorname.' '.$booking->employee?->nachname) }}</strong>
                                                <span style="font-size:.72rem;opacity:.85;">{{ $statusLabels[$booking->status] ?? $booking->status }}</span>
                                            </a>
                                        @elseif ($canManage)
                                            {{-- Frei + buchbar (Admin) --}}
                                            <button
                                                type="button"
                                                wire:click="mountAction('createBooking', { timeSlotId: {{ $slot->id }} })"
                                                style="height:100%;width:100%;border:0;cursor:pointer;border-radius:.5rem;background:#dcfce7;color:#166534;font-weight:600;"
                                                title="Termin buchen"
                                            >frei +</button>
                                        @else
                                            {{-- Frei (Betrachter: nur Anzeige) --}}
                                            <div style="height:100%;display:flex;align-items:center;justify-content:center;border-radius:.5rem;background:#dcfce7;color:#166534;font-weight:600;">frei</div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Legende --}}
            <div class="kal-muted" style="display:flex;flex-wrap:wrap;gap:1rem;margin-top:1rem;font-size:.8rem;">
                <span><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#dcfce7;"></span> frei</span>
                <span><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#dbeafe;"></span> bestätigt</span>
                <span><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#fee2e2;"></span> nicht erschienen</span>
                <span><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#fef3c7;"></span> krank</span>
                <span><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#e0e7ff;"></span> abgeschlossen</span>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
