<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityDefect extends Model
{
    use HasFactory;

    protected $fillable = ['fabric_record_id', 'inspection_roll_id', 'defect_type', 'count', 'metre_position', 'points', 'defect_size', 'severity', 'notes'];

    protected $casts = ['count' => 'integer', 'metre_position' => 'integer', 'points' => 'integer'];

    public function fabricRecord(): BelongsTo
    {
        return $this->belongsTo(FabricRecord::class);
    }

    public function inspectionRoll(): BelongsTo
    {
        return $this->belongsTo(InspectionRoll::class);
    }
}
