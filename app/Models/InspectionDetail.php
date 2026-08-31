<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'fabric_record_id', 'inspected_kg', 'approved_kg', 'rejected_kg',
        'gsm_actual', 'gsm_target', 'width_actual', 'width_target',
        'pass_pct', 'bowing_pct', 'skewing_pct', 'shade_status',
        'inspected_by', 'inspection_date',
    ];

    protected $casts = [
        'inspected_kg' => 'decimal:2',
        'approved_kg' => 'decimal:2',
        'rejected_kg' => 'decimal:2',
        'gsm_actual' => 'decimal:2',
        'gsm_target' => 'decimal:2',
        'width_actual' => 'decimal:2',
        'width_target' => 'decimal:2',
        'pass_pct' => 'decimal:2',
        'bowing_pct' => 'decimal:2',
        'skewing_pct' => 'decimal:2',
        'inspection_date' => 'date',
    ];

    public function fabricRecord(): BelongsTo
    {
        return $this->belongsTo(FabricRecord::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
