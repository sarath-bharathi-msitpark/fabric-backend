<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuttingChecklistTemplate extends Model
{
    protected $fillable = ['stage', 'version', 'signoff_roles', 'is_active'];

    protected $casts = [
        'signoff_roles' => 'array',
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    public function checkpoints(): HasMany
    {
        return $this->hasMany(CuttingCheckpoint::class)->orderBy('sort_order');
    }

    public function activeCheckpoints(): HasMany
    {
        return $this->hasMany(CuttingCheckpoint::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public static function currentFor(string $stage): ?self
    {
        return static::where('stage', $stage)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }
}
