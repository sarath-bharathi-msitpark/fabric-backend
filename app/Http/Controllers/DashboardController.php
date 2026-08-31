<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Buyer;
use App\Models\Style;
use App\Models\Supplier;
use App\Services\KpiService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected KpiService $kpi) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $kpis = $this->kpi->calculate($filters);

        $buyers = Buyer::orderBy('buyer_name')->get();
        $styles = Style::orderBy('style_number')->get();
        $suppliers = Supplier::orderBy('supplier_name')->get();
        $colors = \App\Models\FabricRecord::distinct()->pluck('color')->sort()->values();
        $fabricTypes = \App\Models\FabricRecord::distinct()->pluck('fabric_type')->sort()->values();

        $alerts = Alert::with(['fabricRecord', 'supplier'])
            ->where('is_resolved', false)
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard', compact('kpis', 'buyers', 'styles', 'suppliers', 'colors', 'fabricTypes', 'alerts', 'filters'));
    }

    public function data(Request $request)
    {
        $filters = $this->filters($request);
        $kpi = $this->kpi;
        $kpi->calculate($filters);

        return response()->json([
            'kpis' => $kpi->calculate($filters),
            'trend' => $kpi->trendData(),
            'status_breakdown' => $kpi->statusBreakdown(),
            'stock_by_type' => $kpi->stockByFabricType(),
            'top_defects' => $kpi->topDefects(),
            'consumption_vs_plan' => $kpi->consumptionVsPlan(),
            'supplier_performance' => Supplier::where('is_active', true)->orderByDesc('quality_pct')->limit(10)->get(['supplier_name', 'quality_pct', 'rating']),
            'inspection_gauge' => $kpi->inspectionCompleted(),
        ]);
    }

    protected function filters(Request $request): array
    {
        return $request->only(['buyer_id', 'style_id', 'supplier_id', 'fabric_type', 'color', 'from', 'to']);
    }
}
