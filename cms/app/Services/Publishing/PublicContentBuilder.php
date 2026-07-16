<?php

namespace App\Services\Publishing;

use App\Models\CreationCategory;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Service;
use App\Models\SiteSetting;

class PublicContentBuilder
{
    /** @return array<string, mixed> */
    public function build(): array
    {
        $settings = SiteSetting::query()
            ->where('locale', config('cms.default_locale'))
            ->where('is_public', true)
            ->get()
            ->keyBy('group')
            ->map->value;

        $pages = [];
        $home = [];
        foreach (Page::query()->with(['translations', 'blocks.translations'])->get() as $page) {
            $translation = $page->translations->firstWhere('locale', config('cms.default_locale'));
            if (! $translation) {
                continue;
            }

            $filename = $translation->slug === '' ? 'index.html' : $translation->slug.'.html';
            $pages[$filename] = [
                'seo' => [
                    'title' => $translation->seo_title ?: $translation->title,
                    'description' => $translation->seo_description ?: $translation->intro,
                ],
            ];

            if ($page->is_home) {
                foreach ($page->blocks as $block) {
                    if (! $block->is_visible) {
                        continue;
                    }
                    $key = $block->settings['import_key'] ?? null;
                    $content = $block->translations->firstWhere('locale', config('cms.default_locale'))?->content;
                    if ($key && $content !== null) {
                        $home[$key] = $content;
                    }
                }
            }
        }

        return [
            'site' => $settings->get('site', []),
            'seo' => $settings->get('seo', []),
            'navigation' => NavigationItem::query()
                ->where('locale', config('cms.default_locale'))
                ->where('location', 'primary')
                ->where('is_visible', true)
                ->orderBy('position')
                ->get(['label', 'url'])
                ->toArray(),
            'pages' => $pages,
            'home' => $home,
            'services' => Service::query()->with('image')->where('is_visible', true)->orderBy('position')->get()
                ->map(fn (Service $service): array => [
                    'title' => $service->title,
                    'slug' => $service->slug,
                    'image' => $service->image?->publicPath() ?: $service->legacy_image_path,
                    'description' => $service->description,
                ])->values()->all(),
            'creationCategories' => CreationCategory::query()
                ->with(['cover', 'media'])
                ->where('is_visible', true)
                ->orderBy('position')
                ->get()
                ->map(fn (CreationCategory $category): array => [
                    'title' => $category->title,
                    'slug' => $category->slug,
                    'folder' => '',
                    'cover' => $category->cover?->publicPath() ?: ($category->legacy_folder.$category->legacy_cover),
                    'description' => $category->description,
                    'photos' => $category->media->map(
                        fn ($media): array => [
                            'src' => $media->publicPath(),
                            'thumbnail' => $media->publicThumbnailPath(),
                            'alt' => $media->pivot->alt_text ?: $media->alt_text ?: $category->title,
                        ],
                    )->values()->all(),
                ])->values()->all(),
            'gallery' => [],
            'savoirFaire' => $settings->get('savoirFaire', []),
            'contact' => $settings->get('contact', []),
            'socialLinks' => $settings->get('socialLinks', []),
            'footer' => $settings->get('footer', []),
        ];
    }
}
