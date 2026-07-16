<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTranslation extends Model
{
    use HasFactory;
    use RecordsActivity;

    protected $fillable = [
        'page_id',
        'locale',
        'slug',
        'title',
        'h1',
        'intro',
        'seo_title',
        'seo_description',
        'og_title',
        'og_description',
        'og_image_id',
        'canonical_url',
        'is_indexable',
        'links_followed',
    ];

    protected function casts(): array
    {
        return [
            'is_indexable' => 'boolean',
            'links_followed' => 'boolean',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_image_id');
    }
}
