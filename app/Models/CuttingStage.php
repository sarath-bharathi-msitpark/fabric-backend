<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuttingStage extends Model
{
    protected $fillable = [
        'cutting_audit_id', 'stage', 'status', 'result', 'comments',
        'inspection_date', 'submitted_at', 'locked_at',
        'template_id', 'checklist_version',
    ];

    protected $casts = [
        'inspection_date' => 'datetime',
        'submitted_at' => 'datetime',
        'locked_at' => 'datetime',
        'checklist_version' => 'integer',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(CuttingAudit::class, 'cutting_audit_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CuttingChecklistTemplate::class, 'template_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(CuttingCheckpointResult::class)->orderBy('sort_order');
    }

    public function signoffs(): HasMany
    {
        return $this->hasMany(CuttingSignoff::class)->orderBy('signed_at');
    }

    public function specialApproval(): HasMany
    {
        return $this->hasMany(CuttingSpecialApproval::class);
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['approved', 'special_approved'], true);
    }

    public function stageLabel(): string
    {
        return CuttingAudit::STAGE_LABELS[$this->stage] ?? ucfirst($this->stage);
    }

    public function resultLabel(): string
    {
        return CuttingAudit::RESULTS[$this->stage][$this->result] ?? '—';
    }
}
