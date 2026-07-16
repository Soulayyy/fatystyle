<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicationRelease extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'sequence',
        'status',
        'manifest_path',
        'checksum',
        'metadata',
        'published_by',
        'published_at',
        'rollback_of_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function rollbackOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rollback_of_id');
    }
}
