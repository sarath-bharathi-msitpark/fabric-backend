<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Style extends Model
{
    use HasFactory;

    protected $fillable = ['style_number', 'buyer_id', 'order_quantity', 'target_date', 'status'];

    protected $casts = [
        'order_quantity' => 'decimal:2',
        'target_date' => 'date',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function fabricRecords(): HasMany
    {
        return $this->hasMany(FabricRecord::class);
    }
}
