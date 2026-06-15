<?php

namespace App\Filament\Resources\SoftwareCatalogs\Tables;

use App\Models\SoftwareCatalog;
use App\Services\SoftwareCatalogMerger;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SoftwareCatalogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === SoftwareCatalog::STATUS_PENDING ? 'Wartet auf Freigabe' : 'Freigegeben')
                    ->color(fn (string $state): string => $state === SoftwareCatalog::STATUS_PENDING ? 'warning' : 'success')
                    ->sortable(),
                TextColumn::make('submittedBy.full_name')
                    ->label('Eingereicht von')
                    ->state(fn (SoftwareCatalog $record): string => $record->submittedBy
                        ? trim($record->submittedBy->vorname.' '.$record->submittedBy->nachname)
                        : '—')
                    ->toggleable(),
                TextColumn::make('version')
                    ->label('Version')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('publisher')
                    ->label('Hersteller')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_standard')
                    ->label('Standard')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('booking_software_count')
                    ->label('Verwendet')
                    ->counts('bookingSoftware')
                    ->badge()
                    ->color('gray')
                    ->suffix(' ×'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        SoftwareCatalog::STATUS_PENDING => 'Wartet auf Freigabe',
                        SoftwareCatalog::STATUS_APPROVED => 'Freigegeben',
                    ]),
                TernaryFilter::make('is_standard')
                    ->label('Standardsoftware')
                    ->placeholder('Alle')
                    ->trueLabel('Nur Standardsoftware')
                    ->falseLabel('Nur Zusatzsoftware'),
            ])
            ->recordActions([
                // Alle Zeilen-Aktionen in ein Aufklapp-Menü (⋮), damit die
                // Tabelle nicht zu breit wird und nichts seitlich wegscrollt.
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Freigeben')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->visible(fn (SoftwareCatalog $record): bool => $record->isPending() && self::userIsAdmin())
                        ->requiresConfirmation()
                        ->modalHeading('Software freigeben')
                        ->modalDescription('Nach der Freigabe ist die Software für alle Mitarbeitenden auswählbar.')
                        ->action(function (SoftwareCatalog $record): void {
                            $record->update(['status' => SoftwareCatalog::STATUS_APPROVED]);

                            Notification::make()
                                ->title('Software freigegeben.')
                                ->success()
                                ->send();
                        }),
                    Action::make('merge')
                        ->label('Zusammenführen')
                        ->icon(Heroicon::OutlinedArrowsPointingIn)
                        ->color('warning')
                        ->visible(fn (): bool => self::userIsAdmin())
                        ->modalHeading('Einträge zusammenführen')
                        ->modalSubmitActionLabel('Zusammenführen')
                        ->schema([
                            Select::make('target_id')
                                ->label('Beibehalten (Ziel-Eintrag)')
                                ->helperText('Der aktuelle Eintrag wird gelöscht; alle zugehörigen Buchungen werden auf den gewählten Ziel-Eintrag umgehängt.')
                                ->required()
                                ->searchable()
                                ->options(fn (SoftwareCatalog $record): array => SoftwareCatalog::query()
                                    ->whereKeyNot($record->getKey())
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()),
                        ])
                        ->action(function (SoftwareCatalog $record, array $data): void {
                            $winner = SoftwareCatalog::findOrFail($data['target_id']);
                            app(SoftwareCatalogMerger::class)->merge($record, $winner);

                            Notification::make()
                                ->title('Einträge zusammengeführt.')
                                ->body($record->name.' wurde in '.$winner->name.' überführt.')
                                ->success()
                                ->send();
                        }),
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->label('Löschen')
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Software-Einträge löschen')
                        ->modalDescription('Die ausgewählten Katalog-Einträge werden gelöscht. Bei Buchungen, die diese Software angefordert hatten, wird die Verknüpfung entfernt.')
                        ->modalSubmitActionLabel('Endgültig löschen')
                        ->visible(fn (): bool => self::userIsAdmin())
                        ->action(function (Collection $records): void {
                            $count = $records->count();
                            $records->each(fn (SoftwareCatalog $software) => $software->delete());

                            Notification::make()
                                ->success()
                                ->title($count === 1 ? '1 Eintrag gelöscht' : $count.' Einträge gelöscht')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    private static function userIsAdmin(): bool
    {
        return Auth::guard('admin')->user()?->isAdmin() ?? false;
    }
}
