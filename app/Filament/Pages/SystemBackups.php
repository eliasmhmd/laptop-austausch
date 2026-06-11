<?php

namespace App\Filament\Pages;

use App\Services\DatabaseBackupService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Datenbank-Sicherungen verwalten: manuell erstellen, herunterladen, löschen
 * und aus einer hochgeladenen .sql-Datei wiederherstellen. Ausschließlich für
 * die Rolle „admin" – Betrachter:innen sehen die Seite nicht.
 */
class SystemBackups extends Page
{
    protected string $view = 'filament.pages.system-backups';

    protected static ?string $title = 'Datensicherung';

    protected static ?string $navigationLabel = 'Datensicherung';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?int $navigationSort = 9;

    /** Nur Admins – versteckt die Navigation und sperrt die Seite für Betrachter:innen. */
    public static function canAccess(): bool
    {
        return static::userIsAdmin();
    }

    protected static function userIsAdmin(): bool
    {
        return Auth::guard('admin')->user()?->isAdmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBackup')
                ->label('Backup erstellen')
                ->icon(Heroicon::OutlinedCircleStack)
                ->action(function (): void {
                    try {
                        $filename = app(DatabaseBackupService::class)->create();

                        Notification::make()
                            ->success()
                            ->title('Backup erstellt')
                            ->body($filename)
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Backup fehlgeschlagen')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('restoreBackup')
                ->label('Wiederherstellen')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('danger')
                ->modalHeading('Datenbank wiederherstellen')
                ->modalDescription('Achtung: Die aktuelle Datenbank wird vollständig durch die hochgeladene Sicherung ersetzt. Dieser Schritt kann nicht rückgängig gemacht werden.')
                ->modalSubmitActionLabel('Jetzt wiederherstellen')
                ->schema([
                    FileUpload::make('file')
                        ->label('SQL-Sicherung (.sql)')
                        ->required()
                        ->storeFiles(false)
                        ->acceptedFileTypes(['application/sql', 'text/plain', 'application/octet-stream', 'text/x-sql']),
                ])
                ->action(function (array $data): void {
                    $upload = $data['file'];

                    if (! $upload instanceof TemporaryUploadedFile
                        || strtolower($upload->getClientOriginalExtension()) !== 'sql') {
                        Notification::make()
                            ->danger()
                            ->title('Ungültige Datei')
                            ->body('Bitte laden Sie eine .sql-Sicherung hoch.')
                            ->send();

                        return;
                    }

                    try {
                        app(DatabaseBackupService::class)->restore($upload->getRealPath());

                        Notification::make()
                            ->success()
                            ->title('Datenbank wiederhergestellt')
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Wiederherstellung fehlgeschlagen')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }

    /**
     * Löscht eine Sicherung (vom Lösch-Button in der Tabelle aufgerufen).
     */
    public function deleteBackup(string $filename): void
    {
        abort_unless(static::userIsAdmin(), 403);

        app(DatabaseBackupService::class)->delete($filename);

        Notification::make()
            ->success()
            ->title('Backup gelöscht')
            ->body($filename)
            ->send();
    }

    protected function getViewData(): array
    {
        return [
            'backups' => app(DatabaseBackupService::class)->all(),
        ];
    }
}
