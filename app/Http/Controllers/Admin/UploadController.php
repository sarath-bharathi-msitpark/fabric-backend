<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FabricTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\FabricImport;
use App\Models\Buyer;
use App\Models\FabricRecord;
use App\Models\InspectionDetail;
use App\Models\Style;
use App\Models\Supplier;
use App\Models\UploadBatch;
use App\Services\AlertsEngineService;
use App\Services\SupplierRatingService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    public function index()
    {
        $batches = UploadBatch::with('uploader')->latest()->paginate(20);
        $buyers = Buyer::where('is_active', true)->orderBy('buyer_name')->get();
        $styles = Style::orderBy('style_number')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('supplier_name')->get();
        $fabricTypes = FabricRecord::distinct()->pluck('fabric_type')->sort()->values();
        return view('admin.upload.index', compact('batches', 'buyers', 'styles', 'suppliers', 'fabricTypes'));
    }

    public function fetchLot(Request $request)
    {
        $request->validate(['lot_no' => 'required|string']);

        $record = FabricRecord::with(['buyer', 'style', 'supplier', 'inspection'])
            ->where('lot_no', $request->lot_no)
            ->first();

        if (!$record) {
            return response()->json(['found' => false], 404);
        }

        return response()->json([
            'found' => true,
            'lot_no' => $record->lot_no,
            'buyer_id' => $record->buyer_id,
            'buyer_name' => $record->buyer?->buyer_name,
            'style_id' => $record->style_id,
            'style_number' => $record->style?->style_number,
            'supplier_id' => $record->supplier_id,
            'supplier_name' => $record->supplier?->supplier_name,
            'fabric_type' => $record->fabric_type,
            'color' => $record->color,
            'ordered_kg' => $record->ordered_kg,
            'received_kg' => $record->received_kg,
            'record_date' => $record->record_date?->format('Y-m-d'),
            'inspected_kg' => $record->inspection?->inspected_kg,
            'approved_kg' => $record->inspection?->approved_kg,
            'rejected_kg' => $record->inspection?->rejected_kg,
            'gsm_actual' => $record->inspection?->gsm_actual,
            'width_actual' => $record->inspection?->width_actual,
            'shade_status' => $record->inspection?->shade_status,
            'inspection_date' => $record->inspection?->inspection_date?->format('Y-m-d'),
        ]);
    }

    public function template()
    {
        return Excel::download(new FabricTemplateExport(), 'fabric-upload-template.xlsx');
    }

    public function validateFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'upload_type' => 'required|in:new_records,daily_update',
        ]);

        $import = new FabricImport([
            'uploaded_by' => auth()->id(),
            'upload_type' => $request->upload_type,
        ]);

        $errors = [];
        $rows = [];
        $validCount = 0;
        $errorCount = 0;

        try {
            $collection = Excel::toCollection($import, $request->file('file'))->first();
            $existingLots = \App\Models\FabricRecord::pluck('lot_no')->toArray();
            $seenLots = [];

            foreach ($collection as $idx => $row) {
                $rowArr = $row->toArray();
                $rowErrors = $this->validateRow($rowArr, $existingLots, $seenLots, $request->upload_type);
                if (!empty($rowErrors)) {
                    $errorCount++;
                    $errors[] = ['row' => $idx + 2, 'lot_no' => $rowArr['lot_no'] ?? '', 'errors' => $rowErrors, 'data' => $rowArr];
                } else {
                    $validCount++;
                    if (!empty($rowArr['lot_no'])) {
                        $seenLots[] = (string) $rowArr['lot_no'];
                    }
                }
                $rows[] = ['row' => $idx + 2, 'data' => $rowArr, 'errors' => $rowErrors, 'valid' => empty($rowErrors)];
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to read file: ' . $e->getMessage());
        }

        session([
            'upload.validation' => [
                'file_name' => $request->file('file')->getClientOriginalName(),
                'upload_type' => $request->upload_type,
                'valid_count' => $validCount,
                'error_count' => $errorCount,
                'rows' => $rows,
                'errors' => $errors,
            ],
        ]);

        return view('admin.upload.index', [
            'batches' => UploadBatch::with('uploader')->latest()->paginate(20),
            'validation' => session('upload.validation'),
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'upload_type' => 'required|in:new_records,daily_update',
        ]);

        $batch = UploadBatch::create([
            'file_name' => $request->file('file')->getClientOriginalName(),
            'upload_type' => $request->upload_type,
            'uploaded_by' => auth()->id(),
            'status' => 'validating',
            'total_rows' => 0,
            'success_rows' => 0,
            'error_rows' => 0,
        ]);

        $import = new FabricImport([
            'uploaded_by' => auth()->id(),
            'upload_batch_id' => $batch->id,
            'upload_type' => $request->upload_type,
        ]);

        try {
            Excel::import($import, $request->file('file'));

            $total = $import->successCount + count($import->errors);
            $batch->update([
                'total_rows' => $total,
                'success_rows' => $import->successCount,
                'error_rows' => count($import->errors),
                'status' => 'completed',
                'error_log' => $import->errors,
            ]);

            app(SupplierRatingService::class)->recalculateAll();
            app(AlertsEngineService::class)->scan();

            session()->forget('upload.validation');

            return redirect()->route('admin.upload.index')
                ->with('success', "Import complete: {$import->successCount} success, " . count($import->errors) . " failed.");
        } catch (\Throwable $e) {
            $batch->update(['status' => 'failed', 'error_log' => [['errors' => [$e->getMessage()]]]]);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function storeManual(Request $request)
    {
        $this->authorize('upload data');

        $data = $request->validate([
            'record_date' => 'required|date',
            'buyer_id' => 'required|exists:buyers,id',
            'style_id' => 'required|exists:styles,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'lot_no' => 'required|string|max:50',
            'fabric_type' => 'required|string|max:50',
            'color' => 'required|string|max:50',
            'ordered_kg' => 'required|numeric|min:0',
            'received_kg' => 'required|numeric|min:0',
            'upload_type' => 'required|in:new_records,daily_update',
            'inspected_kg' => 'nullable|numeric|min:0',
            'approved_kg' => 'nullable|numeric|min:0',
            'rejected_kg' => 'nullable|numeric|min:0',
            'gsm_actual' => 'nullable|numeric|min:0',
            'width_actual' => 'nullable|numeric|min:0',
            'shade_status' => 'nullable|in:approved,rejected,pending',
            'inspection_date' => 'nullable|date',
        ]);

        $existingLots = FabricRecord::pluck('lot_no')->toArray();
        $lotNo = $data['lot_no'];

        if ($data['upload_type'] === 'new_records' && in_array($lotNo, $existingLots)) {
            return back()->with('error', "Lot No '{$lotNo}' already exists. Use Daily Update to modify it.")
                ->withInput();
        }
        if ($data['upload_type'] === 'daily_update' && !in_array($lotNo, $existingLots)) {
            return back()->with('error', "Lot No '{$lotNo}' does not exist. Use New Records to create it.")
                ->withInput();
        }

        $batch = UploadBatch::create([
            'file_name' => 'Manual Entry — ' . $lotNo,
            'upload_type' => $data['upload_type'],
            'uploaded_by' => auth()->id(),
            'status' => 'validating',
            'total_rows' => 1,
            'success_rows' => 0,
            'error_rows' => 0,
        ]);

        try {
            $record = FabricRecord::updateOrCreate(
                ['lot_no' => $lotNo],
                [
                    'record_date' => $data['record_date'],
                    'buyer_id' => $data['buyer_id'],
                    'style_id' => $data['style_id'],
                    'supplier_id' => $data['supplier_id'],
                    'fabric_type' => $data['fabric_type'],
                    'color' => $data['color'],
                    'ordered_kg' => $data['ordered_kg'],
                    'received_kg' => $data['received_kg'],
                    'uploaded_by' => auth()->id(),
                    'upload_batch_id' => $batch->id,
                ]
            );

            $inspected = (float) ($data['inspected_kg'] ?? 0);
            $approved = (float) ($data['approved_kg'] ?? 0);
            $rejected = (float) ($data['rejected_kg'] ?? 0);

            if ($inspected > 0 || $approved > 0 || $rejected > 0 || !empty($data['gsm_actual']) || !empty($data['width_actual'])) {
                $passPct = $inspected > 0 ? round(($approved / $inspected) * 100, 2) : 0;
                InspectionDetail::updateOrCreate(
                    ['fabric_record_id' => $record->id],
                    [
                        'inspected_kg' => $inspected,
                        'approved_kg' => $approved,
                        'rejected_kg' => $rejected,
                        'gsm_actual' => $data['gsm_actual'] ?? null,
                        'gsm_target' => $record->inspection?->gsm_target ?? 220,
                        'width_actual' => $data['width_actual'] ?? null,
                        'width_target' => $record->inspection?->width_target ?? 180,
                        'pass_pct' => $passPct,
                        'shade_status' => $data['shade_status'] ?? 'pending',
                        'inspected_by' => auth()->id(),
                        'inspection_date' => $data['inspection_date'] ?? $data['record_date'],
                    ]
                );
            }

            $batch->update([
                'status' => 'completed',
                'success_rows' => 1,
                'error_rows' => 0,
            ]);

            app(SupplierRatingService::class)->recalculate($record->supplier);
            app(AlertsEngineService::class)->scan($record->id);

            return redirect()->route('admin.upload.index')
                ->with('success', "Record for lot '{$lotNo}' saved successfully.");
        } catch (\Throwable $e) {
            $batch->update(['status' => 'failed', 'error_log' => [['errors' => [$e->getMessage()]]]]);
            return back()->with('error', 'Failed to save record: ' . $e->getMessage())->withInput();
        }
    }

    protected function validateRow(array $row, array $existingLots, array $seenLots, string $uploadType): array
    {
        $errors = [];
        $required = ['date', 'buyer', 'style', 'supplier', 'lot_no', 'fabric_type', 'color', 'ordered_kg', 'received_kg'];
        foreach ($required as $field) {
            if (empty($row[$field]) && $row[$field] !== '0') {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        if (!empty($row['date'])) {
            $dateStr = (string) $row['date'];
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr) && !\DateTime::createFromFormat('Y-m-d', $dateStr)) {
                if (is_numeric($row['date'])) {
                    $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $row['date']);
                    if (!$excelDate) $errors[] = 'Date must be YYYY-MM-DD format.';
                } else {
                    $errors[] = 'Date must be YYYY-MM-DD format.';
                }
            }
        }
        foreach (['ordered_kg', 'received_kg', 'inspected_kg', 'approved_kg', 'rejected_kg'] as $numField) {
            if (!empty($row[$numField]) && is_string($row[$numField]) && preg_match('/[,$₹€£]/', $row[$numField])) {
                $errors[] = ucfirst(str_replace('_', ' ', $numField)) . ' must be numeric (no commas or currency).';
            }
        }
        if (!empty($row['lot_no'])) {
            $lot = (string) $row['lot_no'];
            if ($uploadType === 'new_records') {
                if (in_array($lot, $existingLots) || in_array($lot, $seenLots)) {
                    $errors[] = 'Lot No must be unique.';
                }
            } else {
                if (!in_array($lot, $existingLots)) {
                    $errors[] = 'Lot No does not exist for daily update.';
                }
            }
        }
        return $errors;
    }
}
