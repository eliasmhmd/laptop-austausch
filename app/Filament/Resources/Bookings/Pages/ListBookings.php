<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Exports\BookingsExport;
use App\Filament\Resources\Bookings\BookingResource;
use App\Services\BookingManager;
use App\Services\ImagingSheetExporter;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBooking')
                ->label('Neue Buchung anlegen')
                ->icon(Heroicon::OutlinedPlus)
                ->visible(fn (): bool => Auth::guard('admin')->user()?->isAdmin() ?? false)
                ->schema([
                    Select::make('employee_id')
                        ->label('Mitarbeiter:in')
                        ->options(fn (): array => BookingResource::employeeOptions())
                        ->searchable()
                        ->required(),
                    Select::make('time_slot_id')
                        ->label('Zeitfenster')
                        ->options(fn (): array => BookingResource::availableSlotOptions())
                        ->searchable()
                        ->required()
                        ->helperText('Es werden nur aktuell freie Zeitfenster angezeigt.'),
                ])
                ->action(function (array $data): void {
                    try {
                        app(BookingManager::class)->create(
                            (int) $data['employee_id'],
                            (int) $data['time_slot_id'],
                        );
                    } catch (RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()->title('Buchung wurde angelegt.')->success()->send();
                }),

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
                ->action(function (array $data): ?RedirectResponse {
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
