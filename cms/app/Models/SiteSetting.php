<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use RecordsActivity;

    protected $fillable = ['group', 'key', 'locale', 'value', 'value_json', 'is_public'];

    protected function casts(): array
    {
        return ['value' => 'array', 'is_public' => 'boolean'];
    }

    protected function valueJson(): Attribute
    {
        return Attribute::make(
            get: fn (): string => json_encode($this->value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            set: fn (?string $value): array => ['value' => json_decode($value ?: 'null', true, flags: JSON_THROW_ON_ERROR)],
        );
    }
}
