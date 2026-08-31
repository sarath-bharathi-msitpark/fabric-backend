<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FabricTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'Date', 'Buyer', 'Style', 'Supplier', 'Lot No', 'Fabric Type', 'Color',
            'Ordered Kg', 'Received Kg', 'Inspected Kg', 'Approved Kg', 'Rejected Kg',
            'GSM', 'Width', 'Pass %', 'Defect Type', 'Defect Count', 'Severity',
        ];
    }

    public function array(): array
    {
        return [
            [now()->format('Y-m-d'), 'INR Global Sourcing', 'STY-1001', 'Tirupur Mills Ltd', 'LOT-DEMO-001', 'Cotton Fleece', 'Navy', 1000, 1000, 1000, 980, 20, 220, 180, 98, 'Stain', 5, 'minor'],
        ];
    }
}
