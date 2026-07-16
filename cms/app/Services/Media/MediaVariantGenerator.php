<?php

namespace App\Services\Media;

use App\Models\MediaAsset;
use App\Models\MediaVariant;
use GdImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MediaVariantGenerator
{
    /** @return Collection<int, MediaVariant> */
    public function generate(MediaAsset $media, bool $force = false): Collection
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            throw new RuntimeException('L’extension GD avec prise en charge WebP est indispensable.');
        }

        $disk = Storage::disk($media->disk);
        $sourcePath = $disk->path($media->path);
        if (! is_file($sourcePath)) {
            throw new RuntimeException("Original introuvable : {$media->original_name}");
        }

        $source = $this->load($sourcePath, $media->mime_type);
        $source = $this->applyExifOrientation($source, $sourcePath, $media->mime_type);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $maxPixels = (int) config('cms.media.max_pixels', 60000000);
        if (($sourceWidth * $sourceHeight) > $maxPixels) {
            imagedestroy($source);
            throw new RuntimeException("L’image {$media->original_name} dépasse la limite de pixels autorisée.");
        }

        if ($force) {
            foreach ($media->variants as $variant) {
                Storage::disk($variant->disk)->delete($variant->path);
            }
            $media->variants()->delete();
        }

        $configuredWidths = array_map('intval', config('cms.media.variant_widths', [320, 640, 960, 1280, 1920]));
        $widths = collect($configuredWidths)
            ->filter(fn (int $width): bool => $width > 0 && $width <= $sourceWidth)
            ->push($sourceWidth)
            ->unique()
            ->sort()
            ->values();
        $quality = max(1, min(100, (int) config('cms.media.webp_quality', 82)));
        $generatedPaths = [];
        $generatedIds = [];

        try {
            foreach ($widths as $width) {
                if ($media->variants()->where('width', $width)->where('format', 'webp')->exists()) {
                    continue;
                }

                $height = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));
                $target = imagecreatetruecolor($width, $height);
                if (! $target instanceof GdImage) {
                    throw new RuntimeException('Impossible de préparer une variante de l’image.');
                }

                imagealphablending($target, false);
                imagesavealpha($target, true);
                $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
                imagefilledrectangle($target, 0, 0, $width, $height, $transparent);
                imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

                $variantPath = 'media/variants/'.substr($media->sha256, 0, 2).'/'.$media->sha256.'/'.$width.'.webp';
                $disk->makeDirectory(dirname($variantPath));
                $absoluteVariantPath = $disk->path($variantPath);
                if (! imagewebp($target, $absoluteVariantPath, $quality)) {
                    imagedestroy($target);
                    throw new RuntimeException("Impossible de générer la variante {$width}px.");
                }
                imagedestroy($target);

                $size = filesize($absoluteVariantPath);
                if ($size === false) {
                    throw new RuntimeException("Impossible de mesurer la variante {$width}px.");
                }

                $variant = $media->variants()->create([
                    'disk' => $media->disk,
                    'path' => $variantPath,
                    'mime_type' => 'image/webp',
                    'format' => 'webp',
                    'width' => $width,
                    'height' => $height,
                    'size_bytes' => $size,
                    'quality' => $quality,
                ]);
                $generatedPaths[] = $variantPath;
                $generatedIds[] = $variant->id;
            }
        } catch (\Throwable $exception) {
            $disk->delete($generatedPaths);
            MediaVariant::query()->whereIn('id', $generatedIds)->delete();
            throw $exception;
        } finally {
            imagedestroy($source);
        }

        return $media->variants()->get();
    }

    private function load(string $path, string $mime): GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            'image/avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($path) : false,
            'image/gif' => @imagecreatefromgif($path),
            default => false,
        };

        if (! $image instanceof GdImage) {
            throw new RuntimeException('Le fichier image est corrompu ou son codec n’est pas disponible.');
        }

        return $image;
    }

    private function applyExifOrientation(GdImage $image, string $path, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) ((@exif_read_data($path)['Orientation'] ?? 1));
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            5, 6 => imagerotate($image, -90, 0),
            7, 8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated !== $image && $rotated instanceof GdImage) {
            imagedestroy($image);
            $image = $rotated;
        }

        if (in_array($orientation, [2, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        } elseif ($orientation === 4) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }

        return $image;
    }
}
