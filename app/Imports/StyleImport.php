<?php

namespace App\Imports;

use App\Models\Buyer;
use App\Models\Style;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class StyleImport implements ToCollection, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    public array $errors = [];
    public int $successCount = 0;

    public function collection(Collection $rows)
    {
        $rowNum = 1;
        foreach ($rows as $row) {
            $rowNum++;
            $data = [
                'style_number' => trim($row['style_number'] ?? ''),
                'buyer_name' => trim($row['buyer'] ?? ''),
                'order_quantity' => $row['order_qty_kg'] ?? null,
                'target_date' => $row['target_date'] ?? null,
                'status' => trim($row['status'] ?? 'planning'),
                'fabric_type' => trim($row['fabric_type'] ?? ''),
                'color' => trim($row['color'] ?? ''),
                'gsm_target' => $row['gsm_target'] ?? null,
                'width_target' => $row['width_target_inches'] ?? null,
            ];

            $errors = [];

            if (empty($data['style_number'])) {
                $errors[] = 'Style Number is required';
            }
            if (empty($data['buyer_name'])) {
                $errors[] = 'Buyer is required';
            }
            if (!empty($errors)) {
                $this->errors[] = ['row' => $rowNum, 'style' => $data['style_number'], 'errors' => $errors];
                continue;
            }

            $buyer = Buyer::firstOrCreate(['buyer_name' => $data['buyer_name']]);
            $status = in_array($data['status'], ['planning', 'in_progress', 'completed', 'on_hold']) ? $data['status'] : 'planning';

            Style::updateOrCreate(
                ['style_number' => $data['style_number']],
                [
                    'buyer_id' => $buyer->id,
                    'order_quantity' => is_numeric($data['order_quantity']) ? $data['order_quantity'] : 0,
                    'target_date' => $data['target_date'] ?: now()->addDays(30)->format('Y-m-d'),
                    'status' => $status,
                    'fabric_type' => $data['fabric_type'] ?: null,
                    'color' => $data['color'] ?: null,
                    'gsm_target' => is_numeric($data['gsm_target']) ? $data['gsm_target'] : null,
                    'width_target' => is_numeric($data['width_target']) ? $data['width_target'] : null,
                ]
            );

            $this->successCount++;
        }
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
