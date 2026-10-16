<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingSignoff extends Model
{
    protected $fillable = [
        'cutting_audit_id', 'cutting_stage_id', 'stage', 'user_id', 'user_name',
        'role', 'result', 'comments', 'reason', 'revision', 'checklist_version', 'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'revision' => 'integer',
        'checklist_version' => 'integer',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(CuttingAudit::class, 'cutting_audit_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CuttingStage::class, 'cutting_stage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
