<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NavigationItem extends Model
{
    use HasUlids;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'location', 'locale', 'label', 'url', 'position', 'is_visible', 'opens_new_tab', 'parent_id',
    ];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'opens_new_tab' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }
}
