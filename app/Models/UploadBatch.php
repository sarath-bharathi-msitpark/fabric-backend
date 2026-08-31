<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name', 'upload_type', 'uploaded_by',
        'total_rows', 'success_rows', 'error_rows', 'status', 'error_log',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'success_rows' => 'integer',
        'error_rows' => 'integer',
        'error_log' => 'array',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function fabricRecords(): HasMany
    {
        return $this->hasMany(FabricRecord::class);
    }
}
