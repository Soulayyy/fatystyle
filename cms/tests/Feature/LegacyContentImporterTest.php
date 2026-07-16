<?php

namespace Tests\Feature;

use App\Models\CreationCategory;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Services\Import\LegacyContentImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyContentImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_content_is_imported_without_copying_the_form_secret(): void
    {
        Storage::fake('local');
        [$jsonPath, $root] = $this->fixture();

        $stats = app(LegacyContentImporter::class)->import($jsonPath);

        $this->assertSame(2, $stats['pages']);
        $this->assertSame(1, NavigationItem::count());
        $this->assertSame(1, Service::count());
        $this->assertSame(1, CreationCategory::count());
        $this->assertSame(2, MediaAsset::count());
        $this->assertSame(2, Page::count());
        $this->assertSame(2, Page::query()->withCount('versions')->get()->sum('versions_count'));

        $contact = SiteSetting::where('group', 'contact')->firstOrFail()->value;
        $this->assertArrayNotHasKey('web3formsAccessKey', $contact);
        $this->assertSame('Un message', $contact['intro']);

        $this->deleteFixture($root);
    }

    public function test_dry_run_leaves_database_and_storage_unchanged(): void
    {
        Storage::fake('local');
        [$jsonPath, $root] = $this->fixture();

        $stats = app(LegacyContentImporter::class)->import($jsonPath, dryRun: true);

        $this->assertSame(2, $stats['pages']);
        $this->assertSame(0, Page::count());
        $this->assertSame(0, MediaAsset::count());
        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->deleteFixture($root);
    }

    public function test_reimport_is_idempotent_and_does_not_create_duplicate_versions(): void
    {
        Storage::fake('local');
        [$jsonPath, $root] = $this->fixture();

        $importer = app(LegacyContentImporter::class);
        $importer->import($jsonPath);
        $importer->import($jsonPath);

        $this->assertSame(2, Page::count());
        $this->assertSame(2, Page::query()->withCount('versions')->get()->sum('versions_count'));
        $this->assertSame(2, MediaAsset::count());

        $this->deleteFixture($root);
    }

    /** @return array{0: string, 1: string} */
    private function fixture(): array
    {
        $root = sys_get_temp_dir().'/fatystyle-import-'.uniqid();
        mkdir($root.'/data', 0777, true);
        mkdir($root.'/assets/images', 0777, true);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        file_put_contents($root.'/assets/images/service.png', $png);
        file_put_contents($root.'/assets/images/cover.png', $png.'different');

        $payload = [
            'site' => ['name' => 'Faty Style'],
            'seo' => ['siteUrl' => 'https://example.test'],
            'navigation' => [['label' => 'Accueil', 'url' => 'index.html']],
            'pages' => [
                'index.html' => ['seo' => ['title' => 'Accueil', 'description' => 'Description accueil']],
                'contact.html' => ['seo' => ['title' => 'Contact', 'description' => 'Description contact']],
            ],
            'home' => ['hero' => ['title' => 'Faty Style']],
            'services' => [[
                'title' => 'Sur mesure', 'slug' => 'sur-mesure', 'description' => 'Description',
                'image' => 'assets/images/service.png',
            ]],
            'creationCategories' => [[
                'title' => 'Mariage', 'slug' => 'mariage', 'description' => 'Description',
                'folder' => 'assets/images/', 'cover' => 'cover.png', 'photos' => ['cover.png'],
            ]],
            'contact' => ['intro' => 'Un message', 'web3formsAccessKey' => 'must-not-enter-cms'],
            'socialLinks' => [],
            'footer' => [],
            'savoirFaire' => [],
        ];
        $jsonPath = $root.'/data/content.json';
        file_put_contents($jsonPath, json_encode($payload, JSON_THROW_ON_ERROR));

        return [$jsonPath, $root];
    }

    private function deleteFixture(string $root): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($root);
    }
}
