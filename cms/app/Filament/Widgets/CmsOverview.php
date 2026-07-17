<?php

namespace App\Filament\Widgets;

use App\Enums\ContactStatus;
use App\Enums\ContentStatus;
use App\Models\Backup;
use App\Models\ContactRequest;
use App\Models\CreationCategory;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\PublicationRelease;
use App\Models\Service;
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
                ->description(MediaAsset::where('is_decorative', false)->where(fn ($query) => $query->whereNull('alt_text')->orWhere('alt_text', ''))->count().' texte(s) alternatif(s) à compléter')
                ->color(MediaAsset::where('is_decorative', false)->where(fn ($query) => $query->whereNull('alt_text')->orWhere('alt_text', ''))->exists() ? 'warning' : 'success'),
            Stat::make('Dernière publication', PublicationRelease::where('status', 'published')->latest('published_at')->first()?->published_at?->diffForHumans() ?? 'Aucune')
                ->description('Release publique active'),
            Stat::make('Contenus programmés', Page::where('status', ContentStatus::Scheduled)->count()
                + Service::where('status', ContentStatus::Scheduled)->count()
                + CreationCategory::where('status', ContentStatus::Scheduled)->count())
                ->description('Publications à venir'),
            Stat::make('Sauvegardes', Backup::where('status', 'completed')->count())
                ->description(Backup::where('status', 'failed')->count().' échec(s) à contrôler')
                ->color(Backup::where('status', 'failed')->exists() ? 'danger' : 'success'),
            Stat::make('Demandes en retard', ContactRequest::where('status', ContactStatus::New)->where('received_at', '<', now()->subHours(48))->count())
                ->description('Nouvelles demandes reçues depuis plus de 48 h')
                ->color(ContactRequest::where('status', ContactStatus::New)->where('received_at', '<', now()->subHours(48))->exists() ? 'danger' : 'success'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('dashboard.view') ?? false;
    }
}
