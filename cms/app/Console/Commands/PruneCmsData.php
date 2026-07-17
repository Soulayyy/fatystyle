<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Models\MediaAsset;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneCmsData extends Command
{
    protected $signature = 'cms:prune {--dry-run}';

    protected $description = 'Applique les durées de conservation des versions, corbeilles et sauvegardes';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = 0;

        Page::query()->with('versions')->each(function (Page $page) use ($dryRun, &$count): void {
            $deletable = $page->versions
                ->sortByDesc('version')
                ->skip((int) config('cms.version_retention_count'))
                ->filter(fn ($version): bool => $version->created_at->lt(now()->subMonths((int) config('cms.version_retention_months'))));
            $count += $deletable->count();
            if (! $dryRun) {
                $deletable->each->delete();
            }
        });

        MediaAsset::onlyTrashed()->where('deleted_at', '<', now()->subDays((int) config('cms.trash_retention_days')))
            ->each(function (MediaAsset $media) use ($dryRun, &$count): void {
                if ($media->isInUse()) {
                    return;
                }
                $count++;
                if (! $dryRun) {
                    Storage::disk($media->disk)->delete($media->path);
                    $media->variants()->each(fn ($variant) => Storage::disk($variant->disk)->delete($variant->path));
                    $media->forceDelete();
                }
            });

        Backup::query()->where('created_at', '<', now()->subDays((int) config('cms.backup_retention_days')))
            ->each(function (Backup $backup) use ($dryRun, &$count): void {
                $count++;
                if (! $dryRun) {
                    if ($backup->path && is_file($backup->path)) {
                        unlink($backup->path);
                    }
                    $backup->delete();
                }
            });

        $this->info("{$count} élément(s) concerné(s).");

        return self::SUCCESS;
    }
}
