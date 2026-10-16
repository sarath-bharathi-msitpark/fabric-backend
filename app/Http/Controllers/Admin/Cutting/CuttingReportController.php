<?php

namespace App\Http\Controllers\Admin\Cutting;

use App\Exports\CuttingReportExport;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\CuttingAudit;
use App\Models\CuttingTable;
use App\Services\CuttingReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CuttingReportController extends Controller
{
    public const TYPES = [
        'defect_analysis' => 'Defect Analysis',
        'special_approval_log' => 'Special Approval Log',
        'signoff_turnaround' => 'Sign-off Turnaround',
        'table_performance' => 'Table-wise Performance',
    ];

    public function __construct(protected CuttingReportService $reports) {}

    public function index(Request $request)
    {
        $this->authorize('export', CuttingAudit::class);

        return view('admin.cutting.reports.index', [
            'types' => self::TYPES,
        ]);
    }

    public function show(Request $request, string $type)
    {
        $this->authorize('export', CuttingAudit::class);
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $filters = $this->filters($request);
        [$headings, $rows] = $this->build($type, $filters);

        $buyers = Buyer::orderBy('buyer_name')->get();
        $tables = CuttingTable::orderBy('table_no')->get();

        return view('admin.cutting.reports.show', [
            'type' => $type,
            'title' => self::TYPES[$type],
            'headings' => $headings,
            'rows' => $rows,
            'filters' => $filters,
            'buyers' => $buyers,
            'tables' => $tables,
        ]);
    }

    public function export(Request $request, string $type)
    {
        $this->authorize('export', CuttingAudit::class);
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $filters = $this->filters($request);
        [$headings, $rows] = $this->build($type, $filters);
        $format = $request->input('format', 'xlsx');
        $title = self::TYPES[$type];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.cutting.reports.generic-pdf', [
                'title' => $title,
                'headings' => $headings,
                'rows' => $rows,
                'company' => config('app.name', 'INR Global Sourcing'),
            ]);
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download($type.'-'.now()->format('Y-m-d').'.pdf');
        }

        return Excel::download(new CuttingReportExport($title, $headings, $rows), $type.'-'.now()->format('Y-m-d').'.xlsx');
    }

    protected function build(string $type, array $filters): array
    {
        return match ($type) {
            'defect_analysis' => $this->defectAnalysis($filters),
            'special_approval_log' => $this->specialApprovalLog($filters),
            'signoff_turnaround' => $this->signoffTurnaround($filters),
            'table_performance' => $this->tablePerformance($filters),
            default => [[], []],
        };
    }

    protected function defectAnalysis(array $filters): array
    {
        $headings = ['Checkpoint', 'Stage', 'Not OK Count'];
        $rows = $this->reports->defectAnalysis($filters)
            ->map(fn ($r) => [$r->checkpoint_name, $r->stage, $r->not_ok_count])
            ->all();

        return [$headings, $rows];
    }

    protected function specialApprovalLog(array $filters): array
    {
        $headings = ['Audit No', 'Stage', 'Table', 'Buyer', 'Reason', 'Requested By', 'Status', 'Approved By', 'Approved At'];
        $rows = $this->reports->specialApprovalLog($filters)
            ->map(fn ($a) => [
                $a->audit?->audit_no,
                $a->stage,
                $a->audit?->cuttingTable?->table_no,
                $a->audit?->buyer?->buyer_name,
                $a->reason,
                $a->requester?->name,
                ucfirst($a->status),
                $a->approver?->name,
                $a->approved_at?->format('Y-m-d H:i'),
            ])->all();

        return [$headings, $rows];
    }

    protected function signoffTurnaround(array $filters): array
    {
        $headings = ['Audit No', 'Stage', 'Role', 'Signer', 'Signed At', 'Turnaround (mins)'];
        $rows = $this->reports->signoffTurnaround($filters)
            ->map(fn ($s) => [
                $s->audit?->audit_no,
                $s->stage,
                $s->role,
                $s->user_name,
                $s->signed_at?->format('Y-m-d H:i'),
                $s->turnaround_minutes,
            ])->all();

        return [$headings, $rows];
    }

    protected function tablePerformance(array $filters): array
    {
        $headings = ['Table', 'Audits', 'Lay Approval %', 'Cut Panel Approval %', 'Rework'];
        $rows = $this->reports->tableWisePerformance($filters)
            ->map(fn ($t) => [$t['table'], $t['audits'], $t['lay_approval_rate'], $t['cut_panel_approval_rate'], $t['rework']])
            ->all();

        return [$headings, $rows];
    }

    protected function filters(Request $request): array
    {
        return $request->only(['from', 'to', 'buyer_id', 'cutting_table_id', 'style_number', 'shift']);
    }
}
