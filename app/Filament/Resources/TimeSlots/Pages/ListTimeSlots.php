<?php

namespace App\Filament\Resources\TimeSlots\Pages;

use App\Filament\Resources\TimeSlots\TimeSlotResource;
use App\Services\SlotGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListTimeSlots extends ListRecords
{
    protected static string $resource = TimeSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Slots generieren')
                ->icon('heroicon-o-calendar-days')
                // Nur Admins dürfen Slots erzeugen, Betrachter nicht.
                ->visible(fn (): bool => Auth::guard('admin')->user()?->isAdmin() ?? false)
                ->modalHeading('Zeitfenster generieren')
                ->modalDescription('Erzeugt 8 Slots pro Werktag (08–16 Uhr) im gewählten Zeitraum. Wochenenden werden übersprungen, bereits vorhandene Slots bleiben unverändert.')
                ->modalSubmitActionLabel('Generieren')
                ->schema([
                    DatePicker::make('start')
                        ->label('Von')
                        ->required()
                        ->default('2026-08-10')
                        ->displayFormat('d.m.Y'),
                    DatePicker::make('end')
                        ->label('Bis')
                        ->required()
                        ->default('2026-09-11')
                        ->displayFormat('d.m.Y')
                        ->afterOrEqual('start'),
                ])
                ->action(function (array $data): void {
                    $result = app(SlotGenerator::class)->generate(
                        $data['start'],
                        $data['end'],
                        Auth::guard('admin')->id(),
                    );

                    Notification::make()
                        ->success()
                        ->title('Slots generiert')
                        ->body("{$result['created']} neue Slots erstellt, {$result['existing']} waren bereits vorhanden ({$result['days']} Werktage).")
                        ->send();
                }),
        ];
    }
}
