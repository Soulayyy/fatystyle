<?php

namespace App\Services\Media;

use App\Models\CreationCategory;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\PageTranslation;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class MediaReplacementService
{
    public function __construct(private readonly MediaIngestor $ingestor) {}

    public function replace(MediaAsset $original, string $temporaryPath, string $originalName, ?int $userId = null): MediaAsset
    {
        $replacement = $this->ingestor->ingestStoredUpload(
            $temporaryPath,
            $originalName,
            $userId,
            [
                'title' => $original->title,
                'alt_text' => $original->alt_text,
                'is_decorative' => $original->is_decorative,
                'caption' => $original->caption,
                'credit' => $original->credit,
                'rights' => $original->rights,
                'tags' => $original->tags,
                'focal_x' => $original->focal_x,
                'focal_y' => $original->focal_y,
            ],
        );

        if ($replacement->is($original)) {
            return $replacement;
        }

        DB::transaction(function () use ($original, $replacement): void {
            Service::withTrashed()->where('image_id', $original->id)->update(['image_id' => $replacement->id]);
            CreationCategory::withTrashed()->where('cover_id', $original->id)->update(['cover_id' => $replacement->id]);
            PageTranslation::query()->where('og_image_id', $original->id)->update(['og_image_id' => $replacement->id]);

            DB::table('creation_category_media')->where('media_asset_id', $original->id)->get()->each(
                function ($usage) use ($original, $replacement): void {
                    DB::table('creation_category_media')->updateOrInsert(
                        ['creation_category_id' => $usage->creation_category_id, 'media_asset_id' => $replacement->id],
                        ['position' => $usage->position, 'alt_text' => $usage->alt_text, 'updated_at' => now(), 'created_at' => $usage->created_at],
                    );
                    DB::table('creation_category_media')->where('id', $usage->id)->where('media_asset_id', $original->id)->delete();
                },
            );

            DB::table('media_usages')->where('media_asset_id', $original->id)->delete();
            Service::withTrashed()->where('image_id', $replacement->id)->each(fn (Service $service) => $this->usage($replacement, $service, 'image'));
            CreationCategory::withTrashed()->where('cover_id', $replacement->id)->each(fn (CreationCategory $category) => $this->usage($replacement, $category, 'cover'));
            PageTranslation::query()->where('og_image_id', $replacement->id)->each(fn (PageTranslation $translation) => $this->usage($replacement, $translation, 'og_image'));
            DB::table('creation_category_media')->where('media_asset_id', $replacement->id)->get()->each(function ($item) use ($replacement): void {
                $category = CreationCategory::withTrashed()->find($item->creation_category_id);
                if ($category) {
                    $this->usage($replacement, $category, 'gallery:'.$replacement->id);
                }
            });
            $original->delete();
        });

        return $replacement->refresh();
    }

    private function usage(MediaAsset $media, $model, string $field): void
    {
        MediaUsage::query()->firstOrCreate([
            'media_asset_id' => $media->id,
            'usable_type' => $model->getMorphClass(),
            'usable_id' => (string) $model->getKey(),
            'field' => $field,
        ]);
    }
}
