<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Warteschlange: Die Verwaltung geht alle (bestätigten) Buchungen einzeln durch
 * und markiert sie als bearbeitet. Zwei Reiter: „Offen" (noch nicht durch-
 * gegangen, reviewed_at = NULL) und „Bereit" (bereits bestätigt).
 */
class Warteschlange extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.warteschlange';

    protected static ?string $title = 'Warteschlange';

    protected static ?string $navigationLabel = 'Warteschlange';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 2;

    /** Aktiver Reiter: „offen" oder „bereit". */
    public string $activeTab = 'offen';

    /** Navigations-Badge: Anzahl noch offener Buchungen. */
    public static function getNavigationBadge(): ?string
    {
        $count = self::offenQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function offenCount(): int
    {
        return self::offenQuery()->count();
    }

    public function bereitCount(): int
    {
        return Booking::query()->where('status', 'confirmed')->whereNotNull('reviewed_at')->count();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Booking::query()
                ->where('status', 'confirmed')
                ->when(
                    $this->activeTab === 'bereit',
                    fn (Builder $q) => $q->whereNotNull('reviewed_at'),
                    fn (Builder $q) => $q->whereNull('reviewed_at'),
                )
                ->with(['employee', 'timeSlot']))
            ->defaultSort('booked_at', 'asc')
            ->emptyStateHeading($this->activeTab === 'bereit' ? 'Noch nichts bestätigt' : 'Warteschlange ist leer')
            ->emptyStateIcon(Heroicon::OutlinedQueueList)
            ->columns([
                TextColumn::make('employee.kvgg_nummer')
                    ->label('KVGG-Nr.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.nachname')
                    ->label('Mitarbeiter:in')
                    ->formatStateUsing(fn (Booking $record): string => trim($record->employee?->vorname.' '.$record->employee?->nachname))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.abteilung')
                    ->label('Abteilung')
                    ->toggleable(),
                TextColumn::make('timeSlot.slot_date')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('timeSlot.start_time')
                    ->label('Uhrzeit')
                    ->formatStateUsing(fn (?string $state): string => $state ? substr($state, 0, 5).' Uhr' : '—'),
                TextColumn::make('reviewed_at')
                    ->label('Bestätigt am')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->visible(fn (): bool => $this->activeTab === 'bereit'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Ansehen')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url(fn (Booking $record): string => BookingResource::getUrl('view', ['record' => $record])),
                Action::make('confirm')
                    ->label('Bestätigen')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Booking $record): bool => $this->canManage() && ! $record->isReviewed())
                    ->action(function (Booking $record): void {
                        $record->update(['reviewed_at' => now()]);

                        Notification::make()
                            ->success()
                            ->title('Buchung bestätigt')
                            ->body('Die Buchung wurde als bearbeitet markiert.')
                            ->send();
                    }),
                Action::make('reopen')
                    ->label('Zurück in Warteschlange')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Zurück in die Warteschlange')
                    ->modalDescription('Die Buchung wird wieder als „offen" markiert und muss erneut bestätigt werden.')
                    ->visible(fn (Booking $record): bool => $this->canManage() && $record->isReviewed())
                    ->action(function (Booking $record): void {
                        $record->update(['reviewed_at' => null]);

                        Notification::make()
                            ->title('Zurück in die Warteschlange verschoben.')
                            ->send();
                    }),
            ]);
    }

    /**
     * Offene (bestätigte, aber noch nicht bearbeitete) Buchungen.
     *
     * @return Builder<Booking>
     */
    private static function offenQuery(): Builder
    {
        return Booking::query()->where('status', 'confirmed')->whereNull('reviewed_at');
    }

    /** Nur Admins dürfen bestätigen – Betrachter:innen sehen die Warteschlange nur an. */
    protected function canManage(): bool
    {
        return Auth::guard('admin')->user()?->isAdmin() ?? false;
    }
}
