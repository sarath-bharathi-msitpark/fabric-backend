<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionRoll extends Model
{
    use HasFactory;

    protected $fillable = [
        'fabric_record_id', 'roll_no', 'color', 'weight_kgs',
        'width_front', 'width_middle', 'width_end', 'gsm',
        'roll_length_yards', 'points_per_100_sq_yd', 'result', 'remarks',
    ];

    protected $casts = [
        'weight_kgs' => 'decimal:3',
        'width_front' => 'decimal:2',
        'width_middle' => 'decimal:2',
        'width_end' => 'decimal:2',
        'gsm' => 'decimal:2',
        'roll_length_yards' => 'decimal:1',
        'points_per_100_sq_yd' => 'decimal:1',
    ];

    public function fabricRecord(): BelongsTo
    {
        return $this->belongsTo(FabricRecord::class);
    }

    public function defects(): HasMany
    {
        return $this->hasMany(QualityDefect::class);
    }

    public function avgWidth(): float
    {
        $vals = array_filter([(float)$this->width_front, (float)$this->width_middle, (float)$this->width_end], fn($v) => $v > 0);
        return count($vals) ? round(array_sum($vals) / count($vals), 2) : 0;
    }

    public function totalPoints(): int
    {
        return (int) $this->defects()->sum('points');
    }

    public function recalculate(): void
    {
        $width = $this->avgWidth();
        $gsm = (float) $this->gsm;
        $weight = (float) $this->weight_kgs;

        if ($gsm > 0 && $width > 0 && $weight > 0) {
            $this->roll_length_yards = round(($weight * 1000 * 100 * 1.0936) / ($gsm * $width * 2.54 * 0.9144), 1);
        }

        $totalPoints = $this->totalPoints();
        $yards = (float) $this->roll_length_yards;
        if ($yards > 0 && $width > 0) {
            $this->points_per_100_sq_yd = round(($totalPoints * 3600) / ($yards * $width), 1);
        } else {
            $this->points_per_100_sq_yd = 0;
        }

        $this->result = (float) $this->points_per_100_sq_yd <= 20 ? 'pass' : 'fail';
        $this->save();
    }
}
