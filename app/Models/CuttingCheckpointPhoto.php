<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingCheckpointPhoto extends Model
{
    protected $fillable = ['cutting_checkpoint_result_id', 'path'];

    public function result(): BelongsTo
    {
        return $this->belongsTo(CuttingCheckpointResult::class, 'cutting_checkpoint_result_id');
    }
}
