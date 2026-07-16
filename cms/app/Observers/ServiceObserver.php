<?php

namespace App\Observers;

use App\Models\Service;
use App\Services\Media\MediaUsageSynchronizer;

class ServiceObserver
{
    public function __construct(private readonly MediaUsageSynchronizer $usages) {}

    public function saved(Service $service): void
    {
        $this->usages->sync($service, 'image', $service->image_id);
    }

    public function forceDeleted(Service $service): void
    {
        $this->usages->forget($service);
    }
}
