<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Übersicht aller Mitarbeitenden, die noch KEINEN Termin gewählt haben
 * (keine bestätigte oder abgeschlossene Buchung). Pro Person gibt es einen
 * „Erinnern"-Button, der eine vorbereitete Erinnerungs-E-Mail im Mailprogramm
 * der/des Admins öffnet – der Server selbst versendet keine Mails.
 */
class Erinnerungen extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.erinnerungen';

    protected static ?string $title = 'Erinnerungen';

    protected static ?string $navigationLabel = 'Erinnerungen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 2;

    /** Navigations-Badge: Anzahl der Personen ohne Termin. */
    public static function getNavigationBadge(): ?string
    {
        $count = Employee::query()->ohneTermin()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Employee::query()->ohneTermin())
            ->defaultSort('nachname')
            ->emptyStateHeading('Alle Mitarbeitenden haben einen Termin')
            ->emptyStateDescription('Es gibt aktuell niemanden, der noch erinnert werden müsste.')
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->columns([
                TextColumn::make('kvgg_nummer')
                    ->label('KVGG-Nr.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nachname')
                    ->label('Name')
                    ->formatStateUsing(fn ($state, Employee $record): string => trim($record->vorname.' '.$record->nachname))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('abteilung')
                    ->label('Abteilung')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->placeholder('—')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('abteilung')
                    ->label('Abteilung')
                    ->options(fn (): array => Employee::query()
                        ->ohneTermin()
                        ->whereNotNull('abteilung')
                        ->distinct()
                        ->orderBy('abteilung')
                        ->pluck('abteilung', 'abteilung')
                        ->all()),
            ])
            ->recordActions([
                Action::make('remind')
                    ->label('Erinnern')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->color('warning')
                    ->visible(fn (Employee $record): bool => filled($record->email))
                    ->url(fn (Employee $record): string => self::reminderMailto($record)),
            ]);
    }

    /**
     * Baut einen mailto:-Link mit vorbefülltem Betreff und deutschem
     * Erinnerungstext. Der Versand erfolgt über das Mailprogramm der/des
     * Admins – der Server hat keinen Mailzugang.
     */
    public static function reminderMailto(Employee $employee): string
    {
        $subject = 'Erinnerung: Bitte Termin für den Laptop-Austausch auswählen';

        $body = "Guten Tag {$employee->vorname} {$employee->nachname},\r\n\r\n"
            .'Sie haben noch keinen Termin für den Austausch Ihres Laptops ausgewählt. '
            ."Bitte wählen Sie zeitnah einen freien Termin im Buchungstool aus.\r\n\r\n"
            ."Vielen Dank und freundliche Grüße\r\n"
            .'Ihre IT-Abteilung – Kreis Groß-Gerau';

        return 'mailto:'.$employee->email
            .'?subject='.rawurlencode($subject)
            .'&body='.rawurlencode($body);
    }
}
