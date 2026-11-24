<?php

namespace App\Models;

use App\Models\Concerns\LogsSewingAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SewingLine extends Model
{
    use LogsSewingAudit;

    protected $fillable = ['line_no', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function operators(): HasMany
    {
        return $this->hasMany(SewingOperator::class);
    }

    public function sheets(): HasMany
    {
        return $this->hasMany(SewingInspectionSheet::class);
    }
}
