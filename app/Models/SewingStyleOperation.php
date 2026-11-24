<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SewingStyleOperation extends Model
{
    protected $fillable = ['style_id', 'sewing_operation_id', 'sewing_machine_id', 'sequence_no', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sequence_no' => 'integer'];

    public function style(): BelongsTo
    {
        return $this->belongsTo(Style::class);
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(SewingOperation::class, 'sewing_operation_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(SewingMachine::class, 'sewing_machine_id');
    }
}
