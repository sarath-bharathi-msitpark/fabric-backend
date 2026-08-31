<?php

namespace App\Imports;

use App\Models\Buyer;
use App\Models\FabricRecord;
use App\Models\InspectionDetail;
use App\Models\QualityDefect;
use App\Models\Style;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class FabricImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    use SkipsFailures;

    public ?int $uploadBatchId = null;
    public ?int $uploadedBy = null;
    public string $uploadType = 'new_records';
    public array $errors = [];
    public int $successCount = 0;

    public function __construct(array $opts = [])
    {
        $this->uploadedBy = $opts['uploaded_by'] ?? null;
        $this->uploadBatchId = $opts['upload_batch_id'] ?? null;
        $this->uploadType = $opts['upload_type'] ?? 'new_records';
    }

    public function collection(Collection $rows): void
    {
        $userId = $this->uploadedBy ?? auth()->id();
        $existingLots = FabricRecord::pluck('lot_no')->toArray();
        $seenLots = [];

        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            $rowErrors = $this->validateRow($rowArray, $existingLots, $seenLots);
            if (!empty($rowErrors)) {
                $this->errors[] = ['row' => $index + 2, 'lot_no' => $rowArray['lot_no'] ?? '', 'errors' => $rowErrors];
                continue;
            }

            $lotNo = (string) $rowArray['lot_no'];
            $seenLots[] = $lotNo;

            try {
                $buyer = Buyer::firstOrCreate(['buyer_name' => $rowArray['buyer']]);
                $style = Style::firstOrCreate(
                    ['style_number' => $rowArray['style']],
                    ['buyer_id' => $buyer->id, 'order_quantity' => $this->num($rowArray, 'ordered_kg'), 'target_date' => now()->addDays(30), 'status' => 'planning']
                );
                $supplier = Supplier::firstOrCreate(['supplier_name' => $rowArray['supplier']]);

                $record = FabricRecord::updateOrCreate(
                    ['lot_no' => $lotNo],
                    [
                        'record_date' => $rowArray['date'],
                        'buyer_id' => $buyer->id,
                        'style_id' => $style->id,
                        'supplier_id' => $supplier->id,
                        'fabric_type' => $rowArray['fabric_type'],
                        'color' => $rowArray['color'],
                        'ordered_kg' => $this->num($rowArray, 'ordered_kg'),
                        'received_kg' => $this->num($rowArray, 'received_kg'),
                        'uploaded_by' => $userId,
                        'upload_batch_id' => $this->uploadBatchId,
                    ]
                );

                $inspected = $this->num($rowArray, 'inspected_kg');
                $approved = $this->num($rowArray, 'approved_kg');
                $rejected = $this->num($rowArray, 'rejected_kg');
                $passPct = $inspected > 0 ? round(($approved / $inspected) * 100, 2) : 0;

                InspectionDetail::updateOrCreate(
                    ['fabric_record_id' => $record->id],
                    [
                        'inspected_kg' => $inspected,
                        'approved_kg' => $approved,
                        'rejected_kg' => $rejected,
                        'gsm_actual' => $this->nullableNum($rowArray, 'gsm'),
                        'gsm_target' => 220,
                        'width_actual' => $this->nullableNum($rowArray, 'width'),
                        'width_target' => 180,
                        'pass_pct' => $passPct,
                        'shade_status' => $this->normalizeShade($rowArray['shade_status'] ?? 'pending'),
                        'inspected_by' => $userId,
                        'inspection_date' => $rowArray['date'],
                    ]
                );

                if (!empty($rowArray['defect_type'])) {
                    QualityDefect::updateOrCreate(
                        ['fabric_record_id' => $record->id, 'defect_type' => $rowArray['defect_type']],
                        [
                            'count' => (int) ($rowArray['defect_count'] ?? 0),
                            'severity' => strtolower($rowArray['severity'] ?? 'minor'),
                        ]
                    );
                }

                $this->successCount++;
            } catch (\Throwable $e) {
                $this->errors[] = ['row' => $index + 2, 'lot_no' => $lotNo, 'errors' => [$e->getMessage()]];
            }
        }
    }

    protected function validateRow(array $row, array $existingLots, array $seenLots): array
    {
        $errors = [];
        $required = ['date', 'buyer', 'style', 'supplier', 'lot_no', 'fabric_type', 'color', 'ordered_kg', 'received_kg'];
        foreach ($required as $field) {
            if (empty($row[$field]) && $row[$field] !== '0') {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        if (!empty($row['date'])) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['date']) && !\DateTime::createFromFormat('Y-m-d', $row['date'])) {
                $errors[] = 'Date must be YYYY-MM-DD format.';
            }
        }
        foreach (['ordered_kg', 'received_kg', 'inspected_kg', 'approved_kg', 'rejected_kg'] as $numField) {
            if (!empty($row[$numField]) && preg_match('/[,$]/', $row[$numField])) {
                $errors[] = ucfirst(str_replace('_', ' ', $numField)) . ' must be numeric (no commas or currency).';
            }
        }
        if (!empty($row['lot_no'])) {
            $lot = (string) $row['lot_no'];
            if ($this->uploadType === 'new_records') {
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

    protected function num(array $row, string $key): float
    {
        $v = $row[$key] ?? 0;
        if (is_numeric($v)) return (float) $v;
        return (float) preg_replace('/[^0-9.\-]/', '', (string) $v) ?: 0;
    }

    protected function nullableNum(array $row, string $key): ?float
    {
        $v = $row[$key] ?? null;
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return (float) $v;
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $v);
        return $clean !== '' ? (float) $clean : null;
    }

    protected function normalizeShade(string $val): string
    {
        $val = strtolower(trim($val));
        return in_array($val, ['approved', 'rejected', 'pending']) ? $val : 'pending';
    }

    public function rules(): array
    {
        return [
            'date' => 'required',
            'buyer' => 'required',
            'style' => 'required',
            'supplier' => 'required',
            'lot_no' => 'required',
            'fabric_type' => 'required',
            'color' => 'required',
            'ordered_kg' => 'required',
            'received_kg' => 'required',
        ];
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
