<?php

namespace App\Http\Controllers\Admin\Cutting;

use App\Exports\CuttingAuditRegisterExport;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\CuttingAudit;
use App\Models\CuttingTable;
use App\Models\Style;
use App\Services\CuttingNumberService;
use App\Services\CuttingReportService;
use App\Services\CuttingWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CuttingAuditController extends Controller
{
    public function __construct(
        protected CuttingNumberService $numbers,
        protected CuttingWorkflowService $workflow,
        protected CuttingReportService $reports,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CuttingAudit::class);

        $filters = $request->only([
            'search', 'from', 'to', 'buyer_id', 'cutting_table_id', 'stage', 'result', 'shift',
        ]);

        $audits = CuttingAudit::query()
            ->visibleTo($request->user())
            ->with(['cuttingTable', 'buyer', 'stages'])
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('audit_no', 'like', "%{$v}%")
                        ->orWhere('style_number', 'like', "%{$v}%")
                        ->orWhere('po_number', 'like', "%{$v}%")
                        ->orWhere('lot_number', 'like', "%{$v}%");
                });
            })
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('audit_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('audit_date', '<=', $v))
            ->when($filters['buyer_id'] ?? null, fn ($q, $v) => $q->where('buyer_id', $v))
            ->when($filters['cutting_table_id'] ?? null, fn ($q, $v) => $q->where('cutting_table_id', $v))
            ->when($filters['shift'] ?? null, fn ($q, $v) => $q->where('shift', $v))
            ->when($filters['stage'] ?? null, function ($q, $v) {
                $q->whereHas('stages', fn ($s) => $s->where('stage', $v));
            })
            ->when($filters['result'] ?? null, function ($q, $v) {
                $q->whereHas('stages', fn ($s) => $s->where('result', $v));
            })
            ->orderByDesc('audit_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $buyers = Buyer::orderBy('buyer_name')->get();
        $tables = CuttingTable::orderBy('table_no')->get();
        $stages = CuttingAudit::STAGES;
        $results = ['approved', 'not_approved', 'special_approval'];
        $shifts = CuttingAudit::whereNotNull('shift')->distinct()->pluck('shift')->sort()->values();

        return view('admin.cutting.audits.index', compact(
            'audits', 'filters', 'buyers', 'tables', 'stages', 'results', 'shifts'
        ));
    }

    public function create(Request $request)
    {
        $this->authorize('create', CuttingAudit::class);

        $buyers = Buyer::where('is_active', true)->orderBy('buyer_name')->get();
        $styles = Style::orderBy('style_number')->get();
        $tables = CuttingTable::where('is_active', true)->orderBy('table_no')->get();

        return view('admin.cutting.audits.create', compact('buyers', 'styles', 'tables'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', CuttingAudit::class);

        $data = $this->validateAudit($request);

        $audit = DB::transaction(function () use ($data, $request) {
            $audit = CuttingAudit::create(array_merge($data, [
                'audit_no' => $this->numbers->nextAuditNumber(),
                'status' => 'in_progress',
                'current_stage' => 'lay',
                'revision' => 1,
                'created_by' => $request->user()->id,
            ]));
            $audit->root_id = $audit->id;
            $audit->save();

            return $audit;
        });

        return redirect()
            ->route('admin.cutting.stages.show', [$audit, 'lay'])
            ->with('success', "Audit {$audit->audit_no} created. Continue with the lay inspection.");
    }

    public function show(Request $request, CuttingAudit $audit)
    {
        $this->authorize('view', $audit);

        $audit->load([
            'cuttingTable', 'buyer', 'creator', 'sizePatterns', 'bundles',
            'stages.results.photos', 'stages.signoffs', 'signoffs',
            'specialApprovals.requester', 'specialApprovals.approver',
        ]);

        $revisions = CuttingAudit::where('root_id', $audit->root_id ?? $audit->id)
            ->orderBy('revision')
            ->get();

        $workflow = $this->workflow;

        return view('admin.cutting.audits.show', compact('audit', 'revisions', 'workflow'));
    }

    public function edit(Request $request, CuttingAudit $audit)
    {
        $this->authorize('update', $audit);

        $buyers = Buyer::orderBy('buyer_name')->get();
        $styles = Style::orderBy('style_number')->get();
        $tables = CuttingTable::orderBy('table_no')->get();

        return view('admin.cutting.audits.edit', compact('audit', 'buyers', 'styles', 'tables'));
    }

    public function update(Request $request, CuttingAudit $audit)
    {
        $this->authorize('update', $audit);

        $data = $this->validateAudit($request);
        $audit->update($data);

        return redirect()
            ->route('admin.cutting.audits.show', $audit)
            ->with('success', 'Audit header and lay details updated.');
    }

    public function destroy(Request $request, CuttingAudit $audit)
    {
        $this->authorize('delete', $audit);
        $no = $audit->audit_no;
        $audit->delete();

        return redirect()
            ->route('admin.cutting.audits.index')
            ->with('success', "Audit {$no} deleted.");
    }

    public function export(Request $request)
    {
        $this->authorize('export', CuttingAudit::class);
        $filters = $request->only([
            'search', 'from', 'to', 'buyer_id', 'cutting_table_id', 'stage', 'result', 'shift',
        ]);

        return Excel::download(
            new CuttingAuditRegisterExport($filters, $request->user()),
            'cutting-audit-register-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function report(Request $request, CuttingAudit $audit)
    {
        $this->authorize('view', $audit);

        $data = $this->reports->auditReport($audit);

        $pdf = Pdf::loadView('admin.cutting.reports.audit-pdf', array_merge($data, [
            'company' => config('app.name', 'INR Global Sourcing'),
        ]));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('SKW-QMS-17-'.$audit->audit_no.'.pdf');
    }

    protected function validateAudit(Request $request): array
    {
        return $request->validate([
            'audit_date' => 'required|date',
            'shift' => 'nullable|string|max:20',
            'cutting_table_id' => 'nullable|exists:cutting_tables,id',
            'buyer_id' => 'nullable|exists:buyers,id',
            'style_number' => 'nullable|string|max:100',
            'style_name' => 'nullable|string|max:191',
            'po_number' => 'nullable|string|max:100',
            'lot_number' => 'nullable|string|max:100',
            'fabric' => 'nullable|string|max:191',
            'colour' => 'nullable|string|max:191',
            'dia' => 'nullable|string|max:50',

            'lay_number' => 'nullable|string|max:100',
            'cut_number' => 'nullable|string|max:100',
            'number_of_sizes' => 'required|integer|min:1|max:100',
            'lay_method' => 'required|in:manual,spreader',
            'marking_type' => 'nullable|string|max:100',
            'required_lay_length' => 'nullable|numeric|min:0',
            'required_lay_width' => 'nullable|numeric|min:0',
            'actual_lay_length' => 'nullable|numeric|min:0',
            'actual_lay_width' => 'nullable|numeric|min:0',
            'required_plies' => 'nullable|integer|min:0',
            'actual_plies' => 'nullable|integer|min:0',
            'actual_lay_height' => 'nullable|numeric|min:0',
            'total_parts' => 'nullable|integer|min:0',
            'tolerance_pct' => 'nullable|numeric|min:0|max:100',
        ]);
    }
}
