<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Bereitgestellte Dokumente</x-slot>
        <x-slot name="description">
            Diese Dateien können alle Mitarbeitenden auf ihrem Dashboard herunterladen.
            Solange hier keine Datei liegt, sehen die Mitarbeitenden keinen Download-Bereich.
        </x-slot>

        @if ($files->isEmpty())
            <p style="color: #6b7280; font-size: 0.875rem;">
                Noch keine Dateien vorhanden. Laden Sie oben rechts eine Datei hoch.
            </p>
        @else
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 0.5rem 0.75rem;">Dateiname</th>
                        <th style="padding: 0.5rem 0.75rem;">Hochgeladen</th>
                        <th style="padding: 0.5rem 0.75rem;">Größe</th>
                        <th style="padding: 0.5rem 0.75rem; text-align: right;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($files as $file)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 0.5rem 0.75rem;">{{ $file->original_name }}</td>
                            <td style="padding: 0.5rem 0.75rem;">{{ $file->created_at->translatedFormat('d.m.Y, H:i') }} Uhr</td>
                            <td style="padding: 0.5rem 0.75rem;">{{ $file->sizeHuman() }}</td>
                            <td style="padding: 0.5rem 0.75rem;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <x-filament::button
                                        tag="a"
                                        href="{{ route('admin.downloads.download', $file) }}"
                                        color="gray"
                                        size="sm"
                                        icon="heroicon-o-arrow-down-tray">
                                        Herunterladen
                                    </x-filament::button>

                                    <x-filament::button
                                        wire:click="deleteDownload({{ $file->id }})"
                                        wire:confirm="Diese Datei wirklich löschen?"
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
