<?php

namespace App\Imports;

use App\Models\SewingDefectMaster;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SewingDefectImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array<string, mixed>> */
    public array $errors = [];

    public int $successCount = 0;

    public function collection(Collection $rows)
    {
        $rowNum = 1;

        foreach ($rows as $row) {
            $rowNum++;

            $type = $this->normaliseType((string) ($row['type'] ?? $row['defect_type'] ?? ''));
            $capa = strtoupper(trim((string) ($row['capa_code'] ?? $row['capa'] ?? '')));
            $name = trim((string) ($row['defect_name'] ?? $row['defect'] ?? ''));
            $rootCause = trim((string) ($row['root_cause'] ?? ''));
            $corrective = trim((string) ($row['corrective_action'] ?? ''));
            $preventive = trim((string) ($row['preventive_action'] ?? ''));

            $rowErrors = [];
            if ($type === null) {
                $rowErrors[] = 'Type must be one of: '.implode(', ', array_keys(SewingDefectMaster::TYPES));
            }
            if ($capa === '') {
                $rowErrors[] = 'CAPA code is required';
            }
            if ($name === '') {
                $rowErrors[] = 'Defect name is required';
            }

            if ($rowErrors) {
                $this->errors[] = ['row' => $rowNum, 'capa' => $capa, 'errors' => $rowErrors];

                continue;
            }

            SewingDefectMaster::updateOrCreate(
                ['capa_code' => $capa],
                [
                    'defect_type' => $type,
                    'defect_name' => $name,
                    'root_cause' => $rootCause !== '' ? $rootCause : '—',
                    'corrective_action' => $corrective !== '' ? $corrective : '—',
                    'preventive_action' => $preventive !== '' ? $preventive : '—',
                    'is_active' => true,
                ]
            );

            $this->successCount++;
        }
    }

    protected function normaliseType(string $value): ?string
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        if (array_key_exists($value, SewingDefectMaster::TYPES)) {
            return $value;
        }

        foreach (SewingDefectMaster::TYPES as $key => $label) {
            if (strtolower($label) === $value) {
                return $key;
            }
        }

        return null;
    }
}
