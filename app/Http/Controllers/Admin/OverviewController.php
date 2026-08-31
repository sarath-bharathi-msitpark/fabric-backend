<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\KpiService;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    public function __construct(protected KpiService $kpi) {}

    public function index()
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $totalOrdered = (float) \App\Models\FabricRecord::whereBetween('record_date', [$monthStart, $monthEnd])->sum('ordered_kg');
        $totalReceived = (float) \App\Models\FabricRecord::whereBetween('record_date', [$monthStart, $monthEnd])->sum('received_kg');
        $totalApproved = (float) \App\Models\FabricRecord::join('inspection_details', 'fabric_records.id', '=', 'inspection_details.fabric_record_id')
            ->whereBetween('record_date', [$monthStart, $monthEnd])
            ->sum('inspection_details.approved_kg');

        $openAlerts = Alert::where('is_resolved', false)->count();
        $recentUploads = \App\Models\UploadBatch::with('uploader')->latest()->limit(5)->get();

        return view('admin.overview', compact('totalOrdered', 'totalReceived', 'totalApproved', 'openAlerts', 'recentUploads'));
    }
}
