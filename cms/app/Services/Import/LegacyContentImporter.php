<?php

namespace App\Services\Import;

use App\Enums\BlockType;
use App\Enums\ContentStatus;
use App\Enums\PageTemplate;
use App\Models\CreationCategory;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Services\Content\PageVersionService;
use App\Services\Media\MediaIngestor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class LegacyContentImporter
{
    private bool $dryRun = false;

    public function __construct(
        private readonly MediaIngestor $media,
        private readonly PageVersionService $versions,
    ) {}

    /** @return array<string, int> */
    public function import(string $jsonPath, bool $dryRun = false): array
    {
        $this->dryRun = $dryRun;
        $jsonPath = realpath($jsonPath) ?: $jsonPath;
        if (! is_file($jsonPath)) {
            throw new RuntimeException("Fichier de contenu introuvable : {$jsonPath}");
        }

        try {
            $payload = json_decode(file_get_contents($jsonPath) ?: '', true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Le fichier de contenu JSON est invalide.', previous: $exception);
        }

        $this->validate($payload);
        $siteRoot = dirname(dirname($jsonPath));
        $stats = ['pages' => 0, 'blocks' => 0, 'settings' => 0, 'navigation' => 0, 'services' => 0, 'categories' => 0, 'media' => 0];

        $operation = function () use ($payload, $siteRoot, &$stats): void {
            $this->importSettings($payload, $stats);
            $this->importNavigation($payload['navigation'], $stats);
            $this->importPages($payload, $stats);
            $this->importServices($payload['services'], $siteRoot, $stats);
            $this->importCategories($payload['creationCategories'], $siteRoot, $stats);
        };

        if ($dryRun) {
            DB::beginTransaction();
            try {
                $operation();
            } finally {
                DB::rollBack();
            }
        } else {
            DB::transaction($operation);
        }

        return $stats;
    }

    /** @param array<string, mixed> $payload */
    private function validate(array $payload): void
    {
        foreach (['site', 'navigation', 'pages', 'services', 'creationCategories'] as $required) {
            if (! array_key_exists($required, $payload)) {
                throw new RuntimeException("Section obligatoire absente : {$required}");
            }
        }
    }

    /** @param array<string, mixed> $payload @param array<string, int> $stats */
    private function importSettings(array $payload, array &$stats): void
    {
        $groups = Arr::only($payload, ['site', 'seo', 'contact', 'socialLinks', 'footer', 'savoirFaire']);
        unset($groups['contact']['web3formsAccessKey']);

        foreach ($groups as $group => $values) {
            SiteSetting::updateOrCreate(
                ['group' => $group, 'key' => 'content', 'locale' => 'fr'],
                ['value' => $values, 'is_public' => true],
            );
            $stats['settings']++;
        }
    }

    /** @param list<array<string, mixed>> $items @param array<string, int> $stats */
    private function importNavigation(array $items, array &$stats): void
    {
        foreach ($items as $position => $item) {
            $navigationItem = NavigationItem::withTrashed()->updateOrCreate(
                ['location' => 'primary', 'locale' => 'fr', 'url' => $item['url']],
                ['label' => $item['label'], 'position' => $position, 'is_visible' => true],
            );
            if ($navigationItem->trashed()) {
                $navigationItem->restore();
            }
            $stats['navigation']++;
        }
    }

    /** @param array<string, mixed> $payload @param array<string, int> $stats */
    private function importPages(array $payload, array &$stats): void
    {
        $blocksByPage = [
            'index.html' => $payload['home'] ?? [],
            'savoir-faire.html' => ['categories' => $payload['creationCategories'] ?? []],
            'contact.html' => ['contact' => Arr::except($payload['contact'] ?? [], ['web3formsAccessKey'])],
        ];

        foreach ($payload['pages'] as $filename => $pageData) {
            $slug = $filename === 'index.html' ? '' : Str::beforeLast($filename, '.html');
            $seo = $pageData['seo'] ?? [];
            $translation = PageTranslation::query()->where('locale', 'fr')->where('slug', $slug)->first();
            $page = $translation ? Page::withTrashed()->find($translation->page_id) : null;
            $changed = false;
            if (! $page) {
                $page = Page::create([
                    'is_home' => $filename === 'index.html',
                    'template' => $this->templateFor($filename),
                    'status' => ContentStatus::Draft,
                ]);
                $changed = true;
            }
            if ($page->trashed()) {
                $page->restore();
                $changed = true;
            }
            $page->fill([
                'is_home' => $filename === 'index.html',
                'template' => $this->templateFor($filename),
            ]);
            if ($page->isDirty()) {
                $page->save();
                $changed = true;
            }

            $pageTranslation = $page->translations()->firstOrNew(['locale' => 'fr']);
            $pageTranslation->fill([
                'slug' => $slug,
                'title' => $seo['title'] ?? $filename,
                'h1' => $seo['title'] ?? $filename,
                'seo_title' => $seo['title'] ?? null,
                'seo_description' => $seo['description'] ?? null,
            ]);
            if (! $pageTranslation->exists || $pageTranslation->isDirty()) {
                $pageTranslation->save();
                $changed = true;
            }

            foreach ($blocksByPage[$filename] ?? [] as $key => $content) {
                $block = $page->blocks->first(fn ($candidate): bool => ($candidate->settings['import_key'] ?? null) === $key);
                if (! $block) {
                    $block = $page->blocks()->create([
                        'type' => $this->blockTypeFor($key),
                        'position' => $page->blocks()->count(),
                        'settings' => ['import_key' => $key, 'source' => 'legacy-json'],
                    ]);
                    $changed = true;
                }
                $blockTranslation = $block->translations()->firstOrNew(['locale' => 'fr']);
                if (! $blockTranslation->exists || $blockTranslation->content != $content) {
                    $blockTranslation->content = $content;
                    $blockTranslation->save();
                    $changed = true;
                }
                $stats['blocks']++;
            }

            if ($changed || ! $page->versions()->exists()) {
                $this->versions->capture($page, 'Import du contenu public');
            }
            $stats['pages']++;
        }
    }

    /** @param list<array<string, mixed>> $items @param array<string, int> $stats */
    private function importServices(array $items, string $siteRoot, array &$stats): void
    {
        foreach ($items as $position => $item) {
            $image = $this->importMedia($siteRoot, $item['image'] ?? null, $stats);
            $service = Service::withTrashed()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'image_id' => $image?->id,
                    'legacy_image_path' => $item['image'] ?? null,
                    'position' => $position,
                    'is_visible' => true,
                ],
            );
            if ($service->trashed()) {
                $service->restore();
            }
            $stats['services']++;
        }
    }

    /** @param list<array<string, mixed>> $items @param array<string, int> $stats */
    private function importCategories(array $items, string $siteRoot, array &$stats): void
    {
        $importedSlugs = array_column($items, 'slug');

        foreach ($items as $position => $item) {
            $coverPath = ($item['folder'] ?? '').($item['cover'] ?? '');
            $cover = $this->importMedia($siteRoot, $coverPath, $stats);
            $category = CreationCategory::withTrashed()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'cover_id' => $cover?->id,
                    'legacy_folder' => $item['folder'] ?? null,
                    'legacy_cover' => $item['cover'] ?? null,
                    'position' => $position,
                    'is_visible' => true,
                ],
            );
            if ($category->trashed()) {
                $category->restore();
            }

            $sync = [];
            foreach ($item['photos'] ?? [] as $photoPosition => $photo) {
                $asset = $this->importMedia($siteRoot, ($item['folder'] ?? '').$photo, $stats);
                if ($asset) {
                    $sync[$asset->id] = ['position' => $photoPosition, 'alt_text' => $item['title']];
                }
            }
            $category->media()->sync($sync);
            $stats['categories']++;
        }

        CreationCategory::query()
            ->whereNotIn('slug', $importedSlugs)
            ->delete();
    }

    /** @param array<string, int> $stats */
    private function importMedia(string $siteRoot, ?string $relativePath, array &$stats): ?MediaAsset
    {
        if (! $relativePath) {
            return null;
        }

        $absolutePath = $siteRoot.'/'.ltrim($relativePath, '/');
        if ($this->dryRun) {
            if (! is_file($absolutePath)) {
                throw new RuntimeException("Média source introuvable : {$relativePath}");
            }
            $stats['media']++;

            return null;
        }

        $asset = $this->media->importLegacyFile($absolutePath, $relativePath);
        $stats['media']++;

        return $asset;
    }

    private function templateFor(string $filename): PageTemplate
    {
        return match ($filename) {
            'index.html' => PageTemplate::Editorial,
            'savoir-faire.html' => PageTemplate::Gallery,
            'pro.html' => PageTemplate::Offer,
            'contact.html' => PageTemplate::Contact,
            default => PageTemplate::Simple,
        };
    }

    private function blockTypeFor(string $key): BlockType
    {
        return match ($key) {
            'hero' => BlockType::Hero,
            'creationPreview', 'categories' => BlockType::Gallery,
            'certification' => BlockType::KeyFigures,
            'googleReviews' => BlockType::Reviews,
            'contact' => BlockType::Form,
            default => BlockType::RichText,
        };
    }
}
