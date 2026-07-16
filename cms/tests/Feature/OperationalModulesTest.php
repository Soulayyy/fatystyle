<?php

namespace Tests\Feature;

use App\Enums\ContactStatus;
use App\Enums\ContentStatus;
use App\Enums\PageTemplate;
use App\Models\ContactRequest;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\PublicationRelease;
use App\Models\RedirectRule;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Services\Publishing\ReleasePublisher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationalModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_request_gets_a_reference_and_structured_status(): void
    {
        $request = ContactRequest::create([
            'name' => 'Cliente Faty Style',
            'email' => 'cliente@example.test',
            'message' => 'Je souhaite une robe sur mesure.',
        ]);

        $this->assertMatchesRegularExpression('/^FS-\d{6}-[A-Z0-9]{6}$/', $request->reference);
        $this->assertSame(ContactStatus::New, $request->status);
        $this->assertNotNull($request->received_at);
    }

    public function test_redirect_source_is_unique(): void
    {
        RedirectRule::create(['source_path' => '/ancienne.html', 'target_url' => '/nouvelle.html']);

        $this->expectException(UniqueConstraintViolationException::class);
        RedirectRule::create(['source_path' => '/ancienne.html', 'target_url' => '/autre.html']);
    }

    public function test_release_is_generated_and_public_content_link_is_switched_atomically(): void
    {
        $root = sys_get_temp_dir().'/fatystyle-release-'.uniqid();
        mkdir($root, 0777, true);
        Storage::fake('local');
        config()->set('cms.public_release_path', $root.'/releases');
        config()->set('cms.public_content_link', $root.'/public/content.json');
        config()->set('cms.public_media_link', $root.'/public/assets/images/cms');

        SiteSetting::create(['group' => 'site', 'key' => 'content', 'locale' => 'fr', 'value' => ['name' => 'Faty Style']]);
        NavigationItem::create(['label' => 'Accueil', 'url' => 'index.html', 'position' => 0]);
        $page = Page::create(['template' => PageTemplate::Editorial, 'status' => ContentStatus::Published, 'is_home' => true]);
        $page->translations()->create([
            'locale' => 'fr', 'slug' => '', 'title' => 'Accueil', 'h1' => 'Faty Style',
            'seo_title' => 'Faty Style', 'seo_description' => 'Atelier de couture',
        ]);
        $hash = hash('sha256', 'managed-image');
        $path = 'media/originals/'.$hash.'.png';
        Storage::disk('local')->put($path, 'managed-image');
        $media = MediaAsset::create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'atelier.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 13,
            'sha256' => $hash,
        ]);
        Service::create(['slug' => 'atelier', 'title' => 'Atelier', 'image_id' => $media->id]);

        $release = app(ReleasePublisher::class)->publish();

        $this->assertSame('published', $release->status);
        $this->assertTrue(is_link($root.'/public/content.json'));
        $payload = json_decode(file_get_contents($root.'/public/content.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Faty Style', $payload['site']['name']);
        $this->assertSame('Faty Style', $payload['pages']['index.html']['seo']['title']);
        $this->assertSame('assets/images/cms/'.$hash.'.png', $payload['services'][0]['image']);
        $this->assertTrue(is_link($root.'/public/assets/images/cms'));
        $this->assertFileExists($root.'/public/assets/images/cms/'.$hash.'.png');
        $this->assertSame(1, PublicationRelease::count());

        $this->deleteDirectory($root);
    }

    private function deleteDirectory(string $root): void
    {
        if (is_link($root.'/public/content.json')) {
            unlink($root.'/public/content.json');
        }
        if (is_link($root.'/public/assets/images/cms')) {
            unlink($root.'/public/assets/images/cms');
        }
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
