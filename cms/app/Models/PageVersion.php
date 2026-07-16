<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVersion extends Model
{
    use HasFactory;
    use HasUlids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'page_id',
        'version',
        'status',
        'snapshot',
        'change_summary',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'snapshot' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
