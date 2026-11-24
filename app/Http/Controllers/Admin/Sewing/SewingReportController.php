<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Exports\SewingReportExport;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\SewingLine;
use App\Services\SewingReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SewingReportController extends Controller
{
    public const TYPES = [
        'inspection-register' => 'Inspection Register',
        'defect-analysis' => 'Defect Analysis',
        're-audit' => 'Re-audit Log',
        'repeat-red' => 'Repeat Red Operators',
        'signoff-turnaround' => 'Sign-off Turnaround',
        'line-performance' => 'Line-wise Performance',
    ];

    public function __construct(protected SewingReportService $reports) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('view sewing reports'), 403);

        return view('admin.sewing.reports.index', ['types' => self::TYPES]);
    }

    public function show(Request $request, string $type)
    {
        abort_unless($request->user()->can('view sewing reports'), 403);
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $filters = $this->filters($request);
        [$headings, $rows] = $this->reports->build($type, $request->user(), $filters);

        $buyers = Buyer::orderBy('buyer_name')->get();
        $lines = SewingLine::orderBy('line_no')->get();

        return view('admin.sewing.reports.show', [
            'type' => $type,
            'title' => self::TYPES[$type],
            'headings' => $headings,
            'rows' => $rows,
            'filters' => $filters,
            'buyers' => $buyers,
            'lines' => $lines,
        ]);
    }

    public function export(Request $request, string $type)
    {
        abort_unless($request->user()->can('export sewing reports'), 403);
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $filters = $this->filters($request);
        [$headings, $rows] = $this->reports->build($type, $request->user(), $filters);
        $format = $request->input('format', 'xlsx');
        $title = self::TYPES[$type];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.sewing.reports.generic-pdf', [
                'title' => $title,
                'headings' => $headings,
                'rows' => $rows,
                'company' => config('app.name', 'INR Global Sourcing'),
            ]);
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download($type.'-'.now()->format('Y-m-d').'.pdf');
        }

        return Excel::download(new SewingReportExport($title, $headings, $rows), $type.'-'.now()->format('Y-m-d').'.xlsx');
    }

    protected function filters(Request $request): array
    {
        return $request->only(['from', 'to', 'sewing_line_id', 'buyer_id', 'style_number', 'shift']);
    }
}
