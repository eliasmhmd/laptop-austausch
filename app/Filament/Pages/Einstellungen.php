<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Allgemeine Einstellungen, die für alle Mitarbeitenden gelten. Aktuell wird
 * hier der Ort (Gebäude + Raum) für den Laptop-Austausch gepflegt – er erscheint
 * auf der Bestätigungsseite der Mitarbeitenden und in der Kalender-Datei (.ics).
 * Nur für die Rolle „admin".
 */
class Einstellungen extends Page
{
    protected string $view = 'filament.pages.einstellungen';

    protected static ?string $title = 'Einstellungen';

    protected static ?string $navigationLabel = 'Einstellungen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 10;

    /** Nur Admins – versteckt die Navigation und sperrt die Seite für Betrachter:innen. */
    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user()?->isAdmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editRoom')
                ->label('Ort ändern')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->modalHeading('Ort für den Laptop-Austausch')
                ->modalDescription('Dieser Ort gilt für alle Termine und erscheint auf der Bestätigungsseite der Mitarbeitenden sowie im Kalender-Eintrag (.ics).')
                ->modalSubmitActionLabel('Speichern')
                ->fillForm(fn (): array => [
                    'room' => Setting::get(Setting::ROOM_KEY),
                ])
                ->schema([
                    TextInput::make('room')
                        ->label('Ort')
                        ->placeholder('z. B. Gebäude B, Raum 345')
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    Setting::set(Setting::ROOM_KEY, trim((string) $data['room']) ?: null);

                    Notification::make()
                        ->success()
                        ->title('Ort gespeichert')
                        ->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'room' => Setting::get(Setting::ROOM_KEY),
            'fallback' => Setting::ROOM_FALLBACK,
        ];
    }
}
