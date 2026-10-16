<?php

namespace App\Http\Controllers\Admin\Cutting;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\CuttingAudit;
use App\Models\CuttingTable;
use App\Services\CuttingDashboardService;
use Illuminate\Http\Request;

class CuttingDashboardController extends Controller
{
    public function __construct(protected CuttingDashboardService $dashboard) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CuttingAudit::class);

        $filters = $this->filters($request);

        $kpis = $this->dashboard->kpis($filters);
        $stageCounts = $this->dashboard->stageCounts($filters);
        $topNotOk = $this->dashboard->topNotOkCheckpoints(7, $filters);
        $tableStatus = $this->dashboard->tableStatus($filters);
        $pending = $this->dashboard->pendingSignoffs($filters);

        $buyers = Buyer::orderBy('buyer_name')->get();
        $tables = CuttingTable::orderBy('table_no')->get();
        $shifts = CuttingAudit::whereNotNull('shift')->distinct()->pluck('shift')->sort()->values();

        return view('admin.cutting.dashboard', compact(
            'kpis', 'stageCounts', 'topNotOk', 'tableStatus', 'pending',
            'buyers', 'tables', 'shifts', 'filters'
        ));
    }

    public function data(Request $request)
    {
        $this->authorize('viewAny', CuttingAudit::class);
        $filters = $this->filters($request);

        return response()->json([
            'kpis' => $this->dashboard->kpis($filters),
            'stage_counts' => $this->dashboard->stageCounts($filters),
            'top_not_ok' => $this->dashboard->topNotOkCheckpoints(7, $filters),
        ]);
    }

    protected function filters(Request $request): array
    {
        return $request->only(['from', 'to', 'buyer_id', 'cutting_table_id', 'style_number', 'shift']);
    }
}
