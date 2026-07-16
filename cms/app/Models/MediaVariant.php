<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaVariant extends Model
{
    protected $fillable = [
        'media_asset_id', 'disk', 'path', 'mime_type', 'format', 'width', 'height', 'size_bytes', 'quality',
    ];

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    public function publicPath(): string
    {
        return 'assets/images/cms/'.basename(dirname($this->path)).'/'.$this->width.'.'.$this->format;
    }
}
