<?php

namespace App\Models;

use App\Models\Concerns\LogsSewingAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SewingOperator extends Model
{
    use LogsSewingAudit;

    protected $fillable = ['employee_code', 'name', 'sewing_line_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function line(): BelongsTo
    {
        return $this->belongsTo(SewingLine::class, 'sewing_line_id');
    }
}
