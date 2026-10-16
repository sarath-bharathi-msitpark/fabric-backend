<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuttingTable extends Model
{
    protected $fillable = ['table_no', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function audits(): HasMany
    {
        return $this->hasMany(CuttingAudit::class);
    }
}
