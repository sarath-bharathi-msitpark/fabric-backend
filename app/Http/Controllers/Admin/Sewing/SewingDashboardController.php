<?php

namespace App\Http\Controllers\Admin\Sewing;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\SewingLine;
use App\Services\SewingDashboardService;
use Illuminate\Http\Request;

class SewingDashboardController extends Controller
{
    public function __construct(protected SewingDashboardService $dashboard) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $this->filters($request);

        $stats = $this->dashboard->kpis($user, $filters);
        $topDefects = $this->dashboard->topDefects($user, $filters);
        $topDefectTypes = $this->dashboard->topDefectTypes($user, $filters);
        $topByLine = $this->dashboard->topDefectsByLine($user, $filters);
        $topByStyle = $this->dashboard->topDefectsByStyle($user, $filters);
        $topByOperation = $this->dashboard->topDefectsByOperation($user, $filters);
        $topByOperator = $this->dashboard->topDefectsByOperator($user, $filters);
        $lineStatus = $this->dashboard->lineStatus($user, $filters);
        $pending = $this->dashboard->pendingReaudits($user, $filters);

        $buyers = Buyer::orderBy('buyer_name')->get();
        $lines = SewingLine::orderBy('line_no')->get();

        return view('admin.sewing.dashboard', compact(
            'stats', 'topDefects', 'topDefectTypes', 'topByLine', 'topByStyle',
            'topByOperation', 'topByOperator', 'lineStatus', 'pending', 'buyers', 'lines', 'filters'
        ));
    }

    protected function filters(Request $request): array
    {
        return $request->only(['from', 'to', 'sewing_line_id', 'buyer_id', 'style_number', 'shift']);
    }
}
