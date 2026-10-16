<?php

namespace App\Services;

use App\Models\CuttingAudit;
use App\Models\CuttingCheckpointResult;
use App\Models\CuttingSignoff;
use App\Models\CuttingSpecialApproval;
use App\Models\CuttingTable;
use Illuminate\Support\Facades\DB;

class CuttingReportService
{
    public function __construct(protected CuttingDashboardService $dashboard) {}

    public function defectAnalysis(array $filters = [])
    {
        return CuttingCheckpointResult::query()
            ->select(
                'checkpoint_name',
                DB::raw('count(*) as not_ok_count'),
                DB::raw('max(stage.stage) as stage')
            )
            ->join('cutting_stages as stage', 'stage.id', '=', 'cutting_checkpoint_results.cutting_stage_id')
            ->join('cutting_audits as audit', 'audit.id', '=', 'stage.cutting_audit_id')
            ->where('cutting_checkpoint_results.result', 'not_ok')
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('audit.audit_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('audit.audit_date', '<=', $v))
            ->when($filters['buyer_id'] ?? null, fn ($q, $v) => $q->where('audit.buyer_id', $v))
            ->when($filters['cutting_table_id'] ?? null, fn ($q, $v) => $q->where('audit.cutting_table_id', $v))
            ->when($filters['shift'] ?? null, fn ($q, $v) => $q->where('audit.shift', $v))
            ->groupBy('cutting_checkpoint_results.checkpoint_name', 'stage.stage')
            ->orderByDesc('not_ok_count')
            ->get();
    }

    public function specialApprovalLog(array $filters = [])
    {
        return CuttingSpecialApproval::query()
            ->with(['audit.cuttingTable', 'audit.buyer', 'requester', 'approver'])
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereHas('audit', fn ($a) => $a->whereDate('audit_date', '>=', $v)))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereHas('audit', fn ($a) => $a->whereDate('audit_date', '<=', $v)))
            ->when($filters['buyer_id'] ?? null, fn ($q, $v) => $q->whereHas('audit', fn ($a) => $a->where('buyer_id', $v)))
            ->when($filters['cutting_table_id'] ?? null, fn ($q, $v) => $q->whereHas('audit', fn ($a) => $a->where('cutting_table_id', $v)))
            ->orderByDesc('created_at')
            ->get();
    }

    public function signoffTurnaround(array $filters = [])
    {
        return CuttingSignoff::query()
            ->with(['audit.cuttingTable'])
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereHas('audit', fn ($a) => $a->whereDate('audit_date', '>=', $v)))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereHas('audit', fn ($a) => $a->whereDate('audit_date', '<=', $v)))
            ->when($filters['buyer_id'] ?? null, fn ($q, $v) => $q->whereHas('audit', fn ($a) => $a->where('buyer_id', $v)))
            ->orderByDesc('signed_at')
            ->get()
            ->map(function (CuttingSignoff $signoff) {
                $stage = $signoff->stage()->first();
                $submitted = $stage?->submitted_at;
                $signoff->setAttribute(
                    'turnaround_minutes',
                    $submitted ? max(0, $submitted->diffInMinutes($signoff->signed_at)) : null
                );

                return $signoff;
            });
    }

    public function tableWisePerformance(array $filters = [])
    {
        $tables = CuttingTable::orderBy('table_no')->get();

        return $tables->map(function ($table) use ($filters) {
            $audits = $this->dashboard->auditQuery($filters)
                ->where('cutting_table_id', $table->id)
                ->with('stages')
                ->get();

            $layStages = $audits->flatMap->stages->where('stage', 'lay');
            $cutStages = $audits->flatMap->stages->where('stage', 'cut_panel');
            $resolvedLay = $layStages->whereIn('status', ['approved', 'special_approved', 'not_approved'])->count();
            $approvedLay = $layStages->whereIn('status', ['approved', 'special_approved'])->count();

            return [
                'table' => $table->table_no,
                'audits' => $audits->count(),
                'lay_approval_rate' => $resolvedLay > 0 ? round(($approvedLay / $resolvedLay) * 100, 1) : 0,
                'cut_panel_approval_rate' => $cutStages->count() > 0
                    ? round(($cutStages->whereIn('status', ['approved', 'special_approved'])->count() / max(1, $cutStages->whereIn('status', ['approved', 'special_approved', 'not_approved'])->count())) * 100, 1)
                    : 0,
                'rework' => $audits->flatMap->stages->where('status', 'not_approved')->count(),
            ];
        });
    }

    public function auditReport(CuttingAudit $audit): array
    {
        $audit->load([
            'cuttingTable', 'buyer', 'creator', 'sizePatterns', 'bundles',
            'stages.results.photos', 'stages.signoffs', 'signoffs', 'specialApprovals',
        ]);

        return ['audit' => $audit];
    }
}
