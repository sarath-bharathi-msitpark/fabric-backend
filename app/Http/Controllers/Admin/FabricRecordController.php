<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FabricRecordsExport;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\FabricRecord;
use App\Models\InspectionDetail;
use App\Models\QualityDefect;
use App\Models\Style;
use App\Models\Supplier;
use App\Services\AlertsEngineService;
use App\Services\KpiService;
use App\Services\SupplierRatingService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FabricRecordController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['buyer_id', 'style_id', 'supplier_id', 'fabric_type', 'from', 'to']);

        $records = FabricRecord::with(['buyer', 'style', 'supplier', 'inspection'])
            ->when($filters['buyer_id'] ?? null, fn ($q, $v) => $q->where('buyer_id', $v))
            ->when($filters['style_id'] ?? null, fn ($q, $v) => $q->where('style_id', $v))
            ->when($filters['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($filters['fabric_type'] ?? null, fn ($q, $v) => $q->where('fabric_type', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('record_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('record_date', '<=', $v))
            ->orderByDesc('record_date')
            ->paginate(20)
            ->withQueryString();

        $buyers = Buyer::orderBy('buyer_name')->get();
        $styles = Style::orderBy('style_number')->get();
        $suppliers = Supplier::orderBy('supplier_name')->get();
        $fabricTypes = FabricRecord::distinct()->pluck('fabric_type')->sort()->values();

        return view('admin.fabric-records.index', compact('records', 'filters', 'buyers', 'styles', 'suppliers', 'fabricTypes'));
    }

    public function export(Request $request)
    {
        $this->authorize('export', FabricRecord::class);
        $filters = $request->only(['buyer_id', 'style_id', 'supplier_id', 'fabric_type', 'color', 'from', 'to']);
        return Excel::download(new FabricRecordsExport($filters), 'fabric-records-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function show(FabricRecord $fabric_record)
    {
        $fabric_record->load(['buyer', 'style', 'supplier', 'uploader', 'inspection.inspector', 'defects', 'alerts']);
        $quality = app(KpiService::class)->qualityMetricsFor($fabric_record);
        return view('admin.fabric-records.show', compact('fabric_record', 'quality'));
    }

    public function edit(FabricRecord $fabric_record)
    {
        $this->authorize('update', $fabric_record);
        $fabric_record->load(['buyer', 'style', 'supplier', 'inspection', 'defects']);
        $buyers = Buyer::orderBy('buyer_name')->get();
        $styles = Style::orderBy('style_number')->get();
        $suppliers = Supplier::orderBy('supplier_name')->get();
        return view('admin.fabric-records.edit', compact('fabric_record', 'buyers', 'styles', 'suppliers'));
    }

    public function update(Request $request, FabricRecord $fabric_record)
    {
        $this->authorize('update', $fabric_record);
        $data = $request->validate([
            'record_date' => 'required|date',
            'buyer_id' => 'required|exists:buyers,id',
            'style_id' => 'required|exists:styles,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'fabric_type' => 'required|string|max:50',
            'color' => 'required|string|max:50',
            'ordered_kg' => 'required|numeric',
            'received_kg' => 'required|numeric',
            'inspected_kg' => 'nullable|numeric',
            'approved_kg' => 'nullable|numeric',
            'rejected_kg' => 'nullable|numeric',
            'gsm_actual' => 'nullable|numeric',
            'width_actual' => 'nullable|numeric',
            'pass_pct' => 'nullable|numeric',
            'bowing_pct' => 'nullable|numeric',
            'skewing_pct' => 'nullable|numeric',
            'shade_status' => 'nullable|in:approved,rejected,pending',
            'inspection_date' => 'nullable|date',
            'defects' => 'nullable|array',
            'defects.*.defect_type' => 'required_with:defects|string',
            'defects.*.count' => 'required_with:defects|integer',
            'defects.*.severity' => 'required_with:defects|in:minor,major,critical',
            'defects.*.notes' => 'nullable|string',
        ]);

        $fabric_record->update($request->only(['record_date', 'buyer_id', 'style_id', 'supplier_id', 'fabric_type', 'color', 'ordered_kg', 'received_kg']));

        $insp = $request->only(['inspected_kg', 'approved_kg', 'rejected_kg', 'gsm_actual', 'width_actual', 'pass_pct', 'bowing_pct', 'skewing_pct', 'shade_status', 'inspection_date']);
        $insp = array_filter($insp, fn ($v) => $v !== null && $v !== '');
        $insp['gsm_target'] = $fabric_record->inspection?->gsm_target ?? 220;
        $insp['width_target'] = $fabric_record->inspection?->width_target ?? 180;
        $insp['inspected_by'] = auth()->id();

        InspectionDetail::updateOrCreate(['fabric_record_id' => $fabric_record->id], $insp);

        if ($request->has('defects')) {
            $fabric_record->defects()->delete();
            foreach ($request->input('defects', []) as $defect) {
                if (!empty($defect['defect_type'])) {
                    QualityDefect::create([
                        'fabric_record_id' => $fabric_record->id,
                        'defect_type' => $defect['defect_type'],
                        'count' => $defect['count'] ?? 0,
                        'severity' => $defect['severity'] ?? 'minor',
                        'notes' => $defect['notes'] ?? null,
                    ]);
                }
            }
        }

        app(SupplierRatingService::class)->recalculate($fabric_record->supplier);
        app(AlertsEngineService::class)->scan($fabric_record->id);
        $this->checkStyleCompletion($fabric_record->style_id);

        return redirect()->route('admin.fabric-records.show', $fabric_record)
            ->with('success', "Lot {$fabric_record->lot_no} updated successfully.");
    }

    public function destroy(FabricRecord $fabric_record)
    {
        $this->authorize('delete', $fabric_record);
        $lot = $fabric_record->lot_no;
        $fabric_record->delete();
        return redirect()->route('admin.fabric-records.index')
            ->with('success', "Lot {$lot} and its inspection/defect data deleted.");
    }

    protected function checkStyleCompletion(int $styleId): void
    {
        $style = Style::find($styleId);
        if (!$style) return;
        $approvedSum = (float) InspectionDetail::query()
            ->join('fabric_records', 'inspection_details.fabric_record_id', '=', 'fabric_records.id')
            ->where('fabric_records.style_id', $styleId)
            ->sum('inspection_details.approved_kg');
        if ($approvedSum >= (float) $style->order_quantity && $style->status !== 'completed') {
            $style->update(['status' => 'completed']);
        }
    }
}
