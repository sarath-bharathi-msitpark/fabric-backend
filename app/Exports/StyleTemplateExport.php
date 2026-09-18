<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StyleTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'Style Number',
            'Buyer',
            'Order Qty Kg',
            'Target Date',
            'Status',
            'Fabric Type',
            'Color',
            'GSM Target',
            'Width Target Inches',
        ];
    }

    public function array(): array
    {
        return [
            ['STY-1001', 'INR Global Sourcing', 5000, now()->addDays(30)->format('Y-m-d'), 'planning', 'Cotton Fleece', 'Navy', 220, 180],
            ['STY-1002', 'Nordic Textiles AB', 3000, now()->addDays(45)->format('Y-m-d'), 'in_progress', 'French Terry', 'Black', 240, 170],
        ];
    }
}
