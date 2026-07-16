<?php

namespace App\Observers;

use App\Models\CreationCategory;
use App\Services\Media\MediaUsageSynchronizer;

class CreationCategoryObserver
{
    public function __construct(private readonly MediaUsageSynchronizer $usages) {}

    public function saved(CreationCategory $category): void
    {
        $this->usages->sync($category, 'cover', $category->cover_id);
    }

    public function forceDeleted(CreationCategory $category): void
    {
        $this->usages->forget($category);
    }
}
