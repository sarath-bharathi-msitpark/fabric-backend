<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SewingInspectionRound extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'sewing_inspection_sheet_id', 'round_no', 'bundle_no', 'started_at', 'closed_at', 'status',
    ];

    protected $casts = [
        'round_no' => 'integer',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(SewingInspectionSheet::class, 'sewing_inspection_sheet_id');
    }
}
