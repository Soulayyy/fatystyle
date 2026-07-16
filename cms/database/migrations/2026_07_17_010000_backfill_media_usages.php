<?php

use App\Models\CreationCategory;
use App\Models\PageTranslation;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [];

        Service::withTrashed()->whereNotNull('image_id')->each(function (Service $service) use (&$rows, $now): void {
            $rows[] = $this->usage($service->image_id, $service->getMorphClass(), $service->id, 'image', $now);
        });

        CreationCategory::withTrashed()->whereNotNull('cover_id')->each(function (CreationCategory $category) use (&$rows, $now): void {
            $rows[] = $this->usage($category->cover_id, $category->getMorphClass(), $category->id, 'cover', $now);
        });

        PageTranslation::query()->whereNotNull('og_image_id')->each(function (PageTranslation $translation) use (&$rows, $now): void {
            $rows[] = $this->usage($translation->og_image_id, $translation->getMorphClass(), $translation->id, 'og_image', $now);
        });

        DB::table('creation_category_media')->orderBy('id')->each(function (object $item) use (&$rows, $now): void {
            $rows[] = $this->usage(
                $item->media_asset_id,
                (new CreationCategory)->getMorphClass(),
                $item->creation_category_id,
                'gallery:'.$item->media_asset_id,
                $now,
            );
        });

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('media_usages')->insertOrIgnore($chunk);
        }
    }

    public function down(): void
    {
        DB::table('media_usages')->whereIn('field', ['image', 'cover', 'og_image'])->delete();
        DB::table('media_usages')->where('field', 'like', 'gallery:%')->delete();
    }

    private function usage(string $mediaId, string $type, string|int $usableId, string $field, mixed $now): array
    {
        return [
            'media_asset_id' => $mediaId,
            'usable_type' => $type,
            'usable_id' => (string) $usableId,
            'field' => $field,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
};
