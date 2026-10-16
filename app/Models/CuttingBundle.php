<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingBundle extends Model
{
    protected $fillable = [
        'cutting_audit_id', 'bundle_no', 'size', 'panel_numbers', 'pieces', 'status', 'remarks',
    ];

    protected $casts = ['pieces' => 'integer'];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(CuttingAudit::class, 'cutting_audit_id');
    }
}
