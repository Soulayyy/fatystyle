<?php

namespace App\Models;

use App\Services\Media\MediaUsageSynchronizer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CreationCategoryMedia extends Pivot
{
    protected $table = 'creation_category_media';

    public $incrementing = true;

    protected $fillable = ['creation_category_id', 'media_asset_id', 'position', 'alt_text'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CreationCategory::class, 'creation_category_id');
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    protected static function booted(): void
    {
        static::saved(function (CreationCategoryMedia $item): void {
            app(MediaUsageSynchronizer::class)->sync(
                CreationCategory::query()->findOrFail($item->creation_category_id),
                'gallery:'.$item->media_asset_id,
                $item->media_asset_id,
            );
        });

        static::deleted(function (CreationCategoryMedia $item): void {
            $category = CreationCategory::withTrashed()->find($item->creation_category_id);

            if ($category !== null) {
                app(MediaUsageSynchronizer::class)->forget($category, 'gallery:'.$item->media_asset_id);
            }
        });
    }
}
