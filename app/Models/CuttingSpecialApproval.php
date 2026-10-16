<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingSpecialApproval extends Model
{
    protected $fillable = [
        'cutting_audit_id', 'cutting_stage_id', 'stage', 'requested_by', 'reason',
        'status', 'approved_by', 'approved_at', 'decision_reason',
    ];

    protected $casts = ['approved_at' => 'datetime'];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(CuttingAudit::class, 'cutting_audit_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CuttingStage::class, 'cutting_stage_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
