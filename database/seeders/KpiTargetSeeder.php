<?php

namespace Database\Seeders;

use App\Models\KpiTarget;
use Illuminate\Database\Seeder;

class KpiTargetSeeder extends Seeder
{
    public function run(): void
    {
        $targets = [
            ['inspection_completed', 100, 'gte'],
            ['pass_rate', 98, 'gte'],
            ['rejection_rate', 2, 'lte'],
            ['available_for_cutting', 100, 'gte'],
            ['shade_approval', 100, 'gte'],
            ['delayed_lots', 0, 'lte'],
            ['gsm_variation_pct', 5, 'lte'],
            ['width_variation_cm', 1, 'lte'],
            ['bowing_pct', 3, 'lte'],
            ['skewing_pct', 3, 'lte'],
        ];

        foreach ($targets as [$key, $value, $comparison]) {
            KpiTarget::updateOrCreate(
                ['kpi_key' => $key],
                ['target_value' => $value, 'comparison' => $comparison]
            );
        }
    }
}
