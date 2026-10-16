<?php

namespace App\Services;

use App\Models\CuttingAudit;
use App\Models\CuttingCheckpointResult;
use App\Models\CuttingStage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CuttingDashboardService
{
    public function auditQuery(array $filters = [])
    {
        return CuttingAudit::query()
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('audit_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('audit_date', '<=', $v))
            ->when($filters['buyer_id'] ?? null, fn ($q, $v) => $q->where('buyer_id', $v))
            ->when($filters['cutting_table_id'] ?? null, fn ($q, $v) => $q->where('cutting_table_id', $v))
            ->when($filters['style_number'] ?? null, fn ($q, $v) => $q->where('style_number', 'like', "%{$v}%"))
            ->when($filters['shift'] ?? null, fn ($q, $v) => $q->where('shift', $v));
    }

    public function kpis(array $filters = []): array
    {
        $today = now()->toDateString();

        $auditsToday = CuttingAudit::whereDate('audit_date', $today)->count();

        $layStages = $this->stageQuery('lay', $filters)->get();
        $cutStages = $this->stageQuery('cut_panel', $filters)->get();

        $bundlesIssued = CuttingAudit::query()
            ->when($filters, fn ($q) => $this->applyAuditFilters($q, $filters))
            ->where('status', 'closed')
            ->count();

        return [
            'audits_started_today' => $auditsToday,
            'audits_total' => $this->auditQuery($filters)->count(),
            'lay_approval_pct' => $this->approvalPct($layStages),
            'cut_panel_approval_pct' => $this->approvalPct($cutStages),
            'bundles_issued' => $bundlesIssued,
            'pending_signoffs' => $this->pendingSignoffs($filters)->count(),
        ];
    }

    public function stageCounts(array $filters = []): array
    {
        $counts = [];
        foreach (CuttingAudit::STAGES as $stage) {
            $stages = $this->stageQuery($stage, $filters)->get();
            $counts[$stage] = [
                'approved' => $stages->whereIn('status', ['approved', 'special_approved'])->count(),
                'not_approved' => $stages->where('status', 'not_approved')->count(),
                'special_approval' => $stages->where('status', 'special_approved')->count()
                    + $stages->where('status', 'special_pending')->count(),
                'pending' => $stages->whereIn('status', ['submitted', 'special_pending'])->count(),
            ];
        }

        return $counts;
    }

    public function topNotOkCheckpoints(int $days = 7, array $filters = []): Collection
    {
        $from = now()->subDays($days)->toDateString();

        return CuttingCheckpointResult::query()
            ->select('checkpoint_name', DB::raw('count(*) as total'))
            ->where('result', 'not_ok')
            ->whereHas('stage.audit', function ($q) use ($from, $filters) {
                $q->whereDate('audit_date', '>=', $from);
                $this->applyAuditFilters($q, $filters);
            })
            ->groupBy('checkpoint_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    public function tableStatus(array $filters = []): Collection
    {
        return $this->auditQuery($filters)
            ->with(['cuttingTable', 'stages'])
            ->orderByDesc('audit_date')
            ->limit(30)
            ->get()
            ->map(function (CuttingAudit $audit) {
                return [
                    'audit' => $audit,
                    'table' => $audit->cuttingTable?->table_no ?? '—',
                    'stage' => $audit->current_stage,
                    'status' => $audit->status,
                ];
            });
    }

    public function pendingSignoffs(array $filters = []): Collection
    {
        return CuttingStage::query()
            ->with(['audit.cuttingTable'])
            ->whereIn('status', ['submitted', 'special_pending'])
            ->whereHas('audit', fn ($q) => $this->applyAuditFilters($q, $filters))
            ->get()
            ->map(function (CuttingStage $stage) {
                $workflow = app(CuttingWorkflowService::class);
                $stage->setAttribute('expected_role', $workflow->nextExpectedRole($stage->audit, $stage->stage));
                $stage->setAttribute('waiting_since', $stage->submitted_at ?? $stage->updated_at);

                return $stage;
            })
            ->filter(fn (CuttingStage $stage) => $stage->expected_role !== null)
            ->sortBy('waiting_since')
            ->values();
    }

    protected function stageQuery(string $stage, array $filters)
    {
        return CuttingStage::query()
            ->where('stage', $stage)
            ->whereHas('audit', fn ($q) => $this->applyAuditFilters($q, $filters));
    }

    protected function applyAuditFilters($query, array $filters)
    {
        return $query
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('audit_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('audit_date', '<=', $v))
            ->when($filters['buyer_id'] ?? null, fn ($q, $v) => $q->where('buyer_id', $v))
            ->when($filters['cutting_table_id'] ?? null, fn ($q, $v) => $q->where('cutting_table_id', $v))
            ->when($filters['style_number'] ?? null, fn ($q, $v) => $q->where('style_number', 'like', "%{$v}%"))
            ->when($filters['shift'] ?? null, fn ($q, $v) => $q->where('shift', $v));
    }

    protected function approvalPct(Collection $stages): float
    {
        $resolved = $stages->whereIn('status', [
            'approved', 'special_approved', 'not_approved',
        ]);

        if ($resolved->count() === 0) {
            return 0.0;
        }

        $approved = $stages->whereIn('status', ['approved', 'special_approved'])->count();

        return round(($approved / $resolved->count()) * 100, 1);
    }
}
