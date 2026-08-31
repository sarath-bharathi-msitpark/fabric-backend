<?php

namespace App\Services;

use App\Models\KpiTarget;
use App\Models\FabricRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class KpiService
{
    protected array $filters = [];

    public function calculate(array $filters = []): array
    {
        $this->filters = $filters;

        return [
            'total_required' => $this->totalRequired(),
            'total_received' => $this->totalReceived(),
            'total_approved' => $this->totalApproved(),
            'inspection_completed' => $this->inspectionCompleted(),
            'pass_rate' => $this->passRate(),
            'rejection_rate' => $this->rejectionRate(),
            'available_for_cutting' => $this->availableForCutting(),
            'shade_approval' => $this->shadeApproval(),
            'delayed_lots' => $this->delayedLots(),
        ];
    }

    protected function baseQuery(): Builder
    {
        return FabricRecord::query()->with('inspection', 'style')
            ->when($this->filters['buyer_id'] ?? null, fn ($q, $v) => $q->where('fabric_records.buyer_id', $v))
            ->when($this->filters['style_id'] ?? null, fn ($q, $v) => $q->where('fabric_records.style_id', $v))
            ->when($this->filters['supplier_id'] ?? null, fn ($q, $v) => $q->where('fabric_records.supplier_id', $v))
            ->when($this->filters['fabric_type'] ?? null, fn ($q, $v) => $q->where('fabric_records.fabric_type', $v))
            ->when($this->filters['color'] ?? null, fn ($q, $v) => $q->where('fabric_records.color', $v))
            ->when($this->filters['from'] ?? null, fn ($q, $v) => $q->whereDate('fabric_records.record_date', '>=', $v))
            ->when($this->filters['to'] ?? null, fn ($q, $v) => $q->whereDate('fabric_records.record_date', '<=', $v));
    }

    public function totalRequired(): array
    {
        $value = (float) $this->baseQuery()->sum('ordered_kg');
        return ['value' => $value, 'status' => 'green', 'unit' => 'kg', 'label' => 'Total Fabric Required'];
    }

    public function totalReceived(): array
    {
        $value = (float) $this->baseQuery()->sum('received_kg');
        return ['value' => $value, 'status' => 'green', 'unit' => 'kg', 'label' => 'Total Fabric Received'];
    }

    public function totalApproved(): array
    {
        $value = (float) $this->baseQuery()
            ->join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->sum('inspection_details.approved_kg');
        return ['value' => $value, 'status' => 'green', 'unit' => 'kg', 'label' => 'Total Approved'];
    }

    public function inspectionCompleted(): array
    {
        $received = (float) $this->baseQuery()->sum('received_kg');
        $inspected = (float) $this->baseQuery()
            ->join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->sum('inspection_details.inspected_kg');
        $value = $received > 0 ? round(($inspected / $received) * 100, 2) : 0;
        return $this->withStatus('inspection_completed', $value, '%', 'Inspection Completed %');
    }

    public function passRate(): array
    {
        $inspected = (float) $this->baseQuery()
            ->join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->sum('inspection_details.inspected_kg');
        $approved = (float) $this->baseQuery()
            ->join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->sum('inspection_details.approved_kg');
        $value = $inspected > 0 ? round(($approved / $inspected) * 100, 2) : 0;
        return $this->withStatus('pass_rate', $value, '%', 'Fabric Pass Rate %');
    }

    public function rejectionRate(): array
    {
        $inspected = (float) $this->baseQuery()
            ->join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->sum('inspection_details.inspected_kg');
        $rejected = (float) $this->baseQuery()
            ->join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->sum('inspection_details.rejected_kg');
        $value = $inspected > 0 ? round(($rejected / $inspected) * 100, 2) : 0;
        return $this->withStatus('rejection_rate', $value, '%', 'Rejection Rate %');
    }

    public function availableForCutting(): array
    {
        $ordered = (float) $this->baseQuery()->sum('ordered_kg');
        $approved = (float) $this->baseQuery()
            ->join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->sum('inspection_details.approved_kg');
        $value = $ordered > 0 ? round(($approved / $ordered) * 100, 2) : 0;
        return $this->withStatus('available_for_cutting', $value, '%', 'Available for Cutting %');
    }

    public function shadeApproval(): array
    {
        $total = (int) $this->baseQuery()
            ->join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->count();
        $approved = (int) $this->baseQuery()
            ->join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->where('inspection_details.shade_status', 'approved')
            ->count();
        $value = $total > 0 ? round(($approved / $total) * 100, 2) : 0;
        return $this->withStatus('shade_approval', $value, '%', 'Shade Approval %');
    }

    public function delayedLots(): array
    {
        $today = now()->toDateString();
        $value = (int) $this->baseQuery()
            ->whereColumn('received_kg', '<', 'ordered_kg')
            ->whereHas('style', fn ($q) => $q->whereDate('target_date', '<', $today)->where('status', '!=', 'completed'))
            ->count();
        return $this->withStatus('delayed_lots', $value, '', 'Delayed Lots');
    }

    protected function withStatus(string $kpiKey, float|int $value, string $unit, string $label): array
    {
        $target = KpiTarget::where('kpi_key', $kpiKey)->first();
        $status = 'green';
        if ($target) {
            $status = self::statusColor((float) $value, (float) $target->target_value, $target->comparison);
        }
        return ['value' => $value, 'status' => $status, 'unit' => $unit, 'label' => $label, 'target' => $target?->target_value];
    }

    public static function statusColor(float $value, float $target, string $comparison): string
    {
        $met = match ($comparison) {
            'gte' => $value >= $target,
            'lte' => $value <= $target,
            'eq' => abs($value - $target) < 0.01,
            default => false,
        };

        if ($met) {
            return 'green';
        }

        $lowerIsBetter = $comparison === 'lte';
        if ($target == 0) {
            return $value > 0 ? 'red' : 'green';
        }
        $deviationPct = abs(($value - $target) / $target) * 100;

        if ($lowerIsBetter) {
            return $value > $target ? 'red' : 'green';
        }

        return $deviationPct <= 10 ? 'yellow' : 'red';
    }

    public function qualityMetricsFor(FabricRecord $record): array
    {
        $insp = $record->inspection;
        if (!$insp) {
            return [];
        }

        $gsmVar = $insp->gsm_target ? abs((float)$insp->gsm_actual - (float)$insp->gsm_target) / (float)$insp->gsm_target * 100 : 0;
        $widthVar = abs((float)$insp->width_actual - (float)$insp->width_target);

        return [
            'gsm_variation_pct' => ['value' => round($gsmVar, 2), 'target' => 5, 'status' => self::statusColor($gsmVar, 5, 'lte')],
            'width_variation_cm' => ['value' => round($widthVar, 2), 'target' => 1, 'status' => self::statusColor($widthVar, 1, 'lte')],
            'bowing_pct' => ['value' => (float)$insp->bowing_pct, 'target' => 3, 'status' => self::statusColor((float)$insp->bowing_pct, 3, 'lte')],
            'skewing_pct' => ['value' => (float)$insp->skewing_pct, 'target' => 3, 'status' => self::statusColor((float)$insp->skewing_pct, 3, 'lte')],
            'hole_defects' => ['value' => (int) $record->defects()->where('defect_type', 'Hole')->sum('count'), 'target' => 0, 'status' => 'green'],
        ];
    }

    public function trendData(int $days = 30): Collection
    {
        return $this->baseQuery()
            ->selectRaw('DATE(fabric_records.record_date) as d, SUM(fabric_records.received_kg) as total')
            ->where('fabric_records.record_date', '>=', now()->subDays($days)->toDateString())
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->pluck('total', 'd');
    }

    public function statusBreakdown(): array
    {
        $rows = $this->baseQuery()
            ->join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->selectRaw('COALESCE(SUM(inspection_details.approved_kg),0) as approved, COALESCE(SUM(inspection_details.rejected_kg),0) as rejected, COALESCE(SUM(fabric_records.received_kg) - SUM(inspection_details.inspected_kg),0) as pending')
            ->first();

        return [
            'pending' => (float) max(0, $rows->pending ?? 0),
            'approved' => (float) $rows->approved ?? 0,
            'rejected' => (float) $rows->rejected ?? 0,
        ];
    }

    public function stockByFabricType(): Collection
    {
        return $this->baseQuery()
            ->join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->selectRaw('fabric_records.fabric_type, SUM(fabric_records.received_kg) as received, SUM(inspection_details.approved_kg) as approved, SUM(inspection_details.rejected_kg) as rejected')
            ->groupBy('fabric_records.fabric_type')
            ->get();
    }

    public function topDefects(int $limit = 10): Collection
    {
        return \App\Models\QualityDefect::query()
            ->join('fabric_records', 'quality_defects.fabric_record_id', '=', 'fabric_records.id')
            ->when($this->filters['buyer_id'] ?? null, fn ($q, $v) => $q->where('fabric_records.buyer_id', $v))
            ->when($this->filters['style_id'] ?? null, fn ($q, $v) => $q->where('fabric_records.style_id', $v))
            ->when($this->filters['supplier_id'] ?? null, fn ($q, $v) => $q->where('fabric_records.supplier_id', $v))
            ->when($this->filters['fabric_type'] ?? null, fn ($q, $v) => $q->where('fabric_records.fabric_type', $v))
            ->when($this->filters['color'] ?? null, fn ($q, $v) => $q->where('fabric_records.color', $v))
            ->when($this->filters['from'] ?? null, fn ($q, $v) => $q->whereDate('fabric_records.record_date', '>=', $v))
            ->when($this->filters['to'] ?? null, fn ($q, $v) => $q->whereDate('fabric_records.record_date', '<=', $v))
            ->selectRaw('defect_type, SUM(count) as total')
            ->groupBy('defect_type')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    public function consumptionVsPlan(): Collection
    {
        return $this->baseQuery()
            ->join('styles', 'fabric_records.style_id', '=', 'styles.id')
            ->join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->selectRaw('styles.style_number, MAX(styles.order_quantity) as planned, SUM(inspection_details.approved_kg) as actual')
            ->groupBy('styles.style_number')
            ->get();
    }
}
