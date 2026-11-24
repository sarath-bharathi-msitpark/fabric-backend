<?php

namespace App\Models;

use App\Models\Concerns\LogsSewingAudit;
use Illuminate\Database\Eloquent\Model;

class SewingInspectionRule extends Model
{
    use LogsSewingAudit;

    public const DEFAULT_PIECES = 7;

    public const DEFAULT_GREEN_MAX = 0;

    public const DEFAULT_AMBER_MAX = 2;

    public const DEFAULT_ROUNDS = 4;

    protected $fillable = [
        'version', 'pieces_per_audit', 'green_max', 'amber_max', 'default_rounds', 'is_active',
    ];

    protected $casts = [
        'version' => 'integer',
        'pieces_per_audit' => 'integer',
        'green_max' => 'integer',
        'amber_max' => 'integer',
        'default_rounds' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function current(): self
    {
        return static::where('is_active', true)->latest('version')->first()
            ?? new static([
                'version' => 1,
                'pieces_per_audit' => self::DEFAULT_PIECES,
                'green_max' => self::DEFAULT_GREEN_MAX,
                'amber_max' => self::DEFAULT_AMBER_MAX,
                'default_rounds' => self::DEFAULT_ROUNDS,
            ]);
    }

    /**
     * Derive the Green/Amber/Red result for a defect count under these rules.
     */
    public function resultForCount(int $count): string
    {
        if ($count <= $this->green_max) {
            return SewingInspectionResult::RESULT_GREEN;
        }

        if ($count <= $this->amber_max) {
            return SewingInspectionResult::RESULT_AMBER;
        }

        return SewingInspectionResult::RESULT_RED;
    }
}
