<?php

namespace App\Services\Publishing;

use App\Models\CreationCategory;
use App\Models\Page;
use App\Models\Service;
use RuntimeException;

class ContentPublicationValidator
{
    /** @return list<string> */
    public function errors(): array
    {
        $errors = [];
        $locale = config('cms.default_locale');

        Page::query()->publiclyAvailable()->with('translations')->each(function (Page $page) use (&$errors, $locale): void {
            $translation = $page->translations->firstWhere('locale', $locale);
            if (! $translation) {
                $errors[] = "La page {$page->id} ne possède pas de traduction {$locale}.";

                return;
            }
            if (blank($translation->h1)) {
                $errors[] = "La page {$translation->title} ne possède pas de H1.";
            }
            if (blank($translation->seo_title) && blank($translation->title)) {
                $errors[] = "La page {$translation->slug} ne possède pas de titre SEO.";
            }
        });

        Service::query()->publiclyAvailable()->with('image')->each(function (Service $service) use (&$errors): void {
            if (mb_strlen(trim($service->description ?? '')) < 30) {
                $errors[] = "La prestation {$service->title} possède une description trop courte.";
            }
            if (! $service->image && blank($service->legacy_image_path)) {
                $errors[] = "La prestation {$service->title} ne possède pas d’image.";
            }
        });

        CreationCategory::query()->publiclyAvailable()->with(['cover', 'media'])->each(function (CreationCategory $category) use (&$errors): void {
            if (mb_strlen(trim($category->description ?? '')) < 30) {
                $errors[] = "L’univers {$category->title} possède une description trop courte.";
            }
            if (! $category->cover && blank($category->legacy_cover)) {
                $errors[] = "L’univers {$category->title} ne possède pas de couverture.";
            }
            if ($category->media->isEmpty()) {
                $errors[] = "L’univers {$category->title} ne possède aucune photo.";
            }
        });

        return $errors;
    }

    public function ensureValid(): void
    {
        $errors = $this->errors();
        if ($errors !== []) {
            throw new RuntimeException("Publication bloquée :\n- ".implode("\n- ", $errors));
        }
    }
}
