<?php

namespace App\Models;

use App\Models\Concerns\HasEditorialPublication;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasEditorialPublication;
    use HasUlids;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'slug', 'title', 'description', 'image_id', 'legacy_image_path', 'position', 'is_visible',
        'status', 'is_featured', 'scheduled_at', 'published_at', 'expires_at', 'seo_title',
        'seo_description', 'audience', 'price_label', 'duration_label', 'cta_label', 'cta_url',
    ];

    protected function casts(): array
    {
        return $this->editorialCasts();
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }
}
