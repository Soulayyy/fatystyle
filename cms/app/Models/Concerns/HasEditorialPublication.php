<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;

trait HasEditorialPublication
{
    protected function editorialCasts(): array
    {
        return [
            'status' => ContentStatus::class,
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_visible', true)
            ->where('status', ContentStatus::Published->value)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
