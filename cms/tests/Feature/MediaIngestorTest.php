<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Services\Media\MediaIngestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class MediaIngestorTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stored_upload_is_validated_deduplicated_and_moved_to_hash_storage(): void
    {
        Storage::fake('local');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $this->assertIsString($png);
        Storage::disk('local')->put('media/originals/uploads/first.png', $png);

        $ingestor = app(MediaIngestor::class);
        $media = $ingestor->ingestStoredUpload(
            'media/originals/uploads/first.png',
            'Photo atelier.png',
            attributes: ['alt_text' => 'Travail de couture à l’atelier'],
        );

        $this->assertSame('image/png', $media->mime_type);
        $this->assertSame('Photo atelier.png', $media->original_name);
        $this->assertSame(1, $media->width);
        $this->assertSame(1, $media->height);
        $this->assertStringStartsWith('media/originals/', $media->path);
        Storage::disk('local')->assertExists($media->path);
        Storage::disk('local')->assertMissing('media/originals/uploads/first.png');
        $this->assertSame([1], $media->variants()->pluck('width')->all());
        Storage::disk('local')->assertExists($media->variants()->firstOrFail()->path);

        Storage::disk('local')->put('media/originals/uploads/duplicate.png', $png);
        $duplicate = $ingestor->ingestStoredUpload('media/originals/uploads/duplicate.png', 'Copie.png');

        $this->assertTrue($media->is($duplicate));
        $this->assertSame(1, MediaAsset::query()->count());
        Storage::disk('local')->assertMissing('media/originals/uploads/duplicate.png');
    }

    public function test_a_fake_image_is_rejected_and_removed(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('media/originals/uploads/fake.jpg', '<?php echo "not an image";');

        $this->expectException(RuntimeException::class);

        try {
            app(MediaIngestor::class)->ingestStoredUpload('media/originals/uploads/fake.jpg', 'fake.jpg');
        } finally {
            Storage::disk('local')->assertMissing('media/originals/uploads/fake.jpg');
        }
    }
}
