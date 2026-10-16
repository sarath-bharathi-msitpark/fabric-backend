<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuttingCheckpointResult extends Model
{
    protected $fillable = [
        'cutting_stage_id', 'cutting_checkpoint_id', 'checkpoint_name',
        'result', 'remarks', 'sort_order',
    ];

    protected $casts = ['sort_order' => 'integer'];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CuttingStage::class, 'cutting_stage_id');
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(CuttingCheckpoint::class, 'cutting_checkpoint_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CuttingCheckpointPhoto::class, 'cutting_checkpoint_result_id');
    }
}
