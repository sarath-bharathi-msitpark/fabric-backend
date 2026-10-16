<?php

namespace Database\Seeders;

use App\Models\CuttingChecklistTemplate;
use App\Models\CuttingNumberSequence;
use App\Models\CuttingTable;
use Illuminate\Database\Seeder;

class CuttingQmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedChecklists();
        $this->seedTables();
        $this->seedNumbering();
    }

    protected function seedChecklists(): void
    {
        $templates = [
            'lay' => [
                'signoff_roles' => ['cutting_qc', 'cutting_incharge'],
                'checkpoints' => [
                    'Marker placement', 'Parts marking', 'Lay ends', 'Lay sides',
                    'Tension', 'Leaning', 'Marker direction', 'Stripe/check matching',
                ],
            ],
            'cut_panel' => [
                'signoff_roles' => ['cutting_qc', 'cutting_incharge'],
                'checkpoints' => [
                    'Notch placement', 'Missing notches', 'Notch depth',
                    'Cut quality / cut part symmetry / grain line direction', 'Panel replacement',
                ],
            ],
            'bundling' => [
                'signoff_roles' => ['cutting_qc', 'cutting_incharge', 'qam'],
                'checkpoints' => [
                    'Bundle card details', 'Panel numbering', 'Sticker placement',
                    'Sticker quality', 'Shading between panels', 'Panel replacement',
                    'Stickered on correct side', 'Panel size',
                ],
            ],
        ];

        foreach ($templates as $stage => $config) {
            $template = CuttingChecklistTemplate::firstOrCreate(
                ['stage' => $stage, 'version' => 1],
                ['signoff_roles' => $config['signoff_roles'], 'is_active' => true]
            );

            foreach ($config['checkpoints'] as $index => $name) {
                $template->checkpoints()->firstOrCreate(
                    ['name' => $name],
                    ['sort_order' => $index + 1, 'is_active' => true]
                );
            }
        }
    }

    protected function seedTables(): void
    {
        foreach (range(1, 5) as $i) {
            CuttingTable::firstOrCreate(
                ['table_no' => 'Table '.$i],
                ['name' => 'Cutting Table '.$i, 'is_active' => true]
            );
        }
    }

    protected function seedNumbering(): void
    {
        CuttingNumberSequence::firstOrCreate(
            ['key' => 'cutting_audit'],
            ['prefix' => 'CQA-', 'next_number' => 1001, 'padding' => 4]
        );
    }
}
