<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Exports\SewingReportExport;
use App\Http\Controllers\Admin\Sewing\Concerns\AuthorizesSewingSheet;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\SewingInspectionRule;
use App\Models\SewingInspectionSheet;
use App\Models\SewingLine;
use App\Models\SewingMachine;
use App\Models\SewingOperation;
use App\Models\SewingOperator;
use App\Models\SewingSignoff;
use App\Models\Style;
use App\Services\SewingInspectionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class SewingSheetController extends Controller
{
    use AuthorizesSewingSheet;

    public function __construct(protected SewingInspectionService $service) {}

    public function index(Request $request)
    {
        $sheets = SewingInspectionSheet::query()
            ->visibleTo($request->user())
            ->with(['line', 'buyer', 'qc'])
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $v = $request->input('search');
                $q->where(function ($sub) use ($v) {
                    $sub->where('sheet_no', 'like', "%{$v}%")
                        ->orWhere('style_number', 'like', "%{$v}%")
                        ->orWhere('po_number', 'like', "%{$v}%")
                        ->orWhere('lot_number', 'like', "%{$v}%");
                });
            })
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $statuses = SewingInspectionSheet::STATUSES;

        return view('admin.sewing.sheets.index', compact('sheets', 'statuses'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('create sewing inspection'), 403);

        $buyers = Buyer::where('is_active', true)->orderBy('buyer_name')->get();
        $styles = Style::orderBy('style_number')->get();
        $lines = SewingLine::where('is_active', true)->orderBy('line_no')->get();
        $operations = SewingOperation::where('is_active', true)->orderBy('name')->get();
        $machines = SewingMachine::where('is_active', true)->orderBy('machine_no')->get();
        $operators = SewingOperator::where('is_active', true)->orderBy('name')->get();
        $rule = SewingInspectionRule::current();

        return view('admin.sewing.sheets.create', compact(
            'buyers', 'styles', 'lines', 'operations', 'machines', 'operators', 'rule'
        ));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('create sewing inspection'), 403);

        $data = $request->validate([
            'inspection_date' => 'required|date',
            'shift' => 'nullable|string|max:20',
            'sewing_line_id' => 'nullable|exists:sewing_lines,id',
            'buyer_id' => 'nullable|exists:buyers,id',
            'style_id' => 'nullable|exists:styles,id',
            'style_number' => 'nullable|string|max:100',
            'style_name' => 'nullable|string|max:191',
            'color' => 'nullable|string|max:191',
            'po_number' => 'nullable|string|max:100',
            'lot_number' => 'nullable|string|max:100',
            'bundle_no' => 'nullable|string|max:100',
            'qc_id' => 'nullable|exists:users,id',
            'rounds' => 'nullable|integer|min:1|max:12',
            'operations' => 'required|array|min:1',
            'operations.*.sewing_operation_id' => 'nullable|exists:sewing_operations,id',
            'operations.*.operation_name' => 'nullable|string|max:191',
            'operations.*.sewing_machine_id' => 'nullable|exists:sewing_machines,id',
            'operations.*.machine_no' => 'nullable|string|max:50',
            'operations.*.sewing_operator_id' => 'nullable|exists:sewing_operators,id',
            'operations.*.note' => 'nullable|string',
        ]);

        $operations = collect($data['operations'])
            ->map(function ($op) {
                if (empty($op['operation_name']) && ! empty($op['sewing_operation_id'])) {
                    $op['operation_name'] = SewingOperation::find($op['sewing_operation_id'])?->name;
                }

                return $op;
            })
            ->filter(fn ($op) => ! empty($op['operation_name']) || ! empty($op['sewing_operation_id']))
            ->values()
            ->all();

        if (count($operations) === 0) {
            throw ValidationException::withMessages(['operations' => 'Add at least one operation.']);
        }

        $header = Arr::except($data, ['operations', 'rounds']);
        $header['qc_id'] = $header['qc_id'] ?? $request->user()->id;

        $sheet = $this->service->createSheet(
            $request->user(),
            $header,
            $operations,
            $data['rounds'] ?? null,
        );

        return redirect()
            ->route('admin.sewing.grid.show', $sheet)
            ->with('success', "Sheet {$sheet->sheet_no} created. Capture round 1 results.");
    }

    public function show(Request $request, SewingInspectionSheet $sheet)
    {
        $this->authorizeSheet($request, $sheet);

        $sheet->load([
            'line', 'buyer', 'style', 'qc', 'creator',
            'operations.operator', 'operations.machine',
            'rounds', 'results', 'defectLogs.defectMaster', 'signoffs.user',
        ]);

        $signoffOrder = SewingInspectionService::SIGNOFF_ORDER;
        $signoffPermissions = SewingInspectionService::SIGNOFF_PERMISSIONS;

        return view('admin.sewing.sheets.show', compact('sheet', 'signoffOrder', 'signoffPermissions'));
    }

    public function signoff(Request $request, SewingInspectionSheet $sheet)
    {
        $this->authorizeSheet($request, $sheet);

        $data = $request->validate([
            'role' => 'required|in:'.implode(',', array_keys(SewingSignoff::ROLES)),
            'comments' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        $this->service->signOff(
            $request->user(),
            $sheet,
            $data['role'],
            $data['comments'] ?? null,
            $data['reason'] ?? null,
        );

        return back()->with('success', 'Sign-off recorded.');
    }

    public function correct(Request $request, SewingInspectionSheet $sheet)
    {
        abort_unless($request->user()->can('manage sewing rules'), 403);
        $this->authorizeSheet($request, $sheet);

        $data = $request->validate([
            'style_name' => 'nullable|string|max:191',
            'color' => 'nullable|string|max:191',
            'po_number' => 'nullable|string|max:100',
            'lot_number' => 'nullable|string|max:100',
            'bundle_no' => 'nullable|string|max:100',
            'shift' => 'nullable|string|max:20',
            'inspection_date' => 'nullable|date',
            'reason' => 'required|string|min:3',
        ]);

        $this->service->correctAfterLock(
            $request->user(),
            $sheet,
            Arr::except($data, ['reason']),
            $data['reason'],
        );

        return back()->with('success', 'Controlled correction recorded.');
    }

    public function print(Request $request, SewingInspectionSheet $sheet)
    {
        abort_unless($request->user()->can('view sewing register'), 403);
        $this->authorizeSheet($request, $sheet);

        return view('admin.sewing.sheets.print', $this->formData($sheet));
    }

    public function pdf(Request $request, SewingInspectionSheet $sheet)
    {
        abort_unless($request->user()->can('view sewing register'), 403);
        $this->authorizeSheet($request, $sheet);

        $pdf = Pdf::loadView('admin.sewing.sheets.print', $this->formData($sheet) + ['pdf' => true]);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download($sheet->sheet_no.'.pdf');
    }

    public function export(Request $request, SewingInspectionSheet $sheet)
    {
        abort_unless($request->user()->can('export sewing reports'), 403);
        $this->authorizeSheet($request, $sheet);

        $sheet->load(['operations.operator', 'operations.machine', 'rounds', 'results']);
        [$headings, $rows] = $this->sheetRows($sheet);

        return Excel::download(
            new SewingReportExport('Inspection Sheet '.$sheet->sheet_no, $headings, $rows),
            $sheet->sheet_no.'.xlsx',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(SewingInspectionSheet $sheet): array
    {
        $sheet->load([
            'line', 'buyer', 'style', 'qc',
            'operations.operator', 'operations.machine',
            'rounds', 'results',
            'defectLogs.operation.operator',
            'signoffs.user',
        ]);

        return [
            'sheet' => $sheet,
            'rounds' => $sheet->rounds->sortBy('round_no')->values(),
            'byOp' => $sheet->results->groupBy('sewing_inspection_operation_id')->map(fn ($g) => $g->keyBy('round_no')),
            'defects' => $sheet->defectLogs->sortBy([['round_no', 'asc'], ['id', 'asc']])->values(),
            'signoffs' => $sheet->signoffs->keyBy('role'),
            'signoffOrder' => SewingInspectionService::SIGNOFF_ORDER,
        ];
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<int, mixed>>}
     */
    protected function sheetRows(SewingInspectionSheet $sheet): array
    {
        $rounds = $sheet->rounds->sortBy('round_no')->values();
        $byOp = $sheet->results->groupBy('sewing_inspection_operation_id')->map(fn ($g) => $g->keyBy('round_no'));
        $letter = fn ($r) => $r === 'green' ? 'G' : ($r === 'amber' ? 'A' : ($r === 'red' ? 'R' : '-'));

        $headings = ['#', 'Operation', 'Operator', 'Machine'];
        foreach ($rounds as $round) {
            $headings[] = 'R'.$round->round_no;
        }
        $headings[] = 'Re-audit';

        $rows = [];
        foreach ($sheet->operations as $op) {
            $opResults = $byOp[$op->id] ?? collect();
            $row = [$op->sequence_no, $op->operation_name, $op->operator->name ?? '', $op->machine_no ?? ''];

            foreach ($rounds as $round) {
                $res = $opResults->get($round->round_no);
                $row[] = $res ? $res->defect_count.' ('.$letter($res->result).')' : '';
            }

            $re = $opResults->first(fn ($r) => $r->re_audit_result);
            $row[] = $re ? ucfirst($re->re_audit_result).' ('.$re->re_audit_count.')' : '';

            $rows[] = $row;
        }

        return [$headings, $rows];
    }
}
