<?php

namespace App\Services\Publishing;

use App\Models\MediaAsset;
use App\Models\PublicationRelease;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ReleasePublisher
{
    public function __construct(
        private readonly PublicContentBuilder $builder,
        private readonly Filesystem $files,
        private readonly ContentPublicationValidator $validator,
    ) {}

    public function publish(?int $userId = null): PublicationRelease
    {
        $this->validator->ensureValid();

        $release = DB::transaction(function () use ($userId): PublicationRelease {
            $lastRelease = PublicationRelease::query()->orderByDesc('sequence')->lockForUpdate()->first();
            $sequence = ((int) $lastRelease?->sequence) + 1;

            return PublicationRelease::create([
                'sequence' => $sequence,
                'status' => 'building',
                'published_by' => $userId ?? auth()->id(),
                'metadata' => ['schema_version' => 1],
            ]);
        });

        try {
            $json = json_encode(
                $this->builder->build(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ).PHP_EOL;
            $checksum = hash('sha256', $json);
            $root = $this->releaseRoot();
            $directory = $root.'/'.str_pad((string) $release->sequence, 8, '0', STR_PAD_LEFT).'-'.substr($checksum, 0, 12);
            $temporary = $directory.'.tmp';

            $this->files->deleteDirectory($temporary);
            $this->files->makeDirectory($temporary.'/data', 0750, true);
            $this->files->makeDirectory($temporary.'/assets/images/cms', 0750, true);
            $this->files->put($temporary.'/data/content.json', $json);
            $this->copyManagedMedia($temporary.'/assets/images/cms');
            $this->files->put($temporary.'/manifest.json', json_encode([
                'sequence' => $release->sequence,
                'checksum' => $checksum,
                'created_at' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);

            if (! $this->files->moveDirectory($temporary, $directory)) {
                throw new RuntimeException('Impossible de finaliser le répertoire de release.');
            }

            $this->switchPublicRelease($directory, $release->sequence);
            $release->update([
                'status' => 'published',
                'manifest_path' => $directory.'/manifest.json',
                'checksum' => $checksum,
                'published_at' => now(),
                'metadata' => ['schema_version' => 1, 'directory' => $directory],
            ]);

            return $release->refresh();
        } catch (\Throwable $exception) {
            $release->update(['status' => 'failed', 'metadata' => ['error' => $exception->getMessage()]]);
            throw $exception;
        }
    }

    public function rollback(PublicationRelease $target, ?int $userId = null): PublicationRelease
    {
        $contentPath = ($target->metadata['directory'] ?? '').'/data/content.json';
        if ($target->status !== 'published' || ! is_file($contentPath)) {
            throw new RuntimeException('Cette release ne peut pas être restaurée.');
        }

        $sequence = ((int) PublicationRelease::query()->max('sequence')) + 1;
        $this->switchPublicRelease(dirname(dirname($contentPath)), $sequence);

        return PublicationRelease::create([
            'sequence' => $sequence,
            'status' => 'published',
            'manifest_path' => $target->manifest_path,
            'checksum' => $target->checksum,
            'metadata' => [...($target->metadata ?? []), 'rollback' => true],
            'published_by' => $userId ?? auth()->id(),
            'published_at' => now(),
            'rollback_of_id' => $target->id,
        ]);
    }

    private function releaseRoot(): string
    {
        $configured = (string) config('cms.public_release_path');
        $path = str_starts_with($configured, '/') ? $configured : base_path($configured);
        $this->files->ensureDirectoryExists($path, 0750, true);

        return realpath($path) ?: $path;
    }

    private function copyManagedMedia(string $targetDirectory): void
    {
        MediaAsset::withTrashed()->with('variants')->each(function (MediaAsset $media) use ($targetDirectory): void {
            if ($media->variants->isNotEmpty()) {
                foreach ($media->variants as $variant) {
                    $source = Storage::disk($variant->disk)->path($variant->path);
                    if (! is_file($source)) {
                        throw new RuntimeException("Variante média introuvable : {$media->original_name} ({$variant->width}px)");
                    }

                    $directory = $targetDirectory.'/'.$media->sha256;
                    $this->files->ensureDirectoryExists($directory, 0750, true);
                    if (! $this->files->copy($source, $directory.'/'.$variant->width.'.'.$variant->format)) {
                        throw new RuntimeException("Impossible de publier la variante : {$media->original_name}");
                    }
                }

                return;
            }

            if ($media->source_path === null) {
                $source = Storage::disk($media->disk)->path($media->path);
                if (! is_file($source)) {
                    throw new RuntimeException("Média source introuvable : {$media->original_name}");
                }
                if (! $this->files->copy($source, $targetDirectory.'/'.$media->sha256.'.'.$media->extension)) {
                    throw new RuntimeException("Impossible de publier le média : {$media->original_name}");
                }
            }
        });
    }

    private function switchPublicRelease(string $directory, int $sequence): void
    {
        $contentLink = config('cms.public_content_link');
        if (! $contentLink) {
            return;
        }

        $mediaLink = config('cms.public_media_link')
            ?: dirname(dirname($contentLink)).'/assets/images/cms';
        $mediaTarget = $directory.'/assets/images/cms';
        $contentTemporaryLink = $this->prepareLink($directory.'/data/content.json', $contentLink, $sequence);
        $mediaTemporaryLink = null;

        try {
            if (is_dir($mediaTarget)) {
                $mediaTemporaryLink = $this->prepareLink($mediaTarget, $mediaLink, $sequence);
            }
            if ($mediaTemporaryLink !== null && ! rename($mediaTemporaryLink, $mediaLink)) {
                throw new RuntimeException("La bascule atomique de {$mediaLink} a échoué.");
            }
            if (! rename($contentTemporaryLink, $contentLink)) {
                throw new RuntimeException("La bascule atomique de {$contentLink} a échoué.");
            }
        } finally {
            @unlink($contentTemporaryLink);
            if ($mediaTemporaryLink !== null) {
                @unlink($mediaTemporaryLink);
            }
        }
    }

    private function prepareLink(string $target, string $link, int $sequence): string
    {
        $this->files->ensureDirectoryExists(dirname($link));
        $temporaryLink = dirname($link).'/.'.basename($link).'.next-'.$sequence;
        @unlink($temporaryLink);

        if (! symlink($target, $temporaryLink)) {
            throw new RuntimeException("La préparation du lien {$link} a échoué.");
        }

        return $temporaryLink;
    }
}
