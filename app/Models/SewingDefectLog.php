<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SewingDefectLog extends Model
{
    protected $fillable = [
        'sewing_inspection_sheet_id', 'sewing_inspection_operation_id', 'round_no', 'is_reaudit',
        'sewing_defect_master_id', 'defect_type', 'capa_code', 'defect_name', 'root_cause',
        'corrective_action', 'preventive_action', 'qty', 'photo_path', 'note', 'logged_by', 'logged_at',
    ];

    protected $casts = [
        'round_no' => 'integer',
        'is_reaudit' => 'boolean',
        'qty' => 'integer',
        'logged_at' => 'datetime',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(SewingInspectionSheet::class, 'sewing_inspection_sheet_id');
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(SewingInspectionOperation::class, 'sewing_inspection_operation_id');
    }

    public function defectMaster(): BelongsTo
    {
        return $this->belongsTo(SewingDefectMaster::class, 'sewing_defect_master_id');
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
