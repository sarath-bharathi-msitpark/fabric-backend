<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\FabricRecord;
use App\Models\InspectionDetail;
use App\Models\QualityDefect;
use App\Models\Style;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $buyers = collect([
            ['INR Global Sourcing', 'Ramesh Kumar', 'ramesh@inrglobal.in', '9876543210'],
            ['Prime Apparel UK', 'Sarah Connor', 'sarah@primeapparel.co.uk', '+44 1234567890'],
            ['Nordic Textiles AB', 'Erik Lund', 'erik@nordictextiles.se', '+46 9876543210'],
        ])->map(fn ($b) => Buyer::create([
            'buyer_name' => $b[0], 'contact_person' => $b[1],
            'email' => $b[2], 'phone' => $b[3], 'is_active' => true,
        ]));

        $styles = collect([
            ['STY-1001', $buyers[0]->id, 5000, now()->addDays(20), 'in_progress'],
            ['STY-1002', $buyers[1]->id, 3200, now()->addDays(10), 'in_progress'],
            ['STY-1003', $buyers[2]->id, 8000, now()->subDays(5), 'in_progress'],
            ['STY-1004', $buyers[0]->id, 2500, now()->subDays(15), 'completed'],
        ])->map(fn ($s) => Style::create([
            'style_number' => $s[0], 'buyer_id' => $s[1],
            'order_quantity' => $s[2], 'target_date' => $s[3], 'status' => $s[4],
        ]));

        $suppliers = collect([
            ['Tirupur Mills Ltd', 'TM-01', 'Kannan', '9087654321', 'sales@tirupurmills.in', 96.5, 98.2, 'excellent'],
            ['Coimbatore Fabrics', 'CB-02', 'Lakshmi', '9012345678', 'info@cbefabrics.in', 91.0, 95.5, 'good'],
            ['Karur Textiles', 'KR-03', 'Murugan', '9123456780', 'orders@karurtextiles.in', 84.5, 89.0, 'average'],
            ['Erode Knit Fab', 'ER-04', 'Velu', '9234567801', 'contact@erodeknit.in', 75.0, 82.0, 'poor'],
        ])->map(fn ($s) => Supplier::create([
            'supplier_name' => $s[0], 'mill_code' => $s[1], 'contact_person' => $s[2],
            'phone' => $s[3], 'email' => $s[4],
            'on_time_pct' => $s[5], 'quality_pct' => $s[6], 'rating' => $s[7],
        ]));

        $admin = User::where('email', 'admin@fabricsourcing.in')->first();
        $colors = ['Navy', 'Black', 'White', 'Olive', 'Charcoal', 'Maroon'];

        $lots = [
            ['LOT-2401-001', $styles[0]->id, $suppliers[0]->id, 'Cotton Fleece', $colors[0], 1200, 1200, true],
            ['LOT-2401-002', $styles[0]->id, $suppliers[1]->id, 'Cotton Fleece', $colors[1], 800, 760, true],
            ['LOT-2401-003', $styles[1]->id, $suppliers[2]->id, 'Polyester Knit', $colors[3], 1500, 1500, true],
            ['LOT-2402-004', $styles[1]->id, $suppliers[3]->id, 'Polyester Knit', $colors[2], 900, 720, false],
            ['LOT-2402-005', $styles[2]->id, $suppliers[0]->id, 'Cotton Spandex', $colors[5], 2000, 2000, true],
            ['LOT-2402-006', $styles[2]->id, $suppliers[1]->id, 'Cotton Spandex', $colors[4], 1800, 1620, false],
            ['LOT-2403-007', $styles[3]->id, $suppliers[0]->id, 'French Terry', $colors[1], 2500, 2500, true],
            ['LOT-2403-008', $styles[0]->id, $suppliers[2]->id, 'Cotton Fleece', $colors[0], 600, 540, false],
        ];

        foreach ($lots as $lot) {
            $style = Style::find($lot[1]);
            $record = FabricRecord::create([
                'record_date' => now()->subDays(rand(1, 25)),
                'buyer_id' => $style->buyer_id,
                'style_id' => $lot[1],
                'supplier_id' => $lot[2],
                'lot_no' => $lot[0],
                'fabric_type' => $lot[3],
                'color' => $lot[4],
                'ordered_kg' => $lot[5],
                'received_kg' => $lot[6],
                'uploaded_by' => $admin->id,
            ]);

            $inspected = $lot[7] ? $lot[6] : $lot[6] * 0.95;
            $rejected = $lot[7] ? $lot[6] * 0.02 : $lot[6] * 0.12;
            $approved = max(0, $inspected - $rejected);
            $passPct = $inspected > 0 ? round(($approved / $inspected) * 100, 2) : 0;
            $gsmTarget = 220;
            $gsmActual = $gsmTarget + rand(-12, 12);
            $widthTarget = 180;
            $widthActual = $widthTarget + rand(-2, 2);

            InspectionDetail::create([
                'fabric_record_id' => $record->id,
                'inspected_kg' => $inspected,
                'approved_kg' => $approved,
                'rejected_kg' => $rejected,
                'gsm_actual' => $gsmActual,
                'gsm_target' => $gsmTarget,
                'width_actual' => $widthActual,
                'width_target' => $widthTarget,
                'pass_pct' => $passPct,
                'bowing_pct' => rand(1, 4),
                'skewing_pct' => rand(1, 4),
                'shade_status' => $lot[7] ? 'approved' : (rand(0, 1) ? 'rejected' : 'pending'),
                'inspected_by' => $admin->id,
                'inspection_date' => $record->record_date->addDays(1),
            ]);

            $defectCount = rand(0, 3);
            for ($d = 0; $d < $defectCount; $d++) {
                QualityDefect::create([
                    'fabric_record_id' => $record->id,
                    'defect_type' => ['Hole', 'Stain', 'Slub', 'Misprint', 'Shade Variation', 'Crease'][array_rand([0,1,2,3,4,5])],
                    'count' => rand(1, 8),
                    'severity' => ['minor', 'major', 'critical'][array_rand([0,1,2])],
                    'notes' => 'Detected during inline inspection',
                ]);
            }
        }
    }
}
