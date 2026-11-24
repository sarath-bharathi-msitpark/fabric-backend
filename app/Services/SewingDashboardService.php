<?php

namespace App\Services;

use App\Models\SewingDefectLog;
use App\Models\SewingInspectionResult;
use App\Models\SewingInspectionSheet;
use App\Models\User;
use Illuminate\Support\Collection;

class SewingDashboardService
{
    /**
     * Base sheet query scoped to what the user may see plus the active filters.
     */
    public function sheetQuery(User $user, array $filters)
    {
        return SewingInspectionSheet::query()
            ->visibleTo($user)
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('inspection_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('inspection_date', '<=', $v))
            ->when($filters['sewing_line_id'] ?? null, fn ($q, $v) => $q->where('sewing_line_id', $v))
            ->when($filters['buyer_id'] ?? null, fn ($q, $v) => $q->where('buyer_id', $v))
            ->when($filters['style_number'] ?? null, fn ($q, $v) => $q->where('style_number', 'like', "%{$v}%"))
            ->when($filters['shift'] ?? null, fn ($q, $v) => $q->where('shift', $v));
    }

    public function kpis(User $user, array $filters): array
    {
        $today = now()->toDateString();
        $sheets = $this->sheetQuery($user, $filters);
        $sheetIds = (clone $sheets)->pluck('id');

        $results = SewingInspectionResult::whereIn('sewing_inspection_sheet_id', $sheetIds);
        $defects = SewingDefectLog::whereIn('sewing_inspection_sheet_id', $sheetIds);

        return [
            'sheets_today' => (clone $sheets)->whereDate('inspection_date', $today)->count(),
            'open' => (clone $sheets)->whereIn('status', [SewingInspectionSheet::STATUS_DRAFT, SewingInspectionSheet::STATUS_IN_PROGRESS])->count(),
            'signed' => (clone $sheets)->whereIn('status', [SewingInspectionSheet::STATUS_SIGNED, SewingInspectionSheet::STATUS_LOCKED])->count(),
            'total' => (clone $sheets)->count(),
            'green' => (clone $results)->where('result', SewingInspectionResult::RESULT_GREEN)->count(),
            'amber' => (clone $results)->where('result', SewingInspectionResult::RESULT_AMBER)->count(),
            'red' => (clone $results)->where('result', SewingInspectionResult::RESULT_RED)->count(),
            'open_reaudits' => (clone $results)->where('result', SewingInspectionResult::RESULT_RED)->whereNull('re_audit_result')->count(),
            'escalations' => (clone $results)->where('re_audit_result', SewingInspectionResult::REAUDIT_ESCALATED)->count(),
            'total_defects' => (int) (clone $defects)->sum('qty'),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function topDefects(User $user, array $filters, int $limit = 8)
    {
        $sheetIds = $this->sheetQuery($user, $filters)->pluck('id');

        return SewingDefectLog::query()
            ->whereIn('sewing_inspection_sheet_id', $sheetIds)
            ->selectRaw('capa_code, defect_name, defect_type, sum(qty) as total, count(*) as occurrences')
            ->groupBy('capa_code', 'defect_name', 'defect_type')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /**
     * Green/Amber/Red cell counts grouped by line.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function lineStatus(User $user, array $filters)
    {
        $sheetIds = $this->sheetQuery($user, $filters)->pluck('id');

        return SewingInspectionResult::with('sheet.line')
            ->whereIn('sewing_inspection_sheet_id', $sheetIds)
            ->get()
            ->groupBy(fn (SewingInspectionResult $r) => $r->sheet->line->line_no ?? 'Unassigned')
            ->map(fn ($group, $line) => [
                'line' => $line,
                'cells' => $group->count(),
                'green' => $group->where('result', SewingInspectionResult::RESULT_GREEN)->count(),
                'amber' => $group->where('result', SewingInspectionResult::RESULT_AMBER)->count(),
                'red' => $group->where('result', SewingInspectionResult::RESULT_RED)->count(),
            ])
            ->sortBy('line')
            ->values();
    }

    /**
     * Red cells still awaiting their mandatory re-audit.
     *
     * @return Collection<int, SewingInspectionResult>
     */
    public function pendingReaudits(User $user, array $filters, int $limit = 20)
    {
        $sheetIds = $this->sheetQuery($user, $filters)->pluck('id');

        return SewingInspectionResult::with(['sheet', 'operation'])
            ->whereIn('sewing_inspection_sheet_id', $sheetIds)
            ->where('result', SewingInspectionResult::RESULT_RED)
            ->whereNull('re_audit_result')
            ->orderByDesc('inspected_at')
            ->limit($limit)
            ->get();
    }
}
