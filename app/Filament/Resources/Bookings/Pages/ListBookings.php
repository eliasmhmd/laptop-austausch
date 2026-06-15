<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Exports\BookingsExport;
use App\Filament\Resources\Bookings\BookingResource;
use App\Services\ImagingSheetExporter;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Buchungen werden komfortabel direkt im Kalender (Panel-Startseite)
            // angelegt: dort auf ein freies Zeitfenster klicken. Dieser Button
            // führt nur dorthin.
            Action::make('createBooking')
                ->label('Neue Buchung anlegen')
                ->icon(Heroicon::OutlinedPlus)
                ->visible(fn (): bool => Auth::guard('admin')->user()?->isAdmin() ?? false)
                ->url(fn (): string => route('filament.admin.pages.kalender')),

            Action::make('imagingDayPdf')
                ->label('Imaging-Blätter (Tag)')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->schema([
                    DatePicker::make('date')
                        ->label('Tag')
                        ->required()
                        ->default('2026-08-10')
                        ->helperText('Ein Blatt je Termin dieses Tages (ohne Stornierungen).'),
                ])
                ->action(function (array $data) {
                    $count = app(ImagingSheetExporter::class)->bookingsForDate($data['date'])->count();

                    if ($count === 0) {
                        Notification::make()
                            ->title('Für diesen Tag gibt es keine Buchungen.')
                            ->warning()
                            ->send();

                        return null;
                    }

                    return redirect()->route('admin.exports.imaging.day', ['date' => $data['date']]);
                }),

            Action::make('exportExcel')
                ->label('Als Excel exportieren')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('gray')
                ->action(function (): BinaryFileResponse {
                    // Exportiert exakt die aktuell gefilterte/sortierte Liste.
                    $query = $this->getFilteredSortedTableQuery();

                    return Excel::download(
                        new BookingsExport($query),
                        'buchungen-'.now()->format('Y-m-d').'.xlsx',
                    );
                }),
        ];
    }
}
