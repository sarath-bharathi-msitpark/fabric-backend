<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FabricTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\FabricImport;
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
        return view('admin.upload.index', compact('batches'));
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
