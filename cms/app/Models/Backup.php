<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    use HasUlids;
    use RecordsActivity;

    protected $fillable = [
        'type', 'status', 'path', 'size_bytes', 'sha256', 'error', 'metadata', 'created_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'completed_at' => 'datetime'];
    }
}
