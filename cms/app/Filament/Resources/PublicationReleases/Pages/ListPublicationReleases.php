<?php

namespace App\Filament\Resources\PublicationReleases\Pages;

use App\Filament\Resources\PublicationReleases\PublicationReleaseResource;
use App\Services\Publishing\ReleasePublisher;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListPublicationReleases extends ListRecords
{
    protected static string $resource = PublicationReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')->label('Publier le contenu')->icon(Heroicon::OutlinedRocketLaunch)
                ->visible(fn (): bool => auth()->user()?->can('releases.publish') ?? false)
                ->requiresConfirmation()
                ->modalDescription('Une release complète sera générée puis basculée atomiquement vers le site public.')
                ->action(function (): void {
                    app(ReleasePublisher::class)->publish();
                    Notification::make()->title('Publication terminée')->success()->send();
                }),
        ];
    }
}
