<?php

namespace App\Models;

use App\Models\Concerns\LogsSewingAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SewingDefectMaster extends Model
{
    use LogsSewingAudit;

    public const TYPE_SEWING = 'sewing';

    public const TYPE_FABRIC = 'fabric';

    public const TYPE_CUTTING = 'cutting';

    public const TYPE_TRIMS = 'trims';

    public const TYPES = [
        self::TYPE_SEWING => 'Sewing',
        self::TYPE_FABRIC => 'Fabric',
        self::TYPE_CUTTING => 'Cutting',
        self::TYPE_TRIMS => 'Trims',
    ];

    protected $fillable = [
        'defect_type', 'capa_code', 'defect_name', 'root_cause',
        'corrective_action', 'preventive_action', 'defect_mock_path', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function logs(): HasMany
    {
        return $this->hasMany(SewingDefectLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
