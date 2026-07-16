<?php

namespace App\Services\Media;

use App\Models\MediaAsset;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MediaIngestor
{
    public function importLegacyFile(string $absolutePath, string $sourcePath, ?int $userId = null): MediaAsset
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new RuntimeException("Média source introuvable : {$sourcePath}");
        }

        $hash = hash_file('sha256', $absolutePath);
        $existing = MediaAsset::withTrashed()->where('sha256', $hash)->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            return $existing;
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $storagePath = 'media/originals/'.substr($hash, 0, 2).'/'.$hash.($extension ? ".{$extension}" : '');
        $disk = $this->disk();

        if (! $disk->exists($storagePath)) {
            $stream = fopen($absolutePath, 'rb');
            if ($stream === false || ! $disk->put($storagePath, $stream)) {
                throw new RuntimeException("Impossible de stocker le média : {$sourcePath}");
            }
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        [$width, $height] = $this->imageDimensions($absolutePath);

        return MediaAsset::create([
            'disk' => 'local',
            'path' => $storagePath,
            'original_name' => basename($absolutePath),
            'mime_type' => mime_content_type($absolutePath) ?: 'application/octet-stream',
            'extension' => $extension ?: null,
            'size_bytes' => filesize($absolutePath),
            'width' => $width,
            'height' => $height,
            'sha256' => $hash,
            'source_path' => $sourcePath,
            'uploaded_by' => $userId,
            'metadata' => ['imported_from_public_site' => true],
        ]);
    }

    public function ingestUpload(UploadedFile $file, ?string $altText = null, ?int $userId = null): MediaAsset
    {
        $media = $this->importLegacyFile($file->getRealPath(), $file->getClientOriginalName(), $userId);
        $media->fill(['alt_text' => $altText, 'original_name' => $file->getClientOriginalName()])->save();

        return $media;
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk('local');
    }

    /** @return array{0: int|null, 1: int|null} */
    private function imageDimensions(string $path): array
    {
        $dimensions = @getimagesize($path);

        return $dimensions === false ? [null, null] : [$dimensions[0], $dimensions[1]];
    }
}
