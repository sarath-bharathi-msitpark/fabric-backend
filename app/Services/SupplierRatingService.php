<?php

namespace App\Services;

use App\Models\FabricRecord;
use App\Models\Supplier;
use Illuminate\Support\Carbon;

class SupplierRatingService
{
    public function recalculateAll(): int
    {
        $count = 0;
        Supplier::chunk(200, function ($suppliers) use (&$count) {
            foreach ($suppliers as $supplier) {
                $this->recalculate($supplier);
                $count++;
            }
        });
        return $count;
    }

    public function recalculate(Supplier $supplier): void
    {
        $records = FabricRecord::where('supplier_id', $supplier->id)
            ->whereHas('inspection')
            ->with('inspection', 'style')
            ->get();

        if ($records->isEmpty()) {
            return;
        }

        $totalLots = $records->count();
        $onTimeLots = $records->filter(function ($r) {
            $target = $r->style?->target_date;
            return $target && Carbon::parse($r->record_date)->lte(Carbon::parse($target));
        })->count();

        $onTimePct = $totalLots > 0 ? round(($onTimeLots / $totalLots) * 100, 2) : 0;

        $inspected = (float) $records->sum(fn ($r) => (float) $r->inspection->inspected_kg);
        $approved = (float) $records->sum(fn ($r) => (float) $r->inspection->approved_kg);
        $qualityPct = $inspected > 0 ? round(($approved / $inspected) * 100, 2) : 0;

        $rating = $this->computeRating($qualityPct, $onTimePct);

        $supplier->update([
            'on_time_pct' => $onTimePct,
            'quality_pct' => $qualityPct,
            'rating' => $rating,
        ]);
    }

    protected function computeRating(float $quality, float $onTime): string
    {
        if ($quality >= 98 && $onTime >= 95) return 'excellent';
        if ($quality >= 95 && $onTime >= 90) return 'good';
        if ($quality >= 90 && $onTime >= 80) return 'average';
        return 'poor';
    }
}
