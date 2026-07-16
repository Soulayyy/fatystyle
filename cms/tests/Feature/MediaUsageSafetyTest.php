<?php

namespace Tests\Feature;

use App\Models\CreationCategory;
use App\Models\MediaAsset;
use App\Models\MediaUsage;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaUsageSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_usages_follow_content_references(): void
    {
        $cover = $this->media('cover.jpg');
        $galleryImage = $this->media('gallery.jpg');

        $service = Service::query()->create([
            'slug' => 'patronage',
            'title' => 'Patronage',
            'image_id' => $cover->id,
        ]);

        $category = CreationCategory::query()->create([
            'slug' => 'sur-mesure',
            'title' => 'Sur mesure',
            'cover_id' => $cover->id,
        ]);
        $category->media()->attach($galleryImage->id, ['position' => 0]);

        $page = Page::query()->create();
        $translation = PageTranslation::query()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'slug' => 'accueil',
            'title' => 'Accueil',
            'h1' => 'Accueil',
            'og_image_id' => $cover->id,
        ]);

        $this->assertDatabaseHas('media_usages', [
            'media_asset_id' => $cover->id,
            'usable_type' => $service->getMorphClass(),
            'usable_id' => $service->id,
            'field' => 'image',
        ]);
        $this->assertDatabaseHas('media_usages', [
            'media_asset_id' => $cover->id,
            'usable_type' => $category->getMorphClass(),
            'usable_id' => $category->id,
            'field' => 'cover',
        ]);
        $this->assertDatabaseHas('media_usages', [
            'media_asset_id' => $galleryImage->id,
            'usable_type' => $category->getMorphClass(),
            'usable_id' => $category->id,
            'field' => 'gallery:'.$galleryImage->id,
        ]);
        $this->assertDatabaseHas('media_usages', [
            'media_asset_id' => $cover->id,
            'usable_type' => $translation->getMorphClass(),
            'usable_id' => (string) $translation->id,
            'field' => 'og_image',
        ]);

        $this->assertTrue($cover->isInUse());
        $this->assertTrue($galleryImage->isInUse());

        $category->media()->detach($galleryImage->id);
        $this->assertDatabaseMissing('media_usages', [
            'media_asset_id' => $galleryImage->id,
            'usable_id' => $category->id,
            'field' => 'gallery:'.$galleryImage->id,
        ]);
        $this->assertFalse($galleryImage->fresh()->isInUse());
    }

    public function test_direct_references_still_prevent_deletion_if_the_usage_index_is_stale(): void
    {
        $media = $this->media('service.jpg');

        Service::query()->create([
            'slug' => 'prototype',
            'title' => 'Prototype',
            'image_id' => $media->id,
        ]);
        MediaUsage::query()->delete();

        $this->assertTrue($media->isInUse());
    }

    private function media(string $name): MediaAsset
    {
        return MediaAsset::query()->create([
            'disk' => 'local',
            'path' => 'media/originals/'.$name,
            'original_name' => $name,
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 1024,
            'width' => 1200,
            'height' => 800,
            'sha256' => hash('sha256', $name),
        ]);
    }
}
