<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaAsset extends Model
{
    use HasUlids;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'disk', 'path', 'original_name', 'mime_type', 'extension', 'size_bytes', 'width', 'height',
        'sha256', 'alt_text', 'caption', 'credit', 'source_path', 'metadata', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = max(0, $this->size_bytes);
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $power = min((int) floor(log(max($bytes, 1), 1024)), count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 1, ',', ' ').' '.$units[$power];
    }
}
