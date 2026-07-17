<?php

namespace App\Console\Commands;

use App\Enums\ContentStatus;
use App\Models\CreationCategory;
use App\Models\Page;
use App\Models\Service;
use App\Services\Content\CatalogWorkflow;
use App\Services\Content\PageWorkflow;
use App\Services\Publishing\ReleasePublisher;
use Illuminate\Console\Command;

class PublishScheduledContent extends Command
{
    protected $signature = 'cms:publish-scheduled';

    protected $description = 'Publie les contenus arrivés à leur date de programmation et masque les contenus expirés';

    public function handle(PageWorkflow $pages, CatalogWorkflow $catalog, ReleasePublisher $publisher): int
    {
        $changed = 0;

        Page::query()->where('status', ContentStatus::Scheduled)->where('scheduled_at', '<=', now())
            ->each(function (Page $page) use ($pages, &$changed): void {
                $pages->transition($page, ContentStatus::Published, 'Publication programmée automatique');
                $changed++;
            });

        Page::query()->where('status', ContentStatus::Published)->whereNotNull('expires_at')->where('expires_at', '<=', now())
            ->each(function (Page $page) use ($pages, &$changed): void {
                $pages->transition($page, ContentStatus::Hidden, 'Expiration automatique');
                $changed++;
            });

        foreach ([Service::class, CreationCategory::class] as $model) {
            $model::query()->where('status', ContentStatus::Scheduled)->where('scheduled_at', '<=', now())
                ->each(function ($record) use ($catalog, &$changed): void {
                    $catalog->transition($record, ContentStatus::Published);
                    $changed++;
                });
            $model::query()->where('status', ContentStatus::Published)->whereNotNull('expires_at')->where('expires_at', '<=', now())
                ->each(function ($record) use ($catalog, &$changed): void {
                    $catalog->transition($record, ContentStatus::Hidden);
                    $changed++;
                });
        }

        if ($changed > 0) {
            $publisher->publish();
        }

        $this->info("{$changed} contenu(s) mis à jour.");

        return self::SUCCESS;
    }
}
