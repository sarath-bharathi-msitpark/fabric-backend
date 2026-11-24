<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Controller;
use App\Models\SewingInspectionResult;
use App\Models\SewingInspectionSheet;
use App\Models\SewingLine;
use Illuminate\Http\Request;

class SewingRepeatRedController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('view sewing reports'), 403);

        $sheetIds = SewingInspectionSheet::query()
            ->visibleTo($request->user())
            ->when($request->filled('sewing_line_id'), fn ($q) => $q->where('sewing_line_id', $request->input('sewing_line_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('inspection_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('inspection_date', '<=', $request->input('to')))
            ->pluck('id');

        $rows = SewingInspectionResult::query()
            ->join('sewing_inspection_operations as o', 'o.id', '=', 'sewing_inspection_results.sewing_inspection_operation_id')
            ->leftJoin('sewing_operators as op', 'op.id', '=', 'o.sewing_operator_id')
            ->leftJoin('sewing_lines as l', 'l.id', '=', 'op.sewing_line_id')
            ->whereIn('sewing_inspection_results.sewing_inspection_sheet_id', $sheetIds)
            ->where('sewing_inspection_results.result', SewingInspectionResult::RESULT_RED)
            ->selectRaw('op.id as operator_id, op.name as operator_name, op.employee_code, l.line_no, count(*) as red_cells')
            ->selectRaw('sum(case when sewing_inspection_results.re_audit_result = ? then 1 else 0 end) as escalated', [SewingInspectionResult::REAUDIT_ESCALATED])
            ->groupBy('op.id', 'op.name', 'op.employee_code', 'l.line_no')
            ->orderByDesc('red_cells')
            ->get();

        $lines = SewingLine::orderBy('line_no')->get();

        return view('admin.sewing.repeat-red', [
            'rows' => $rows,
            'lines' => $lines,
        ]);
    }
}
