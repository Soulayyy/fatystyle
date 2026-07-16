<?php

namespace App\Observers;

use App\Models\PageTranslation;
use App\Services\Media\MediaUsageSynchronizer;

class PageTranslationObserver
{
    public function __construct(private readonly MediaUsageSynchronizer $usages) {}

    public function saved(PageTranslation $translation): void
    {
        $this->usages->sync($translation, 'og_image', $translation->og_image_id);
    }

    public function deleted(PageTranslation $translation): void
    {
        $this->usages->forget($translation);
    }
}
