<?php

namespace App\Filament\Resources\Backups\Pages;

use App\Filament\Resources\Backups\BackupResource;
use App\Services\Operations\BackupService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListBackups extends ListRecords
{
    protected static string $resource = BackupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('database')->label('Sauvegarder la base')->icon(Heroicon::OutlinedCircleStack)
                ->visible(fn (): bool => auth()->user()?->can('backups.manage') ?? false)
                ->action(function (): void {
                    app(BackupService::class)->create('database');
                    Notification::make()->title('Sauvegarde créée')->success()->send();
                }),
            Action::make('full')->label('Sauvegarde complète')->icon(Heroicon::OutlinedArchiveBox)
                ->visible(fn (): bool => auth()->user()?->can('backups.manage') ?? false)
                ->action(function (): void {
                    app(BackupService::class)->create('full');
                    Notification::make()->title('Sauvegarde complète créée')->success()->send();
                }),
            Action::make('export')->label('Exporter le contenu JSON')->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn (): bool => auth()->user()?->can('exports.export') ?? false)
                ->url(route('admin.content.export')),
        ];
    }
}
