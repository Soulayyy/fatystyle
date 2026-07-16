<?php

namespace Tests\Feature;

use App\Enums\BlockType;
use App\Enums\ContentStatus;
use App\Enums\PageTemplate;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageTranslation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_page_can_store_translated_locked_blocks(): void
    {
        $page = Page::query()->create([
            'template' => PageTemplate::Editorial,
            'status' => ContentStatus::Draft,
            'is_home' => true,
        ]);

        PageTranslation::query()->create([
            'page_id' => $page->id,
            'locale' => 'fr',
            'slug' => 'accueil',
            'title' => 'Accueil',
            'h1' => 'Votre atelier de couture sur mesure',
        ]);

        $block = PageBlock::query()->create([
            'page_id' => $page->id,
            'type' => BlockType::Hero,
            'position' => 0,
            'is_locked' => true,
        ]);

        $block->translations()->create([
            'locale' => 'fr',
            'content' => ['title' => 'Votre atelier de couture sur mesure'],
        ]);

        $this->assertSame(ContentStatus::Draft, $page->fresh()->status);
        $this->assertSame('accueil', $page->translations()->first()->slug);
        $this->assertTrue($block->fresh()->is_locked);
        $this->assertSame('Votre atelier de couture sur mesure', $block->translations()->first()->content['title']);
    }

    public function test_page_slugs_are_unique_per_locale(): void
    {
        $firstPage = Page::query()->create();
        $secondPage = Page::query()->create();

        PageTranslation::query()->create([
            'page_id' => $firstPage->id,
            'locale' => 'fr',
            'slug' => 'presentation',
            'title' => 'Présentation',
            'h1' => 'Présentation',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        PageTranslation::query()->create([
            'page_id' => $secondPage->id,
            'locale' => 'fr',
            'slug' => 'presentation',
            'title' => 'Autre présentation',
            'h1' => 'Autre présentation',
        ]);
    }
}
