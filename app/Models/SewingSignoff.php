<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SewingSignoff extends Model
{
    public const ROLE_QC = 'qc';

    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLE_PM = 'pm';

    public const ROLE_QAM = 'qam';

    public const ROLES = [
        self::ROLE_QC => 'Line QC',
        self::ROLE_SUPERVISOR => 'Line Supervisor',
        self::ROLE_PM => 'Production Manager',
        self::ROLE_QAM => 'QA Manager',
    ];

    protected $fillable = [
        'sewing_inspection_sheet_id', 'role', 'user_id', 'user_name', 'comments', 'reason', 'signed_at',
    ];

    protected $casts = ['signed_at' => 'datetime'];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(SewingInspectionSheet::class, 'sewing_inspection_sheet_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
