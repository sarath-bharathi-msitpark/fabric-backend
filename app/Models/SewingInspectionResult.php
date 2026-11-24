<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SewingInspectionResult extends Model
{
    public const RESULT_GREEN = 'green';

    public const RESULT_AMBER = 'amber';

    public const RESULT_RED = 'red';

    public const REAUDIT_CLEARED = 'cleared';

    public const REAUDIT_ESCALATED = 'escalated';

    protected $fillable = [
        'sewing_inspection_sheet_id', 'sewing_inspection_operation_id', 'round_no',
        'defect_count', 'result', 'corrective_action', 'note', 'inspected_at',
        're_audit_count', 're_audit_result', 're_audit_note', 're_audited_at',
    ];

    protected $casts = [
        'round_no' => 'integer',
        'defect_count' => 'integer',
        're_audit_count' => 'integer',
        'inspected_at' => 'datetime',
        're_audited_at' => 'datetime',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(SewingInspectionSheet::class, 'sewing_inspection_sheet_id');
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(SewingInspectionOperation::class, 'sewing_inspection_operation_id');
    }

    public function isGreen(): bool
    {
        return $this->result === self::RESULT_GREEN;
    }

    public function isAmber(): bool
    {
        return $this->result === self::RESULT_AMBER;
    }

    public function isRed(): bool
    {
        return $this->result === self::RESULT_RED;
    }

    public function requiresCorrectiveAction(): bool
    {
        return in_array($this->result, [self::RESULT_AMBER, self::RESULT_RED], true);
    }
}
