<?php

namespace App\Services\Content;

use App\Enums\ContentStatus;
use App\Models\Page;
use DomainException;
use Illuminate\Support\Facades\DB;

class PageWorkflow
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['in_review', 'archived'],
        'in_review' => ['draft', 'approved', 'archived'],
        'approved' => ['draft', 'scheduled', 'published', 'archived'],
        'scheduled' => ['draft', 'published', 'hidden', 'archived'],
        'published' => ['draft', 'hidden', 'archived'],
        'hidden' => ['draft', 'published', 'archived'],
        'archived' => ['draft'],
    ];

    public function __construct(private readonly PageVersionService $versions) {}

    public function transition(
        Page $page,
        ContentStatus $target,
        ?string $summary = null,
        ?int $userId = null,
    ): Page {
        return DB::transaction(function () use ($page, $target, $summary, $userId): Page {
            $locked = Page::query()->lockForUpdate()->findOrFail($page->getKey());
            $current = $locked->status;

            if (! in_array($target->value, self::TRANSITIONS[$current->value] ?? [], true)) {
                throw new DomainException("Transition impossible de {$current->value} vers {$target->value}.");
            }

            if ($target === ContentStatus::Scheduled && $locked->scheduled_at?->isPast() !== false) {
                throw new DomainException('Une publication programmée doit avoir une date future.');
            }

            $locked->status = $target;
            $locked->updated_by = $userId ?? auth()->id();
            $locked->lock_version++;

            if ($target === ContentStatus::Published) {
                $locked->published_at = now();
                $locked->scheduled_at = null;
            }

            $locked->save();
            $this->versions->capture($locked, $summary ?? "Passage au statut {$target->value}", $userId);

            return $locked->refresh();
        });
    }

    /** @return list<ContentStatus> */
    public function allowedTargets(Page $page): array
    {
        return array_map(
            ContentStatus::from(...),
            self::TRANSITIONS[$page->status->value] ?? [],
        );
    }
}
