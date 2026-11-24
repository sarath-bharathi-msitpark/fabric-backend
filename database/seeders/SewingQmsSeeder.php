<?php

namespace Database\Seeders;

use App\Models\SewingDefectMaster;
use App\Models\SewingInspectionRule;
use App\Models\SewingNumberSequence;
use App\Models\SewingRoundConfig;
use Illuminate\Database\Seeder;

class SewingQmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDefectMaster();
        $this->seedRules();
        $this->seedRounds();
        $this->seedNumbering();
    }

    protected function seedDefectMaster(): void
    {
        $rows = [
            // Sewing S1-S17
            ['sewing', 'S1', 'Broken stitches', 'Incorrect thread tension', 'Re-set thread tension to standard', 'Daily tension check at start of shift'],
            ['sewing', 'S2', 'Broken stitches', 'Needle too small or worn', 'Replace needle with correct size', 'Follow needle change schedule'],
            ['sewing', 'S3', 'Broken stitches', 'Poor thread quality', 'Replace thread cone', 'Incoming thread inspection'],
            ['sewing', 'S4', 'Misaligned buttons and button holes', 'Buttonhole machine setting drift', 'Re-calibrate buttonhole machine', 'Setup approval before style change'],
            ['sewing', 'S5', 'Open seams', 'Inadequate seam allowance or feeding', 'Re-stitch with correct seam allowance', 'Operator training on seam allowance'],
            ['sewing', 'S6', 'Puckering', 'High thread tension', 'Reduce thread tension', 'Maintain tension log per machine'],
            ['sewing', 'S7', 'Puckering', 'Faulty fabric feeding', 'Adjust feed dog or differential', 'Machine maintenance schedule'],
            ['sewing', 'S8', 'Puckering', 'Wrong thread or needle combination', 'Use specified thread and needle', 'Material specification check'],
            ['sewing', 'S9', 'Size jump', 'Pattern or measurement deviation', 'Re-measure and correct pattern', 'Inline measurement check'],
            ['sewing', 'S10', 'Skipped stitches', 'Bent or blunt needle', 'Replace needle', 'Follow needle change schedule'],
            ['sewing', 'S11', 'Skipped stitches', 'Incorrect needle bar timing', 'Re-time needle bar', 'Mechanic maintenance schedule'],
            ['sewing', 'S12', 'Untrimmed threads', 'Trimmer not engaged', 'Engage and adjust trimmer', 'End-line thread check'],
            ['sewing', 'S13', 'Asymmetric seam joint', 'Misaligned panels during joining', 'Re-align and re-join panels', 'Notch matching training'],
            ['sewing', 'S14', 'Wavy seam', 'Excessive stretching during sewing', 'Re-sew with correct handling', 'Operator handling training'],
            ['sewing', 'S15', 'Wavy seam', 'Feed differential incorrect', 'Adjust differential feed', 'Machine setup verification'],
            ['sewing', 'S16', 'Oil stain', 'Machine oil leakage', 'Clean and seal oil leak', 'Daily machine cleaning'],
            ['sewing', 'S17', 'Handling stain', 'Unclean handling or table', 'Clean garment and workstation', '5S and hygiene training'],
            // Fabric F1-F10
            ['fabric', 'F1', 'Barriness', 'Yarn quality variation', 'Reject or replace fabric lot', 'Incoming fabric inspection'],
            ['fabric', 'F2', 'Contamination', 'Foreign fibre in fabric', 'Remove contaminated pieces', 'Supplier quality audit'],
            ['fabric', 'F3', 'Printing mistake', 'Print registration error', 'Reject printed panels', 'Print approval sample'],
            ['fabric', 'F4', 'Drop stitches / Fabric hole', 'Fabric damage during knitting', 'Replace defective panel', 'Fabric four-point inspection'],
            ['fabric', 'F5', 'Fabric press off', 'Knitting machine press-off', 'Segregate affected fabric', 'Knitting machine monitoring'],
            ['fabric', 'F6', 'Needle or Sinker lines', 'Knitting needle or sinker fault', 'Replace needle or sinker', 'Knitting maintenance schedule'],
            ['fabric', 'F7', 'Slub', 'Yarn slub', 'Remove slub pieces', 'Yarn clearing'],
            ['fabric', 'F8', 'Snarling', 'Yarn twist instability', 'Replace affected fabric', 'Yarn conditioning'],
            ['fabric', 'F9', 'Yarn streaks', 'Uneven yarn', 'Segregate lot', 'Yarn lot inspection'],
            ['fabric', 'F10', 'Hole', 'Fabric hole', 'Replace panel', 'Fabric handling care'],
            // Cutting C1-C2
            ['cutting', 'C1', 'Shade variation', 'Mixed shade panels', 'Sort by shade and re-bundle', 'Shade band verification'],
            ['cutting', 'C2', 'Wrong size', 'Wrong pattern or panel size', 'Re-cut correct size', 'Pattern check before cutting'],
            // Trims T1-T3
            ['trims', 'T1', 'Broken trim', 'Faulty trim', 'Replace trim', 'Trims inspection'],
            ['trims', 'T2', 'Broken trim', 'Weak attachment', 'Re-attach trim', 'Attachment strength check'],
            ['trims', 'T3', 'Wrong trim', 'Wrong trim issued', 'Replace with correct trim', 'Trims kit verification'],
        ];

        foreach ($rows as [$type, $code, $name, $cause, $corrective, $preventive]) {
            SewingDefectMaster::firstOrCreate(
                ['capa_code' => $code],
                [
                    'defect_type' => $type,
                    'defect_name' => $name,
                    'root_cause' => $cause,
                    'corrective_action' => $corrective,
                    'preventive_action' => $preventive,
                    'is_active' => true,
                ]
            );
        }
    }

    protected function seedRules(): void
    {
        SewingInspectionRule::firstOrCreate(
            ['version' => 1],
            [
                'pieces_per_audit' => SewingInspectionRule::DEFAULT_PIECES,
                'green_max' => SewingInspectionRule::DEFAULT_GREEN_MAX,
                'amber_max' => SewingInspectionRule::DEFAULT_AMBER_MAX,
                'default_rounds' => SewingInspectionRule::DEFAULT_ROUNDS,
                'is_active' => true,
            ]
        );
    }

    protected function seedRounds(): void
    {
        foreach (range(1, 4) as $round) {
            SewingRoundConfig::firstOrCreate(
                ['round_no' => $round],
                ['is_active' => true]
            );
        }
    }

    protected function seedNumbering(): void
    {
        SewingNumberSequence::firstOrCreate(
            ['key' => 'sewing_inspection'],
            ['prefix' => 'SII-', 'next_number' => 1, 'padding' => 4]
        );
    }
}
