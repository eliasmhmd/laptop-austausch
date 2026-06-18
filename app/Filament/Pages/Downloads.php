<?php

namespace App\Filament\Pages;

use App\Models\DownloadFile;
use App\Services\DownloadFileService;
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
 * Dokumente (z. B. PDF-Anleitungen) bereitstellen: hochladen und löschen. Die
 * Dateien erscheinen auf dem Dashboard der Mitarbeitenden zum Download – aber
 * nur, solange mindestens eine Datei vorhanden ist. Nur für die Rolle „admin".
 */
class Downloads extends Page
{
    protected string $view = 'filament.pages.downloads';

    protected static ?string $title = 'Dokumente';

    protected static ?string $navigationLabel = 'Dokumente';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static ?int $navigationSort = 8;

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
            Action::make('uploadDocument')
                ->label('Datei hochladen')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading('Datei für Mitarbeitende bereitstellen')
                ->modalDescription('Die Datei kann anschließend von allen Mitarbeitenden auf ihrem Dashboard heruntergeladen werden.')
                ->modalSubmitActionLabel('Hochladen')
                ->schema([
                    FileUpload::make('file')
                        ->label('Datei')
                        ->required()
                        ->storeFiles(false)
                        ->maxSize(20480) // 20 MB
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'text/plain',
                            'image/png',
                            'image/jpeg',
                            'application/zip',
                        ]),
                ])
                ->action(function (array $data): void {
                    $upload = $data['file'];

                    if (! $upload instanceof TemporaryUploadedFile) {
                        Notification::make()
                            ->danger()
                            ->title('Keine Datei')
                            ->body('Bitte wählen Sie eine Datei aus.')
                            ->send();

                        return;
                    }

                    try {
                        $file = app(DownloadFileService::class)->store(
                            $upload,
                            Auth::guard('admin')->id(),
                        );

                        Notification::make()
                            ->success()
                            ->title('Datei hochgeladen')
                            ->body($file->original_name)
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Upload fehlgeschlagen')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }

    /**
     * Löscht ein Dokument (vom Lösch-Button in der Tabelle aufgerufen).
     */
    public function deleteDownload(int $id): void
    {
        abort_unless(static::userIsAdmin(), 403);

        $file = DownloadFile::find($id);

        if ($file === null) {
            return;
        }

        $name = $file->original_name;
        app(DownloadFileService::class)->delete($file);

        Notification::make()
            ->success()
            ->title('Datei gelöscht')
            ->body($name)
            ->send();
    }

    protected function getViewData(): array
    {
        return [
            'files' => DownloadFile::query()->latest()->get(),
        ];
    }
}
