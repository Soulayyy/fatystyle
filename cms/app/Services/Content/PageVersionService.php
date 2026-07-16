<?php

namespace App\Services\Content;

use App\Models\Page;
use App\Models\PageVersion;
use Illuminate\Support\Facades\DB;

class PageVersionService
{
    public function __construct(private readonly PageSnapshotter $snapshotter) {}

    public function capture(Page $page, ?string $summary = null, ?int $userId = null): PageVersion
    {
        return DB::transaction(function () use ($page, $summary, $userId): PageVersion {
            $lockedPage = Page::query()->lockForUpdate()->findOrFail($page->getKey());
            $nextVersion = ((int) $lockedPage->versions()->max('version')) + 1;

            return $lockedPage->versions()->create([
                'version' => $nextVersion,
                'status' => $lockedPage->status,
                'snapshot' => $this->snapshotter->snapshot($lockedPage),
                'change_summary' => $summary,
                'created_by' => $userId ?? auth()->id(),
            ]);
        });
    }
}
