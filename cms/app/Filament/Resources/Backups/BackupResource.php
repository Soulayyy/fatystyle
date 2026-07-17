<?php

namespace App\Filament\Resources\Backups;

use App\Filament\Resources\Backups\Pages\ListBackups;
use App\Filament\Resources\Concerns\AuthorizesCmsResource;
use App\Models\Backup;
use App\Services\Operations\BackupService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BackupResource extends Resource
{
    use AuthorizesCmsResource;

    protected const PERMISSION_MODULE = 'backups';

    protected static ?string $model = Backup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Sauvegardes';

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('created_at')->label('Créée le')->dateTime('d/m/Y H:i:s'),
            TextColumn::make('type')->label('Type')->badge(),
            TextColumn::make('status')->label('Statut')->badge(),
            TextColumn::make('size_bytes')->label('Taille')->formatStateUsing(fn ($state): string => $state ? number_format($state / 1048576, 1, ',', ' ').' Mo' : '—'),
            TextColumn::make('sha256')->label('Empreinte')->limit(14)->copyable(),
        ])->recordActions([
            Action::make('download')->label('Télécharger')->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn (Backup $record): bool => $record->status === 'completed')
                ->url(fn (Backup $record): string => route('admin.backups.download', $record)),
            Action::make('restore')->label('Restaurer')->color('danger')->requiresConfirmation()
                ->modalDescription('Cette opération remplace la base actuelle. Une sauvegarde automatique sera créée avant restauration.')
                ->visible(fn (Backup $record): bool => $record->status === 'completed' && (auth()->user()?->can('backups.restore') ?? false))
                ->action(function (Backup $record): void {
                    $service = app(BackupService::class);
                    $service->create('full');
                    $service->restore($record);
                    Notification::make()->title('Restauration terminée')->success()->send();
                }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBackups::route('/')];
    }
}
