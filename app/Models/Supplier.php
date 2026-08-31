<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_name', 'mill_code', 'contact_person', 'phone', 'email',
        'on_time_pct', 'quality_pct', 'rating', 'is_active',
    ];

    protected $casts = [
        'on_time_pct' => 'decimal:2',
        'quality_pct' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function fabricRecords(): HasMany
    {
        return $this->hasMany(FabricRecord::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
