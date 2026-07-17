<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\PageTemplate;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasFactory;
    use HasUlids;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'template',
        'status',
        'is_home',
        'lock_version',
        'scheduled_at',
        'expires_at',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'template' => PageTemplate::class,
            'status' => ContentStatus::class,
            'is_home' => 'boolean',
            'scheduled_at' => 'datetime',
            'expires_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('position');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class)->orderByDesc('version');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->translations->firstWhere('locale', config('cms.default_locale'))?->title
            ?? $this->translations->first()?->title
            ?? 'Page sans titre';
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query
            ->where('status', ContentStatus::Published->value)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
