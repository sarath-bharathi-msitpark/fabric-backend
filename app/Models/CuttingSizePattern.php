<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingSizePattern extends Model
{
    protected $fillable = [
        'cutting_audit_id', 'size_name', 'sort_order',
        'top_result', 'middle_result', 'bottom_result', 'remarks',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'top_result' => 'boolean',
        'middle_result' => 'boolean',
        'bottom_result' => 'boolean',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(CuttingAudit::class, 'cutting_audit_id');
    }
}
