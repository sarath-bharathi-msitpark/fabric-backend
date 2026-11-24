<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Admin\Sewing\Concerns\AuthorizesSewingSheet;
use App\Http\Controllers\Controller;
use App\Models\SewingDefectLog;
use App\Models\SewingDefectMaster;
use App\Models\SewingInspectionOperation;
use App\Models\SewingInspectionResult;
use App\Models\SewingInspectionRule;
use App\Models\SewingInspectionSheet;
use App\Services\SewingInspectionService;
use Illuminate\Http\Request;

class SewingGridController extends Controller
{
    use AuthorizesSewingSheet;

    public function __construct(protected SewingInspectionService $service) {}

    public function show(Request $request, SewingInspectionSheet $sheet)
    {
        $this->authorizeSheet($request, $sheet);

        $sheet->load([
            'operations.operator', 'operations.machine',
            'rounds', 'results', 'defectLogs.defectMaster',
        ]);

        $rule = SewingInspectionRule::current();
        $defects = SewingDefectMaster::active()->orderBy('defect_type')->orderBy('capa_code')->get();
        $signoffOrder = SewingInspectionService::SIGNOFF_ORDER;

        return view('admin.sewing.sheets.grid', compact('sheet', 'rule', 'defects', 'signoffOrder'));
    }

    public function saveResult(Request $request, SewingInspectionSheet $sheet, SewingInspectionOperation $operation)
    {
        abort_unless($request->user()->can('edit sewing inspection'), 403);
        $this->authorizeSheet($request, $sheet);

        $data = $request->validate([
            'round_no' => 'required|integer|min:1|max:12',
            'defect_count' => 'required|integer|min:0|max:100',
            'corrective_action' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $this->service->saveResult(
            $request->user(),
            $sheet,
            $operation,
            $data['round_no'],
            $data['defect_count'],
            $data['corrective_action'] ?? null,
            $data['note'] ?? null,
        );

        return back()->with('success', 'Result saved.');
    }

    public function logDefect(Request $request, SewingInspectionSheet $sheet, SewingInspectionOperation $operation)
    {
        abort_unless($request->user()->can('edit sewing inspection'), 403);
        $this->authorizeSheet($request, $sheet);

        $data = $request->validate([
            'round_no' => 'required|integer|min:1|max:12',
            'defect_master_id' => 'required|exists:sewing_defect_masters,id',
            'qty' => 'nullable|integer|min:1|max:100',
            'is_reaudit' => 'nullable|boolean',
            'note' => 'nullable|string',
        ]);

        $this->service->logDefect(
            $request->user(),
            $sheet,
            $operation,
            $data['round_no'],
            $data['defect_master_id'],
            $data['qty'] ?? 1,
            $request->boolean('is_reaudit'),
            $data['note'] ?? null,
        );

        return back()->with('success', 'Defect logged with CAPA snapshot.');
    }

    public function removeDefect(Request $request, SewingInspectionSheet $sheet, SewingDefectLog $log)
    {
        abort_unless($request->user()->can('edit sewing inspection'), 403);
        $this->authorizeSheet($request, $sheet);

        $this->service->removeDefect($request->user(), $sheet, $log);

        return back()->with('success', 'Defect log entry removed.');
    }

    public function reAudit(Request $request, SewingInspectionSheet $sheet, SewingInspectionResult $result)
    {
        abort_unless($request->user()->can('edit sewing inspection'), 403);
        $this->authorizeSheet($request, $sheet);

        $data = $request->validate([
            'defect_count' => 'required|integer|min:0|max:100',
            'note' => 'nullable|string',
        ]);

        $this->service->reAudit(
            $request->user(),
            $sheet,
            $result,
            $data['defect_count'],
            $data['note'] ?? null,
        );

        return back()->with('success', 'Re-audit recorded.');
    }

    public function closeRound(Request $request, SewingInspectionSheet $sheet, int $round)
    {
        abort_unless($request->user()->can('edit sewing inspection'), 403);
        $this->authorizeSheet($request, $sheet);

        $this->service->closeRound($request->user(), $sheet, $round);

        return back()->with('success', "Round {$round} closed.");
    }
}
