<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\TimeSlot;
use App\Services\BookingManager;
use App\Services\SlotGenerator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Startseite der Verwaltung: Wochenkalender aller Zeitfenster. Freie Slots sind
 * grün und (für Admins) per Klick buchbar, belegte Slots zeigen den Namen und
 * verlinken auf die Buchungsdetails. Ersetzt das frühere Auslastungs-Diagramm
 * und die separate Zeitfenster-Übersicht.
 */
class Kalender extends Page
{
    protected string $view = 'filament.pages.kalender';

    protected static ?string $title = 'Kalender';

    protected static ?string $navigationLabel = 'Kalender';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 0;

    /** Aktuell angezeigte Kalenderwoche. */
    public ?int $kw = null;

    // Macht den Kalender zur Startseite des Panels (Pfad /admin statt /admin/kalender).
    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public function mount(): void
    {
        $this->kw ??= TimeSlot::query()->min('calendar_week');
    }

    /** Nur Admins dürfen buchen/generieren – Betrachter sehen den Kalender nur an. */
    protected function canManage(): bool
    {
        return Auth::guard('admin')->user()?->isAdmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateSlots')
                ->label('Slots generieren')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->visible(fn (): bool => $this->canManage())
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

    /**
     * Buchen eines freien Zeitfensters direkt aus dem Kalender heraus. Das
     * Zeitfenster kommt als Argument vom angeklickten Feld; im Formular wird nur
     * noch die Person ausgewählt.
     */
    public function createBookingAction(): Action
    {
        return Action::make('createBooking')
            ->visible(fn (): bool => $this->canManage())
            ->modalHeading('Termin manuell buchen')
            ->modalDescription(function (array $arguments): ?string {
                $slot = TimeSlot::find($arguments['timeSlotId'] ?? null);

                return $slot
                    ? Carbon::parse($slot->slot_date)->translatedFormat('l, d.m.Y').' um '.substr($slot->start_time, 0, 5).' Uhr'
                    : null;
            })
            ->schema([
                Select::make('employee_id')
                    ->label('Mitarbeiter:in')
                    ->options(fn (): array => BookingResource::employeeOptions())
                    ->searchable()
                    ->required(),
            ])
            ->modalSubmitActionLabel('Buchen')
            ->action(function (array $data, array $arguments): void {
                try {
                    app(BookingManager::class)->create(
                        (int) $data['employee_id'],
                        (int) $arguments['timeSlotId'],
                    );
                } catch (RuntimeException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Buchung wurde angelegt.')->success()->send();
            });
    }

    protected function getViewData(): array
    {
        $weeks = TimeSlot::query()->distinct()->orderBy('calendar_week')->pluck('calendar_week');

        $kw = $this->kw ?? $weeks->first();

        $slots = TimeSlot::query()
            ->where('calendar_week', $kw)
            ->with(['bookings' => fn ($q) => $q->where('status', '!=', 'cancelled')->with('employee')])
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        // grid[YYYY-MM-DD][HH:MM] = TimeSlot
        $grid = [];
        foreach ($slots as $slot) {
            $grid[$slot->slot_date->format('Y-m-d')][substr($slot->start_time, 0, 5)] = $slot;
        }

        return [
            'weeks' => $weeks,
            'currentKw' => $kw,
            'days' => $slots->pluck('slot_date')->unique(fn (Carbon $d): string => $d->format('Y-m-d'))->sort()->values(),
            'times' => $slots->map(fn (TimeSlot $s): string => substr($s->start_time, 0, 5))->unique()->sort()->values(),
            'grid' => $grid,
            'canManage' => $this->canManage(),
        ];
    }
}
