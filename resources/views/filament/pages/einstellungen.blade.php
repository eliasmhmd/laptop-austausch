<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Raum für den Laptop-Austausch</x-slot>
        <x-slot name="description">
            Dieser Raum gilt für alle Termine und erscheint auf der Bestätigungsseite
            der Mitarbeitenden sowie im Kalender-Eintrag (.ics). Über „Raum ändern"
            oben rechts können Sie ihn anpassen.
        </x-slot>

        @if ($room)
            <p style="font-size: 1.125rem; font-weight: 600;">{{ $room }}</p>
        @else
            <p style="color: #6b7280; font-size: 0.875rem;">
                Es ist noch kein Raum festgelegt. Solange kein Raum gepflegt ist, wird
                <strong>{{ $fallback }}</strong> angezeigt.
            </p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
