<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FabricRecordsExport;
use App\Exports\FourPointInspectionReportExport;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\FabricRecord;
use App\Models\InspectionDetail;
use App\Models\InspectionRoll;
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

    public function inspectionReport(FabricRecord $fabric_record)
    {
        $this->authorize('view', $fabric_record);
        $fabric_record->load(['buyer', 'style', 'supplier', 'rolls.defects', 'inspection']);

        $reportNo = 'RPT-' . $fabric_record->lot_no . '-' . now()->format('Ymd');

        return (new FourPointInspectionReportExport($fabric_record, $reportNo))->download();
    }

    public function show(FabricRecord $fabric_record)
    {
        $fabric_record->load(['buyer', 'style', 'supplier', 'uploader', 'inspection.inspector', 'rolls.defects', 'defects', 'alerts']);
        $quality = app(KpiService::class)->qualityMetricsFor($fabric_record);
        return view('admin.fabric-records.show', compact('fabric_record', 'quality'));
    }

    public function edit(FabricRecord $fabric_record)
    {
        $this->authorize('update', $fabric_record);
        $fabric_record->load(['buyer', 'style', 'supplier', 'inspection', 'rolls.defects', 'defects']);
        $buyers = Buyer::orderBy('buyer_name')->get();
        $styles = Style::orderBy('style_number')->get();
        $suppliers = Supplier::orderBy('supplier_name')->get();
        $defectTypes = [
            'Hole', 'GSM hole', 'Color yarn', 'Oil stain', 'Drop needle',
            'Patches', 'Crease mark', 'Thick yarn', 'Compacting foot mark',
            'Pulled yarn', 'Fabric joint', 'Stain', 'Other',
        ];
        $defectSizeOptions = [
            'Up to 3"' => 1,
            'Over 3" up to 6"' => 2,
            'Over 6" up to 9"' => 3,
            'Over 9"' => 4,
            'Hole/opening over 1"' => 4,
        ];
        return view('admin.fabric-records.edit', compact('fabric_record', 'buyers', 'styles', 'suppliers', 'defectTypes', 'defectSizeOptions'));
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
            'gsm_target' => 'nullable|numeric',
            'width_target' => 'nullable|numeric',
            'shade_status' => 'nullable|in:approved,rejected,pending',
            'inspection_date' => 'nullable|date',
            'rolls' => 'nullable|array',
            'rolls.*.roll_no' => 'required_with:rolls|integer',
            'rolls.*.color' => 'nullable|string|max:50',
            'rolls.*.weight_kgs' => 'required_with:rolls|numeric',
            'rolls.*.width_front' => 'nullable|numeric',
            'rolls.*.width_middle' => 'nullable|numeric',
            'rolls.*.width_end' => 'nullable|numeric',
            'rolls.*.gsm' => 'nullable|numeric',
            'rolls.*.remarks' => 'nullable|string',
            'rolls.*.defects' => 'nullable|array',
            'rolls.*.defects.*.defect_type' => 'required_with:rolls.*.defects|string',
            'rolls.*.defects.*.metre_position' => 'nullable|integer',
            'rolls.*.defects.*.points' => 'nullable|integer|min:1|max:4',
            'rolls.*.defects.*.defect_size' => 'nullable|string',
            'rolls.*.defects.*.notes' => 'nullable|string',
        ]);

        $fabric_record->update($request->only(['record_date', 'buyer_id', 'style_id', 'supplier_id', 'fabric_type', 'color', 'ordered_kg', 'received_kg']));

        $gsmTarget = $request->input('gsm_target', $fabric_record->inspection?->gsm_target ?? 220);
        $widthTarget = $request->input('width_target', $fabric_record->inspection?->width_target ?? 180);

        if ($request->has('rolls')) {
            $fabric_record->rolls()->delete();
            $fabric_record->defects()->whereNull('inspection_roll_id')->delete();

            $approvedKg = 0;
            $rejectedKg = 0;
            $inspectedKg = 0;
            $totalPointsAllRolls = 0;
            $totalYards = 0;
            $widthSum = 0;
            $rollCount = 0;

            foreach ($request->input('rolls', []) as $rollData) {
                $roll = InspectionRoll::create([
                    'fabric_record_id' => $fabric_record->id,
                    'roll_no' => $rollData['roll_no'],
                    'color' => $rollData['color'] ?? null,
                    'weight_kgs' => $rollData['weight_kgs'],
                    'width_front' => $rollData['width_front'] ?? null,
                    'width_middle' => $rollData['width_middle'] ?? null,
                    'width_end' => $rollData['width_end'] ?? null,
                    'gsm' => $rollData['gsm'] ?? null,
                    'remarks' => $rollData['remarks'] ?? null,
                ]);

                $inspectedKg += (float) $rollData['weight_kgs'];

                if (!empty($rollData['defects'])) {
                    foreach ($rollData['defects'] as $defectData) {
                        if (!empty($defectData['defect_type'])) {
                            QualityDefect::create([
                                'fabric_record_id' => $fabric_record->id,
                                'inspection_roll_id' => $roll->id,
                                'defect_type' => $defectData['defect_type'],
                                'count' => 1,
                                'metre_position' => $defectData['metre_position'] ?? null,
                                'points' => $defectData['points'] ?? null,
                                'defect_size' => $defectData['defect_size'] ?? null,
                                'severity' => ($defectData['points'] ?? 0) >= 4 ? 'critical' : (($defectData['points'] ?? 0) >= 3 ? 'major' : 'minor'),
                                'notes' => $defectData['notes'] ?? null,
                            ]);
                        }
                    }
                }

                $roll->recalculate();

                if ($roll->result === 'pass') {
                    $approvedKg += (float) $rollData['weight_kgs'];
                } else {
                    $rejectedKg += (float) $rollData['weight_kgs'];
                }

                $totalPointsAllRolls += $roll->totalPoints();
                $totalYards += (float) $roll->roll_length_yards;
                $widthSum += $roll->avgWidth();
                $rollCount++;
            }

            $avgWidth = $rollCount > 0 ? $widthSum / $rollCount : 0;
            $overallPointsPer100SqYd = ($totalYards > 0 && $avgWidth > 0)
                ? round(($totalPointsAllRolls * 3600) / ($totalYards * $avgWidth), 1)
                : 0;
            $passPct = $inspectedKg > 0 ? round(($approvedKg / $inspectedKg) * 100, 2) : 0;

            $gsmActual = $fabric_record->rolls->where('gsm', '>', 0)->avg('gsm');
            $widthActual = $rollCount > 0 ? $widthSum / $rollCount : 0;

            InspectionDetail::updateOrCreate(
                ['fabric_record_id' => $fabric_record->id],
                [
                    'inspected_kg' => $inspectedKg,
                    'approved_kg' => $approvedKg,
                    'rejected_kg' => $rejectedKg,
                    'gsm_actual' => $gsmActual ? round($gsmActual, 2) : null,
                    'gsm_target' => $gsmTarget,
                    'width_actual' => round($widthActual, 2),
                    'width_target' => $widthTarget,
                    'pass_pct' => $passPct,
                    'shade_status' => $request->input('shade_status', 'pending'),
                    'inspected_by' => auth()->id(),
                    'inspection_date' => $request->input('inspection_date'),
                ]
            );
        } else {
            $insp = $request->only(['shade_status', 'inspection_date']);
            $insp['gsm_target'] = $gsmTarget;
            $insp['width_target'] = $widthTarget;
            $insp['inspected_by'] = auth()->id();
            $insp = array_filter($insp, fn ($v) => $v !== null && $v !== '');
            InspectionDetail::updateOrCreate(['fabric_record_id' => $fabric_record->id], $insp);
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
