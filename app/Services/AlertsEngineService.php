<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\FabricRecord;
use App\Models\Supplier;

class AlertsEngineService
{
    public function scan(?int $recordId = null): int
    {
        $created = 0;
        $query = FabricRecord::with(['inspection', 'style', 'supplier'])->whereHas('inspection');

        if ($recordId) {
            $query->where('id', $recordId);
        }

        $query->chunk(200, function ($records) use (&$created) {
            foreach ($records as $record) {
                $created += $this->scanRecord($record) ? 1 : 0;
            }
        });

        $this->scanSupplierQuality();
        return $created;
    }

    protected function scanRecord(FabricRecord $record): bool
    {
        $insp = $record->inspection;
        $style = $record->style;
        $made = false;
        $today = now()->startOfDay();
        $targetDate = $style?->target_date ? \Illuminate\Support\Carbon::parse($style->target_date)->startOfDay() : null;

        if ($targetDate && $record->received_kg < $record->ordered_kg) {
            if ($targetDate < $today && $style->status !== 'completed') {
                $made |= $this->createAlert(
                    $record,
                    'delay',
                    'red',
                    "Lot {$record->lot_no} delayed: target date {$targetDate->toDateString()} passed with shortfall of " . number_format((float)$record->ordered_kg - (float)$record->received_kg, 2) . " kg."
                );
            } elseif ($targetDate->diffInDays($today) <= 3 && $targetDate >= $today) {
                $made |= $this->createAlert(
                    $record,
                    'delay',
                    'yellow',
                    "Lot {$record->lot_no} approaching target date {$targetDate->toDateString()} (within 3 days) with incomplete delivery."
                );
            }
        }

        if ($insp && $insp->inspected_kg > 0) {
            $rejPct = ((float)$insp->rejected_kg / (float)$insp->inspected_kg) * 100;
            if ($rejPct > 2) {
                $made |= $this->createAlert(
                    $record,
                    'rejection',
                    'red',
                    "Lot {$record->lot_no} rejection rate " . round($rejPct, 2) . "% exceeds 2% target."
                );
            }
            if ((float)$insp->pass_pct < 96) {
                $made |= $this->createAlert(
                    $record,
                    'quality',
                    'yellow',
                    "Lot {$record->lot_no} pass rate " . $insp->pass_pct . "% is below 96% threshold."
                );
            }
        }

        if ($insp && $insp->shade_status === 'rejected') {
            $made |= $this->createAlert(
                $record,
                'shade',
                'red',
                "Lot {$record->lot_no} shade rejected — rework/resubmission required."
            );
        }

        return $made;
    }

    protected function scanSupplierQuality(): void
    {
        $suppliers = Supplier::where('is_active', true)->where('quality_pct', '<', 90)->get();
        foreach ($suppliers as $supplier) {
            $exists = Alert::where('supplier_id', $supplier->id)
                ->where('alert_type', 'quality')
                ->where('is_resolved', false)
                ->exists();
            if (!$exists) {
                Alert::create([
                    'supplier_id' => $supplier->id,
                    'alert_type' => 'quality',
                    'severity' => 'red',
                    'message' => "Supplier {$supplier->supplier_name} quality dropped to {$supplier->quality_pct}% (below 90%).",
                ]);
            }
        }
    }

    protected function createAlert(FabricRecord $record, string $type, string $severity, string $message): bool
    {
        $exists = Alert::where('fabric_record_id', $record->id)
            ->where('alert_type', $type)
            ->where('is_resolved', false)
            ->exists();
        if ($exists) {
            return false;
        }
        Alert::create([
            'fabric_record_id' => $record->id,
            'supplier_id' => $record->supplier_id,
            'alert_type' => $type,
            'severity' => $severity,
            'message' => $message,
        ]);
        return true;
    }
}
