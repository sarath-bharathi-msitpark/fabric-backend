<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityDefect extends Model
{
    use HasFactory;

    protected $fillable = ['fabric_record_id', 'defect_type', 'count', 'severity', 'notes'];

    protected $casts = ['count' => 'integer'];

    public function fabricRecord(): BelongsTo
    {
        return $this->belongsTo(FabricRecord::class);
    }
}
