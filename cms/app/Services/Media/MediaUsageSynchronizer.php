<?php

namespace App\Services\Media;

use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Model;

class MediaUsageSynchronizer
{
    public function sync(Model $usable, string $field, string|int|null $mediaAssetId): void
    {
        MediaUsage::query()
            ->where('usable_type', $usable->getMorphClass())
            ->where('usable_id', (string) $usable->getKey())
            ->where('field', $field)
            ->delete();

        if ($mediaAssetId === null || $mediaAssetId === '') {
            return;
        }

        MediaUsage::query()->firstOrCreate([
            'media_asset_id' => $mediaAssetId,
            'usable_type' => $usable->getMorphClass(),
            'usable_id' => (string) $usable->getKey(),
            'field' => $field,
        ]);
    }

    public function forget(Model $usable, ?string $field = null): void
    {
        MediaUsage::query()
            ->where('usable_type', $usable->getMorphClass())
            ->where('usable_id', (string) $usable->getKey())
            ->when($field !== null, fn ($query) => $query->where('field', $field))
            ->delete();
    }
}
