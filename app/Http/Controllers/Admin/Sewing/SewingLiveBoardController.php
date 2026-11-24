<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Controller;
use App\Models\SewingInspectionSheet;
use Illuminate\Http\Request;

class SewingLiveBoardController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('view sewing dashboard'), 403);

        return view('admin.sewing.live-board');
    }

    public function data(Request $request)
    {
        abort_unless($request->user()->can('view sewing dashboard'), 403);

        $sheets = SewingInspectionSheet::query()
            ->visibleTo($request->user())
            ->whereIn('status', [SewingInspectionSheet::STATUS_DRAFT, SewingInspectionSheet::STATUS_IN_PROGRESS])
            ->with(['line', 'operations.operator', 'results'])
            ->orderBy('sewing_line_id')
            ->orderByDesc('inspection_date')
            ->get();

        $lines = $sheets
            ->groupBy(fn (SewingInspectionSheet $s) => $s->line->line_no ?? 'Unassigned')
            ->map(fn ($group, $lineNo) => [
                'line' => $lineNo,
                'sheets' => $group->map(fn (SewingInspectionSheet $sheet) => [
                    'id' => $sheet->id,
                    'sheet_no' => $sheet->sheet_no,
                    'style' => $sheet->style_number,
                    'po' => $sheet->po_number,
                    'current_round' => $sheet->current_round,
                    'operations' => $sheet->operations->map(function ($op) use ($sheet) {
                        $res = $sheet->results
                            ->where('sewing_inspection_operation_id', $op->id)
                            ->firstWhere('round_no', $sheet->current_round);

                        return [
                            'name' => $op->operation_name,
                            'operator' => $op->operator->name ?? null,
                            'result' => $res->result ?? null,
                            'count' => $res->defect_count ?? null,
                            'reaudit' => $res->re_audit_result ?? null,
                        ];
                    })->values(),
                ])->values(),
            ])
            ->values();

        return response()->json([
            'lines' => $lines,
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
