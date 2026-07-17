<?php

namespace App\Models;

use App\Models\Concerns\HasEditorialPublication;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreationCategory extends Model
{
    use HasEditorialPublication;
    use HasUlids;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'slug', 'title', 'description', 'cover_id', 'legacy_folder', 'legacy_cover', 'position', 'is_visible',
        'status', 'is_featured', 'scheduled_at', 'published_at', 'expires_at', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return $this->editorialCasts();
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_id');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class, 'creation_category_media')
            ->using(CreationCategoryMedia::class)
            ->withPivot(['position', 'alt_text'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function galleryItems(): HasMany
    {
        return $this->hasMany(CreationCategoryMedia::class)->orderBy('position');
    }
}
