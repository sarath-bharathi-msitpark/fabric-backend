<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FabricRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_date', 'buyer_id', 'style_id', 'supplier_id', 'lot_no',
        'fabric_type', 'color', 'ordered_kg', 'received_kg',
        'uploaded_by', 'upload_batch_id',
    ];

    protected $casts = [
        'record_date' => 'date',
        'ordered_kg' => 'decimal:2',
        'received_kg' => 'decimal:2',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function style(): BelongsTo
    {
        return $this->belongsTo(Style::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function uploadBatch(): BelongsTo
    {
        return $this->belongsTo(UploadBatch::class);
    }

    public function inspection(): HasOne
    {
        return $this->hasOne(InspectionDetail::class);
    }

    public function defects(): HasMany
    {
        return $this->hasMany(QualityDefect::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
