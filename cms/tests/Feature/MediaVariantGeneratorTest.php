<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Services\Media\MediaVariantGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaVariantGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_responsive_webp_variants_and_srcset_are_generated_without_upscaling(): void
    {
        Storage::fake('local');
        $image = imagecreatetruecolor(2000, 1000);
        $this->assertNotFalse($image);
        $background = imagecolorallocate($image, 190, 20, 90);
        imagefilledrectangle($image, 0, 0, 1999, 999, $background);
        ob_start();
        imagejpeg($image, null, 90);
        $contents = ob_get_clean();
        imagedestroy($image);
        $this->assertIsString($contents);

        $path = 'media/originals/test/source.jpg';
        Storage::disk('local')->put($path, $contents);
        $media = MediaAsset::query()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'source.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => strlen($contents),
            'width' => 2000,
            'height' => 1000,
            'sha256' => hash('sha256', $contents),
        ]);

        app(MediaVariantGenerator::class)->generate($media);
        $media->load('variants');

        $this->assertSame([320, 640, 960, 1280, 1920, 2000], $media->variants->pluck('width')->all());
        $this->assertSame([160, 320, 480, 640, 960, 1000], $media->variants->pluck('height')->all());
        $this->assertSame('image/webp', $media->variants->first()->mime_type);
        $this->assertStringContainsString('320.webp 320w', $media->publicSrcset());
        $this->assertStringEndsWith('/1920.webp', $media->publicPath());
        $this->assertStringEndsWith('/640.webp', $media->publicThumbnailPath());

        foreach ($media->variants as $variant) {
            Storage::disk('local')->assertExists($variant->path);
            $this->assertLessThanOrEqual(2000, $variant->width);
        }
    }
}
