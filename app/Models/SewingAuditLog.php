<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SewingAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'auditable_type', 'auditable_id', 'action', 'field',
        'old_value', 'new_value', 'reason', 'user_id', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
