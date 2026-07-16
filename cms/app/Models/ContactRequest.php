<?php

namespace App\Models;

use App\Enums\ContactStatus;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ContactRequest extends Model
{
    use HasUlids;
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'reference', 'status', 'name', 'email', 'phone', 'request_type', 'desired_date', 'message',
        'internal_notes', 'assigned_to', 'consent_at', 'received_at', 'replied_at', 'source', 'ip_hash',
        'user_agent', 'metadata',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            $request->reference ??= 'FS-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
            $request->status ??= ContactStatus::New;
            $request->received_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ContactStatus::class,
            'desired_date' => 'date',
            'consent_at' => 'datetime',
            'received_at' => 'datetime',
            'replied_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
