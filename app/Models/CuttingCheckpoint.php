<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingCheckpoint extends Model
{
    protected $fillable = [
        'cutting_checklist_template_id', 'name', 'description', 'sort_order',
        'mandatory_remarks_on_not_ok', 'blocks_approval', 'is_active',
    ];

    protected $casts = [
        'mandatory_remarks_on_not_ok' => 'boolean',
        'blocks_approval' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(CuttingChecklistTemplate::class, 'cutting_checklist_template_id');
    }
}
