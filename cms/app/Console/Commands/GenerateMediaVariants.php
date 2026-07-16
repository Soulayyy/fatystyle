<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use App\Services\Media\MediaVariantGenerator;
use Illuminate\Console\Command;
use Throwable;

class GenerateMediaVariants extends Command
{
    protected $signature = 'cms:generate-media-variants
        {--force : Supprimer et régénérer toutes les variantes}
        {--limit= : Limiter le nombre de médias traités}';

    protected $description = 'Génère les variantes WebP responsives manquantes de la médiathèque';

    public function handle(MediaVariantGenerator $generator): int
    {
        $query = MediaAsset::query()->orderBy('created_at');
        if (! $this->option('force')) {
            $query->whereDoesntHave('variants');
        }
        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $media = $query->get();
        $bar = $this->output->createProgressBar($media->count());
        $failures = [];

        foreach ($media as $asset) {
            try {
                $generator->generate($asset, (bool) $this->option('force'));
            } catch (Throwable $exception) {
                report($exception);
                $failures[] = $asset->original_name.': '.$exception->getMessage();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(($media->count() - count($failures)).' média(s) traité(s) avec succès.');

        foreach ($failures as $failure) {
            $this->error($failure);
        }

        return $failures === [] ? self::SUCCESS : self::FAILURE;
    }
}
