<?php

namespace App\Models;

use App\Models\Concerns\LogsSewingAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SewingOperation extends Model
{
    use LogsSewingAudit;

    protected $fillable = ['name', 'default_machine_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function defaultMachine(): BelongsTo
    {
        return $this->belongsTo(SewingMachine::class, 'default_machine_id');
    }

    public function styleOperations(): HasMany
    {
        return $this->hasMany(SewingStyleOperation::class);
    }
}
