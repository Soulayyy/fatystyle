<?php

namespace App\Filament\Widgets;

use App\Enums\ContactStatus;
use App\Enums\ContentStatus;
use App\Models\ContactRequest;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\PublicationRelease;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CmsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Pages publiées', Page::where('status', ContentStatus::Published)->count())
                ->description(Page::where('status', ContentStatus::InReview)->count().' en attente de validation'),
            Stat::make('Nouvelles demandes', ContactRequest::where('status', ContactStatus::New)->count())
                ->description('Demandes de contact à traiter'),
            Stat::make('Médias', MediaAsset::count())
                ->description('Originaux disponibles'),
            Stat::make('Dernière publication', PublicationRelease::where('status', 'published')->latest('published_at')->first()?->published_at?->diffForHumans() ?? 'Aucune')
                ->description('Release publique active'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.view') ?? false;
    }
}
