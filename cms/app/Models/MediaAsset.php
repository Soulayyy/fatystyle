<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class MediaAsset extends Model
{
    use HasUlids;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'disk', 'path', 'original_name', 'mime_type', 'extension', 'size_bytes', 'width', 'height',
        'sha256', 'title', 'alt_text', 'is_decorative', 'caption', 'credit', 'rights', 'taken_at',
        'tags', 'focal_x', 'focal_y', 'source_path', 'metadata', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'is_decorative' => 'boolean',
            'taken_at' => 'date',
            'tags' => 'array',
            'focal_x' => 'decimal:4',
            'focal_y' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MediaVariant::class)->orderBy('width');
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = max(0, $this->size_bytes);
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $power = min((int) floor(log(max($bytes, 1), 1024)), count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 1, ',', ' ').' '.$units[$power];
    }

    public function isInUse(): bool
    {
        return $this->usages()->exists()
            || Service::withTrashed()->where('image_id', $this->getKey())->exists()
            || CreationCategory::withTrashed()->where('cover_id', $this->getKey())->exists()
            || PageTranslation::query()->where('og_image_id', $this->getKey())->exists()
            || DB::table('creation_category_media')->where('media_asset_id', $this->getKey())->exists();
    }

    public function publicPath(): string
    {
        $variant = $this->bestVariant(1920);
        if ($variant !== null) {
            return $variant->publicPath();
        }

        if ($this->source_path) {
            return ltrim($this->source_path, '/');
        }

        return 'assets/images/cms/'.$this->sha256.'.'.$this->extension;
    }

    public function publicThumbnailPath(): string
    {
        $variant = $this->bestVariant(640);
        if ($variant !== null) {
            return $variant->publicPath();
        }

        if ($this->source_path) {
            $directory = trim(dirname($this->source_path), '.\/');

            return ($directory === '' ? '' : $directory.'/').'thumbs/'.basename($this->source_path);
        }

        return $this->publicPath();
    }

    public function publicSrcset(): ?string
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();
        if ($variants->isEmpty()) {
            return null;
        }

        return $variants->map(fn (MediaVariant $variant): string => $variant->publicPath().' '.$variant->width.'w')->implode(', ');
    }

    private function bestVariant(int $maximumWidth): ?MediaVariant
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();
        if ($variants->isEmpty()) {
            return null;
        }

        return $variants->where('width', '<=', $maximumWidth)->last() ?: $variants->first();
    }
}
