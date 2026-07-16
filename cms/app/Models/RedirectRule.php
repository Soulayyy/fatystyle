<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RedirectRule extends Model
{
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'source_path', 'target_url', 'http_status', 'is_active', 'hit_count', 'last_hit_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_hit_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
