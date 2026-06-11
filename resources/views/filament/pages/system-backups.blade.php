<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Vorhandene Sicherungen</x-slot>
        <x-slot name="description">
            SQL-Sicherungen liegen auf dem Server unter <code>storage/app/backups</code>.
            Vor jeder Migration im Produktivbetrieb wird automatisch eine Sicherung erstellt.
        </x-slot>

        @if ($backups->isEmpty())
            <p style="color: #6b7280; font-size: 0.875rem;">
                Noch keine Sicherungen vorhanden. Erstellen Sie oben rechts eine neue Sicherung.
            </p>
        @else
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 0.5rem 0.75rem;">Dateiname</th>
                        <th style="padding: 0.5rem 0.75rem;">Erstellt</th>
                        <th style="padding: 0.5rem 0.75rem;">Größe</th>
                        <th style="padding: 0.5rem 0.75rem; text-align: right;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($backups as $backup)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 0.5rem 0.75rem; font-family: monospace;">{{ $backup['filename'] }}</td>
                            <td style="padding: 0.5rem 0.75rem;">{{ $backup['date']->translatedFormat('d.m.Y, H:i') }} Uhr</td>
                            <td style="padding: 0.5rem 0.75rem;">{{ $backup['size_human'] }}</td>
                            <td style="padding: 0.5rem 0.75rem;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <x-filament::button
                                        tag="a"
                                        href="{{ route('admin.backups.download', $backup['filename']) }}"
                                        color="gray"
                                        size="sm"
                                        icon="heroicon-o-arrow-down-tray">
                                        Herunterladen
                                    </x-filament::button>

                                    <x-filament::button
                                        wire:click="deleteBackup('{{ $backup['filename'] }}')"
                                        wire:confirm="Diese Sicherung wirklich löschen?"
                                        color="danger"
                                        size="sm"
                                        icon="heroicon-o-trash">
                                        Löschen
                                    </x-filament::button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-panels::page>
