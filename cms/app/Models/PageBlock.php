<?php

namespace App\Models;

use App\Enums\BlockType;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PageBlock extends Model
{
    use HasFactory;
    use HasUlids;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'page_id',
        'type',
        'position',
        'settings',
        'is_visible',
        'is_locked',
        'visible_from',
        'visible_until',
    ];

    protected function casts(): array
    {
        return [
            'type' => BlockType::class,
            'settings' => 'array',
            'is_visible' => 'boolean',
            'is_locked' => 'boolean',
            'visible_from' => 'datetime',
            'visible_until' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PageBlockTranslation::class, 'block_id');
    }
}
