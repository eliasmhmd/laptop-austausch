<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Allgemeine Einstellungen, die für alle Mitarbeitenden gelten:
 * - der Ort (Gebäude + Raum) für den Laptop-Austausch – erscheint auf der
 *   Bestätigungsseite der Mitarbeitenden und in der Kalender-Datei (.ics);
 * - die Texte im Software-Formular (Einleitung, Softwarecenter-Hinweis samt
 *   Programmliste, Warnhinweis).
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

            Action::make('editSoftwareTexts')
                ->label('Software-Texte bearbeiten')
                ->icon(Heroicon::OutlinedDocumentText)
                ->modalHeading('Texte im Software-Formular')
                ->modalDescription('Diese Texte sehen die Mitarbeitenden auf dem Formular, in dem sie ihre Software angeben. Lassen Sie ein Feld leer, wird der Standardtext angezeigt.')
                ->modalSubmitActionLabel('Speichern')
                ->fillForm(fn (): array => [
                    'intro' => Setting::softwareIntro(),
                    'center_text' => Setting::softwareCenterText(),
                    'center_programs' => implode("\n", Setting::softwareCenterPrograms()),
                    'warning' => Setting::softwareWarning(),
                ])
                ->schema([
                    Textarea::make('intro')
                        ->label('Einleitung')
                        ->helperText('Grauer Text direkt unter der Überschrift „Benötigte Software".')
                        ->rows(3),
                    Textarea::make('center_text')
                        ->label('Softwarecenter-Hinweis')
                        ->helperText('Text im blauen Infokasten.')
                        ->rows(4),
                    Textarea::make('center_programs')
                        ->label('Softwarecenter-Programme')
                        ->helperText('Ein Programm pro Zeile. Wird als Schaltflächen unter dem Hinweis angezeigt.')
                        ->rows(7),
                    Textarea::make('warning')
                        ->label('Warnhinweis')
                        ->helperText('Text im gelben Hinweiskasten.')
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    Setting::set(Setting::SOFTWARE_INTRO_KEY, trim((string) $data['intro']) ?: null);
                    Setting::set(Setting::SOFTWARE_CENTER_TEXT_KEY, trim((string) $data['center_text']) ?: null);
                    Setting::set(Setting::SOFTWARE_CENTER_PROGRAMS_KEY, trim((string) $data['center_programs']) ?: null);
                    Setting::set(Setting::SOFTWARE_WARNING_KEY, trim((string) $data['warning']) ?: null);

                    Notification::make()
                        ->success()
                        ->title('Texte gespeichert')
                        ->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'room' => Setting::get(Setting::ROOM_KEY),
            'fallback' => Setting::ROOM_FALLBACK,
            'softwareIntro' => Setting::softwareIntro(),
            'softwareCenterText' => Setting::softwareCenterText(),
            'softwareCenterPrograms' => Setting::softwareCenterPrograms(),
            'softwareWarning' => Setting::softwareWarning(),
        ];
    }
}
