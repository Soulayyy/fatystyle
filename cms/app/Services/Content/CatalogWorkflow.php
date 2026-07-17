<?php

namespace App\Services\Content;

use App\Enums\ContentStatus;
use App\Models\CreationCategory;
use App\Models\Service;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CatalogWorkflow
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

    public function transition(Service|CreationCategory $record, ContentStatus $target): Model
    {
        return DB::transaction(function () use ($record, $target): Model {
            $locked = $record->newQuery()->lockForUpdate()->findOrFail($record->getKey());
            $current = $locked->status;

            if (! in_array($target->value, self::TRANSITIONS[$current->value] ?? [], true)) {
                throw new DomainException("Transition impossible de {$current->value} vers {$target->value}.");
            }

            if ($target === ContentStatus::Scheduled && $locked->scheduled_at?->isPast() !== false) {
                throw new DomainException('Une publication programmée doit avoir une date future.');
            }

            $locked->status = $target;
            $locked->is_visible = ! in_array($target, [ContentStatus::Hidden, ContentStatus::Archived], true);

            if ($target === ContentStatus::Published) {
                $locked->published_at = now();
                $locked->scheduled_at = null;
                $locked->is_visible = true;
            }

            $locked->save();

            return $locked->refresh();
        });
    }

    /** @return list<ContentStatus> */
    public function allowedTargets(Service|CreationCategory $record): array
    {
        return array_map(
            ContentStatus::from(...),
            self::TRANSITIONS[$record->status->value] ?? [],
        );
    }
}
