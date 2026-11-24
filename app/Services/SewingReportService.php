<?php

namespace App\Services;

use App\Models\SewingDefectLog;
use App\Models\SewingInspectionResult;
use App\Models\SewingInspectionSheet;
use App\Models\SewingSignoff;
use App\Models\User;

class SewingReportService
{
    /**
     * @return array{0: array<int, string>, 1: array<int, array<int, mixed>>}
     */
    public function build(string $type, User $user, array $filters): array
    {
        return match ($type) {
            'inspection-register' => $this->inspectionRegister($user, $filters),
            'defect-analysis' => $this->defectAnalysis($user, $filters),
            're-audit' => $this->reAuditLog($user, $filters),
            'repeat-red' => $this->repeatRed($user, $filters),
            'signoff-turnaround' => $this->signoffTurnaround($user, $filters),
            'line-performance' => $this->linePerformance($user, $filters),
            default => [[], []],
        };
    }

    protected function sheets(User $user, array $filters)
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

    protected function inspectionRegister(User $user, array $filters): array
    {
        $headings = ['Sheet No', 'Date', 'Line', 'Buyer', 'Style', 'PO', 'QC', 'Status', 'Round', 'Defects'];
        $rows = $this->sheets($user, $filters)
            ->with(['line', 'buyer', 'qc'])
            ->withCount('defectLogs')
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (SewingInspectionSheet $s) => [
                $s->sheet_no,
                optional($s->inspection_date)->format('Y-m-d'),
                $s->line->line_no ?? '',
                $s->buyer->buyer_name ?? '',
                $s->style_number ?? '',
                $s->po_number ?? '',
                $s->qc->name ?? '',
                ucfirst(str_replace('_', ' ', $s->status)),
                $s->current_round,
                $s->defect_logs_count,
            ])->all();

        return [$headings, $rows];
    }

    protected function defectAnalysis(User $user, array $filters): array
    {
        $headings = ['CAPA Code', 'Defect', 'Type', 'Occurrences', 'Total Qty'];
        $sheetIds = $this->sheets($user, $filters)->pluck('id');
        $rows = SewingDefectLog::query()
            ->whereIn('sewing_inspection_sheet_id', $sheetIds)
            ->selectRaw('capa_code, defect_name, defect_type, count(*) as occurrences, sum(qty) as total')
            ->groupBy('capa_code', 'defect_name', 'defect_type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [$r->capa_code, $r->defect_name, $r->defect_type, $r->occurrences, (int) $r->total])
            ->all();

        return [$headings, $rows];
    }

    protected function reAuditLog(User $user, array $filters): array
    {
        $headings = ['Sheet', 'Operation', 'Round', 'Defects', 'Re-audit Count', 'Outcome', 'Re-audited At'];
        $sheetIds = $this->sheets($user, $filters)->pluck('id');
        $rows = SewingInspectionResult::with(['sheet', 'operation'])
            ->whereIn('sewing_inspection_sheet_id', $sheetIds)
            ->whereNotNull('re_audit_result')
            ->orderByDesc('re_audited_at')
            ->get()
            ->map(fn (SewingInspectionResult $r) => [
                $r->sheet->sheet_no ?? '',
                $r->operation->operation_name ?? '',
                $r->round_no,
                $r->defect_count,
                $r->re_audit_count,
                ucfirst($r->re_audit_result),
                optional($r->re_audited_at)->format('Y-m-d H:i'),
            ])->all();

        return [$headings, $rows];
    }

    protected function repeatRed(User $user, array $filters): array
    {
        $headings = ['Operator', 'Employee Code', 'Red Cells', 'Escalated'];
        $sheetIds = $this->sheets($user, $filters)->pluck('id');
        $rows = SewingInspectionResult::query()
            ->join('sewing_inspection_operations as o', 'o.id', '=', 'sewing_inspection_results.sewing_inspection_operation_id')
            ->leftJoin('sewing_operators as op', 'op.id', '=', 'o.sewing_operator_id')
            ->whereIn('sewing_inspection_results.sewing_inspection_sheet_id', $sheetIds)
            ->where('sewing_inspection_results.result', SewingInspectionResult::RESULT_RED)
            ->selectRaw('op.name as operator_name, op.employee_code, count(*) as red_cells')
            ->selectRaw('sum(case when sewing_inspection_results.re_audit_result = ? then 1 else 0 end) as escalated', [SewingInspectionResult::REAUDIT_ESCALATED])
            ->groupBy('op.name', 'op.employee_code')
            ->havingRaw('count(*) >= 2')
            ->orderByDesc('red_cells')
            ->get()
            ->map(fn ($r) => [$r->operator_name ?? 'Unassigned', $r->employee_code ?? '', $r->red_cells, (int) $r->escalated])
            ->all();

        return [$headings, $rows];
    }

    protected function signoffTurnaround(User $user, array $filters): array
    {
        $headings = ['Sheet', 'Role', 'Signer', 'Signed At', 'Turnaround (mins)'];
        $sheetIds = $this->sheets($user, $filters)->pluck('id');
        $rows = [];

        SewingSignoff::with('sheet')
            ->whereIn('sewing_inspection_sheet_id', $sheetIds)
            ->orderBy('sewing_inspection_sheet_id')
            ->orderBy('signed_at')
            ->get()
            ->groupBy('sewing_inspection_sheet_id')
            ->each(function ($signoffs) use (&$rows) {
                $previous = $signoffs->first()->sheet->created_at ?? null;

                foreach ($signoffs as $signoff) {
                    $turnaround = ($previous && $signoff->signed_at)
                        ? $previous->diffInMinutes($signoff->signed_at)
                        : null;

                    $rows[] = [
                        $signoff->sheet->sheet_no ?? '',
                        SewingSignoff::ROLES[$signoff->role] ?? $signoff->role,
                        $signoff->user_name,
                        optional($signoff->signed_at)->format('Y-m-d H:i'),
                        $turnaround,
                    ];

                    $previous = $signoff->signed_at ?? $previous;
                }
            });

        return [$headings, $rows];
    }

    protected function linePerformance(User $user, array $filters): array
    {
        $headings = ['Line', 'Cells', 'Green', 'Amber', 'Red', 'Red %'];
        $sheetIds = $this->sheets($user, $filters)->pluck('id');
        $rows = SewingInspectionResult::with('sheet.line')
            ->whereIn('sewing_inspection_sheet_id', $sheetIds)
            ->get()
            ->groupBy(fn (SewingInspectionResult $r) => $r->sheet->line->line_no ?? 'Unassigned')
            ->sortKeys()
            ->map(function ($group, $line) {
                $cells = $group->count();
                $red = $group->where('result', SewingInspectionResult::RESULT_RED)->count();

                return [
                    $line,
                    $cells,
                    $group->where('result', SewingInspectionResult::RESULT_GREEN)->count(),
                    $group->where('result', SewingInspectionResult::RESULT_AMBER)->count(),
                    $red,
                    $cells ? round($red / $cells * 100, 1).'%' : '0%',
                ];
            })
            ->values()
            ->all();

        return [$headings, $rows];
    }
}
