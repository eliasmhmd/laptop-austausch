<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Ort für den Laptop-Austausch</x-slot>
        <x-slot name="description">
            Dieser Ort gilt für alle Termine und erscheint auf der Bestätigungsseite
            der Mitarbeitenden sowie im Kalender-Eintrag (.ics). Über „Ort ändern"
            oben rechts können Sie ihn anpassen.
        </x-slot>

        @if ($room)
            <p style="font-size: 1.125rem; font-weight: 600;">{{ $room }}</p>
        @else
            <p style="color: #6b7280; font-size: 0.875rem;">
                Es ist noch kein Ort festgelegt. Solange kein Ort gepflegt ist, wird
                <strong>{{ $fallback }}</strong> angezeigt.
            </p>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Texte im Software-Formular</x-slot>
        <x-slot name="description">
            Diese Texte sehen die Mitarbeitenden auf dem Formular, in dem sie ihre
            Software angeben. Über „Software-Texte bearbeiten" oben rechts können Sie
            sie anpassen.
        </x-slot>

        <dl style="display: flex; flex-direction: column; gap: 1rem;">
            <div>
                <dt style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #6b7280;">Einleitung</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem;">{{ $softwareIntro }}</dd>
            </div>
            <div>
                <dt style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #6b7280;">Softwarecenter-Hinweis</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem;">{{ $softwareCenterText }}</dd>
                @if (count($softwareCenterPrograms))
                    <dd style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.375rem;">
                        @foreach ($softwareCenterPrograms as $sc)
                            <span style="border-radius: 9999px; background: #eff6ff; color: #1d4ed8; padding: 0.125rem 0.625rem; font-size: 0.75rem; font-weight: 500;">{{ $sc }}</span>
                        @endforeach
                    </dd>
                @endif
            </div>
            <div>
                <dt style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #6b7280;">Warnhinweis</dt>
                <dd style="margin-top: 0.25rem; font-size: 0.875rem;">{{ $softwareWarning }}</dd>
            </div>
        </dl>
    </x-filament::section>
</x-filament-panels::page>
