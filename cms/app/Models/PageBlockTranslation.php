<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBlockTranslation extends Model
{
    use HasFactory;
    use RecordsActivity;

    protected $fillable = ['block_id', 'locale', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(PageBlock::class, 'block_id');
    }
}
