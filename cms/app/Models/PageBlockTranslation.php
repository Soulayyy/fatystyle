<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBlockTranslation extends Model
{
    use HasFactory;
    use RecordsActivity;

    protected $fillable = ['block_id', 'locale', 'content', 'content_json'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(PageBlock::class, 'block_id');
    }

    protected function contentJson(): Attribute
    {
        return Attribute::make(
            get: fn (): string => json_encode($this->content ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            set: fn (?string $value): array => [
                'content' => json_decode($value ?: '{}', true, flags: JSON_THROW_ON_ERROR),
            ],
        );
    }
}
