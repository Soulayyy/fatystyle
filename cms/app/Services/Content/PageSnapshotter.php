<?php

namespace App\Services\Content;

use App\Models\Page;

class PageSnapshotter
{
    /** @return array<string, mixed> */
    public function snapshot(Page $page): array
    {
        $page->load([
            'translations' => fn ($query) => $query->orderBy('locale'),
            'blocks' => fn ($query) => $query->with([
                'translations' => fn ($translationQuery) => $translationQuery->orderBy('locale'),
            ])->orderBy('position'),
        ]);

        return [
            'schema_version' => 1,
            'captured_at' => now()->toIso8601String(),
            'page' => $page->only([
                'id', 'template', 'status', 'is_home', 'lock_version', 'scheduled_at', 'expires_at',
                'published_at',
            ]),
            'translations' => $page->translations->map->only([
                'locale', 'slug', 'title', 'h1', 'intro', 'seo_title', 'seo_description', 'og_title',
                'og_description', 'og_image_id', 'canonical_url', 'is_indexable', 'links_followed',
            ])->values()->all(),
            'blocks' => $page->blocks->map(fn ($block): array => [
                ...$block->only([
                    'id', 'type', 'position', 'settings', 'is_visible', 'is_locked', 'visible_from',
                    'visible_until',
                ]),
                'translations' => $block->translations->map->only([
                    'locale', 'content',
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
