<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SewingInspectionOperation extends Model
{
    protected $fillable = [
        'sewing_inspection_sheet_id', 'sewing_operation_id', 'sewing_machine_id', 'sewing_operator_id',
        'operation_name', 'machine_no', 'sequence_no', 'note',
    ];

    protected $casts = ['sequence_no' => 'integer'];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(SewingInspectionSheet::class, 'sewing_inspection_sheet_id');
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(SewingOperation::class, 'sewing_operation_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(SewingMachine::class, 'sewing_machine_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(SewingOperator::class, 'sewing_operator_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(SewingInspectionResult::class);
    }

    public function resultForRound(int $round): ?SewingInspectionResult
    {
        return $this->results->firstWhere('round_no', $round);
    }
}
