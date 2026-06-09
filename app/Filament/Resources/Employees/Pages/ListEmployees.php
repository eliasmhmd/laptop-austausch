<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Services\EmployeeImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Mitarbeitende importieren')
                ->icon('heroicon-o-arrow-up-tray')
                // Nur Admins dürfen importieren, Betrachter nicht.
                ->visible(fn (): bool => Auth::guard('admin')->user()?->isAdmin() ?? false)
                ->modalHeading('Mitarbeitende aus CSV importieren')
                ->modalDescription('CSV mit den Spalten PC-Nummer, Login, Vorname, Nachname, eMail-Adresse, Fachabteilung. Bestehende Einträge (gleiche KVGG-Nummer) werden aktualisiert.')
                ->modalSubmitActionLabel('Importieren')
                ->schema([
                    FileUpload::make('file')
                        ->label('CSV-Datei')
                        ->required()
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                        ->disk('local')
                        ->directory('imports'),
                ])
                ->action(function (array $data): void {
                    $path = Storage::disk('local')->path($data['file']);

                    $result = app(EmployeeImporter::class)->importFile($path);

                    // Hochgeladene Datei nach dem Import wieder entfernen.
                    Storage::disk('local')->delete($data['file']);

                    $body = "{$result['created']} neu angelegt, {$result['updated']} aktualisiert.";
                    if ($result['errors'] !== []) {
                        $body .= ' '.count($result['errors']).' Zeile(n) übersprungen.';
                    }

                    $notification = Notification::make()->title('Import abgeschlossen')->body($body);
                    $result['errors'] === [] ? $notification->success() : $notification->warning();
                    $notification->send();
                }),
        ];
    }
}
