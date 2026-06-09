<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Services\BookingManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->markStatusAction(
                name: 'markNoShow',
                label: 'Als „nicht erschienen“ markieren',
                status: 'no_show',
                successTitle: 'Termin als „nicht erschienen“ gespeichert.',
                icon: Heroicon::OutlinedXCircle,
                color: 'danger',
            ),
            $this->markStatusAction(
                name: 'markSick',
                label: 'Als „krank“ markieren',
                status: 'sick',
                successTitle: 'Termin als „krank“ gespeichert.',
                icon: Heroicon::OutlinedExclamationTriangle,
                color: 'warning',
            ),
            Action::make('resetToConfirmed')
                ->label('Auf „bestätigt“ zurücksetzen')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Status zurücksetzen')
                ->modalDescription('Der Termin wird wieder auf „bestätigt“ gesetzt und die Notiz entfernt.')
                ->visible(fn (): bool => $this->canManage()
                    && in_array($this->getRecord()->status, ['no_show', 'sick'], true))
                ->action(function (): void {
                    $this->getRecord()->update([
                        'status' => 'confirmed',
                        'unplanned_note' => null,
                    ]);

                    Notification::make()
                        ->title('Status auf „bestätigt“ zurückgesetzt.')
                        ->success()
                        ->send();
                }),

            Action::make('moveBooking')
                ->label('Termin verschieben')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('primary')
                ->visible(fn (): bool => $this->canManage()
                    && $this->getRecord()->status !== 'cancelled')
                ->schema([
                    Select::make('time_slot_id')
                        ->label('Neues Zeitfenster')
                        ->options(fn (): array => BookingResource::availableSlotOptions($this->getRecord()->time_slot_id))
                        ->searchable()
                        ->required()
                        ->helperText('Es werden nur aktuell freie Zeitfenster angezeigt.'),
                ])
                ->action(function (array $data): void {
                    try {
                        app(BookingManager::class)->move($this->getRecord(), (int) $data['time_slot_id']);
                    } catch (RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()->title('Termin wurde verschoben.')->success()->send();
                }),

            Action::make('cancelBooking')
                ->label('Buchung stornieren')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->visible(fn (): bool => $this->canManage()
                    && $this->getRecord()->status !== 'cancelled')
                ->schema([
                    Textarea::make('cancellation_reason')
                        ->label('Stornogrund')
                        ->placeholder('Optionaler Hinweis, z. B. „Gerät bereits getauscht“.')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->modalHeading('Buchung stornieren')
                ->modalDescription('Das Zeitfenster wird wieder freigegeben.')
                ->modalSubmitActionLabel('Stornieren')
                ->action(function (array $data): void {
                    try {
                        app(BookingManager::class)->cancel($this->getRecord(), $data['cancellation_reason'] ?: null);
                    } catch (RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()->title('Buchung wurde storniert.')->success()->send();
                }),
        ];
    }

    /**
     * Gemeinsame Aktion für „nicht erschienen“ und „krank“ – jeweils mit
     * optionalem Notizfeld, das in unplanned_note gespeichert wird.
     */
    protected function markStatusAction(string $name, string $label, string $status, string $successTitle, Heroicon $icon, string $color): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (): bool => $this->canManage()
                && ! in_array($this->getRecord()->status, ['cancelled', $status], true))
            ->fillForm(fn (): array => ['unplanned_note' => $this->getRecord()->unplanned_note])
            ->schema([
                Textarea::make('unplanned_note')
                    ->label('Grund / Notiz')
                    ->placeholder('Optionaler Hinweis, z. B. „kurzfristig erkrankt“.')
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->action(function (array $data) use ($status, $successTitle): void {
                $this->getRecord()->update([
                    'status' => $status,
                    'unplanned_note' => $data['unplanned_note'] ?: null,
                ]);

                Notification::make()
                    ->title($successTitle)
                    ->success()
                    ->send();
            });
    }

    /**
     * Nur Admins dürfen Status ändern – Betrachter:innen sehen die Aktionen nicht.
     */
    protected function canManage(): bool
    {
        return Auth::guard('admin')->user()?->isAdmin() ?? false;
    }
}
