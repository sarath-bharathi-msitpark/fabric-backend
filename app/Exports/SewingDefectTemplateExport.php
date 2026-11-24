<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SewingDefectTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'Type',
            'CAPA Code',
            'Defect Name',
            'Root Cause',
            'Corrective Action',
            'Preventive Action',
        ];
    }

    public function array(): array
    {
        return [
            ['sewing', 'S1', 'Broken stitches', 'Incorrect needle tension', 'Re-thread and re-stitch', 'Verify needle and tension daily'],
            ['fabric', 'F1', 'Barriness', 'Yarn irregularity', 'Reject the roll', 'Strengthen incoming inspection'],
        ];
    }
}
