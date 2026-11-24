<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Exports\SewingReportExport;
use App\Http\Controllers\Controller;
use App\Models\SewingDefectLog;
use App\Models\SewingDefectMaster;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class SewingDefectLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = $this->filtered($request)->paginate(30)->withQueryString();
        $types = SewingDefectMaster::TYPES;
        $summary = $this->summary($request);

        return view('admin.sewing.defect-log.index', compact('logs', 'types', 'summary'));
    }

    public function export(Request $request, string $format = 'xlsx')
    {
        abort_unless($request->user()->can('export sewing reports'), 403);

        $headings = ['Sheet', 'Round', 'Operation', 'Type', 'CAPA', 'Defect', 'Qty', 'Logged by'];
        $rows = $this->filtered($request)->get()->map(fn (SewingDefectLog $log) => [
            $log->sheet->sheet_no ?? '',
            'R'.$log->round_no,
            $log->operation->operation_name ?? '',
            $log->defect_type,
            $log->capa_code,
            $log->defect_name,
            $log->qty,
            $log->loggedBy->name ?? '',
        ])->all();

        $title = 'Sewing Defect Log';

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.sewing.reports.generic-pdf', [
                'title' => $title,
                'headings' => $headings,
                'rows' => $rows,
                'company' => config('app.name', 'INR Global Sourcing'),
            ]);
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download('sewing-defect-log-'.now()->format('Y-m-d').'.pdf');
        }

        return Excel::download(
            new SewingReportExport($title, $headings, $rows),
            'sewing-defect-log-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    protected function baseQuery(Request $request)
    {
        return SewingDefectLog::query()
            ->when($request->filled('type'), fn ($q) => $q->where('defect_type', $request->input('type')))
            ->when($request->filled('capa_code'), fn ($q) => $q->where('capa_code', 'like', '%'.$request->input('capa_code').'%'))
            ->when($request->filled('defect_name'), fn ($q) => $q->where('defect_name', 'like', '%'.$request->input('defect_name').'%'))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')));
    }

    protected function filtered(Request $request)
    {
        return $this->baseQuery($request)
            ->with(['sheet', 'operation', 'defectMaster', 'loggedBy'])
            ->orderByDesc('id');
    }

    /**
     * Summary of the most frequent defects by name for the active filters.
     *
     * @return Collection<int, SewingDefectLog>
     */
    protected function summary(Request $request)
    {
        return $this->baseQuery($request)
            ->selectRaw('capa_code, defect_name, defect_type, sum(qty) as total, count(*) as occurrences')
            ->groupBy('capa_code', 'defect_name', 'defect_type')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }
}
