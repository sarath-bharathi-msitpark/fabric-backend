<?php

namespace App\Exports;

use App\Models\FabricRecord;
use App\Models\InspectionRoll;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class FourPointInspectionReportExport
{
    protected FabricRecord $record;
    protected string $reportNo;

    protected const DEFECT_COLUMNS = [
        'Hole', 'GSM hole', 'Color yarn', 'Oil stain', 'Drop needle',
        'Patches', 'Crease mark', 'Thick yarn', 'Compacting foot mark',
        'Pulled yarn', 'Fabric joint',
    ];

    public function __construct(FabricRecord $record, string $reportNo)
    {
        $this->record = $record;
        $this->reportNo = $reportNo;
    }

    public function download(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $this->buildSheet1($spreadsheet->getActiveSheet());
        $this->buildSheet2($spreadsheet->createSheet());

        $writer = new XlsxWriter($spreadsheet);
        $fileName = '4-point-inspection-' . $this->record->lot_no . '-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    protected function thinBorder(): array
    {
        return [
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ];
    }

    protected function lightBlueFill(): array
    {
        return ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D6E4F0']]];
    }

    protected function greenFill(): array
    {
        return ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '22C55E']]];
    }

    protected function redFill(): array
    {
        return ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC2626']]];
    }

    protected function buildSheet1($sheet): void
    {
        $sheet->setTitle('4 Point Inspection Report');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(1);
        $sheet->getPageMargins()->setTop(0.3);
        $sheet->getPageMargins()->setBottom(0.3);
        $sheet->getPageMargins()->setLeft(0.3);
        $sheet->getPageMargins()->setRight(0.3);

        $rolls = $this->record->rolls->sortBy('roll_no')->values();
        $company = config('app.name', 'INR Global Sourcing');

        $totalCols = 5 + count(self::DEFECT_COLUMNS) + 6 + 2 + 2;

        $col = 1;
        $sheet->mergeCells([$col, 1, $col + 2, 2]);
        $sheet->getCell([$col, 1])->setValue($company);
        $sheet->getCell([$col, 1])->getStyle()->getFont()->setBold(true)->setSize(12);

        $titleEndCol = $totalCols;
        $sheet->mergeCells([$col + 3, 1, $titleEndCol, 2]);
        $sheet->getCell([$col + 3, 1])->setValue($company . ' - 4 Point Fabric Inspection Report');
        $sheet->getCell([$col + 3, 1])->getStyle()->getFont()->setBold(true)->setSize(14);
        $sheet->getCell([$col + 3, 1])->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 3;
        $sheet->mergeCells([1, $row, 3, $row]);
        $sheet->getCell([1, $row])->setValue('REPORT#: ' . $this->reportNo)
            ->getStyle()->getFont()->setBold(true);
        $sheet->getStyle([1, $row, 3, $row])->applyFromArray($this->lightBlueFill());

        $sheet->mergeCells([4, $row, 7, $row]);
        $sheet->getCell([4, $row])->setValue('Doc#: QC-FI-001 · REV#: 01 · IMP.ON: 01/07/2026');
        $sheet->getStyle([4, $row, 7, $row])->applyFromArray($this->lightBlueFill());

        $sheet->mergeCells([8, $row, $totalCols, $row]);
        $sheet->getCell([8, $row])->setValue('Passing Criteria: Per Roll = 20 pts/100 sq yd · Overall Shipment = 18 pts/100 sq yd');
        $sheet->getStyle([8, $row, $totalCols, $row])->applyFromArray($this->lightBlueFill());

        $row = 4;
        $formulaCol = (int) ceil($totalCols * 0.7);
        $sheet->mergeCells([1, $row, $formulaCol - 1, $row + 5]);
        $sheet->getCell([1, $row])->setValue("ROLL LENGTH IN YARDS = (ROLL WEIGHT KGS × 1000 × 100 × 1.0936) / (GSM × FABRIC WIDTH IN INCHES × 2.54 × 0.9144)\n\nPOINTS/100 SQ. YARD = (TOTAL POINTS × 3600) / (YARDS INSPECTED × WIDTH IN INCHES)");
        $sheet->getCell([1, $row])->getStyle()->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle([1, $row, $formulaCol - 1, $row + 5])->applyFromArray($this->lightBlueFill());
        $sheet->getStyle([1, $row, $formulaCol - 1, $row + 5])->applyFromArray($this->thinBorder());

        $sheet->mergeCells([$formulaCol, $row, $totalCols, $row + 5]);
        $sheet->getCell([$formulaCol, $row])->setValue('');
        $sheet->getStyle([$formulaCol, $row, $totalCols, $row + 5])->applyFromArray($this->lightBlueFill());
        $sheet->getStyle([$formulaCol, $row, $totalCols, $row + 5])->applyFromArray($this->thinBorder());

        $jobStartRow = 10;
        $jobFields = [
            'INSPECTION DATE' => $this->record->inspection?->inspection_date?->format('d/m/y') ?? '',
            'INTERNAL PO#' => $this->record->style?->style_number ?? '',
            'DYEING' => '',
            'COMPACTING' => '',
            'BUYER' => $this->record->buyer?->buyer_name ?? '',
            'FABRIC TYPE' => $this->record->fabric_type ?? '',
            'COLOR' => $this->record->color ?? '',
            'DC#' => '',
            'LOT#' => $this->record->lot_no ?? '',
            'DIA/WIDTH (REQ)' => $this->record->inspection?->width_target ?? '',
            'DIA/WIDTH (ACT AVG)' => $this->record->inspection?->width_actual ?? '',
            'GSM (REQ)' => $this->record->inspection?->gsm_target ?? '',
            'GSM (ACT AVG)' => $this->record->inspection?->gsm_actual ?? '',
            'GSM RESULT' => $this->gsmResult(),
            'INSPECTOR NAME' => $this->record->inspection?->inspector?->name ?? '',
        ];

        $jobCol1 = 1;
        $jobCol1End = (int) ceil($totalCols * 0.35);
        $jobCol2Start = $jobCol1End + 1;
        $jobCol2End = (int) ceil($totalCols * 0.7);
        $summaryStart = $jobCol2End + 1;

        $halfJob = (int) ceil(count($jobFields) / 2);
        $jobFieldsLeft = array_slice($jobFields, 0, $halfJob, true);
        $jobFieldsRight = array_slice($jobFields, $halfJob, null, true);

        $r = $jobStartRow;
        foreach ($jobFieldsLeft as $label => $value) {
            $sheet->mergeCells([$jobCol1, $r, $jobCol1, $r]);
            $sheet->getCell([$jobCol1, $r])->setValue($label)->getStyle()->getFont()->setBold(true)->setSize(8);
            $sheet->getStyle([$jobCol1, $r])->applyFromArray($this->thinBorder());
            $sheet->mergeCells([$jobCol1 + 1, $r, $jobCol1End, $r]);
            $sheet->getCell([$jobCol1 + 1, $r])->setValue($value)->getStyle()->getFont()->setSize(8);
            $sheet->getStyle([$jobCol1 + 1, $r, $jobCol1End, $r])->applyFromArray($this->thinBorder());
            $r++;
        }

        $r = $jobStartRow;
        foreach ($jobFieldsRight as $label => $value) {
            $sheet->getCell([$jobCol2Start, $r])->setValue($label)->getStyle()->getFont()->setBold(true)->setSize(8);
            $sheet->getStyle([$jobCol2Start, $r])->applyFromArray($this->thinBorder());
            $sheet->mergeCells([$jobCol2Start + 1, $r, $jobCol2End, $r]);
            $sheet->getCell([$jobCol2Start + 1, $r])->setValue($value)->getStyle()->getFont()->setSize(8);
            $sheet->getStyle([$jobCol2Start + 1, $r, $jobCol2End, $r])->applyFromArray($this->thinBorder());
            $r++;
        }

        $summaryFields = [
            'TOTAL WEIGHT (KGS.)' => number_format((float) $rolls->sum('weight_kgs'), 3),
            'PASSED QTY (KGS.)' => number_format((float) $rolls->where('result', 'pass')->sum('weight_kgs'), 3),
            'INSPECTED ROLL WT. (KGS.)' => number_format((float) $rolls->sum('weight_kgs'), 3),
            'FAILED QTY (KGS.)' => number_format((float) $rolls->where('result', 'fail')->sum('weight_kgs'), 3),
            'HAND FEEL / COLOR' => $this->record->inspection?->shade_status ?? 'pending',
        ];

        $r = $jobStartRow;
        foreach ($summaryFields as $label => $value) {
            $sheet->getCell([$summaryStart, $r])->setValue($label)->getStyle()->getFont()->setBold(true)->setSize(8);
            $sheet->getStyle([$summaryStart, $r])->applyFromArray($this->thinBorder());
            $sheet->mergeCells([$summaryStart + 1, $r, $totalCols, $r]);
            $sheet->getCell([$summaryStart + 1, $r])->setValue($value)->getStyle()->getFont()->setSize(8);
            $sheet->getStyle([$summaryStart + 1, $r, $totalCols, $r])->applyFromArray($this->thinBorder());
            $r++;
        }

        $totalPoints = $rolls->sum(fn($rl) => $rl->defects()->sum('points'));
        $totalYards = $rolls->sum(fn($rl) => (float) $rl->roll_length_yards);
        $avgWidth = $rolls->count() > 0 ? $rolls->avg(fn($rl) => $rl->avgWidth()) : 0;
        $overallPoints = ($totalYards > 0 && $avgWidth > 0) ? round(($totalPoints * 3600) / ($totalYards * $avgWidth), 1) : 0;
        $overallResult = $overallPoints > 18 ? 'FAIL' : 'PASS';

        $sheet->getCell([$summaryStart, $r])->setValue('OVER ALL POINTS')->getStyle()->getFont()->setBold(true)->setSize(8);
        $sheet->getStyle([$summaryStart, $r])->applyFromArray($this->thinBorder());
        $sheet->mergeCells([$summaryStart + 1, $r, $totalCols, $r]);
        $sheet->getCell([$summaryStart + 1, $r])->setValue($overallPoints . ' pts/100 sq yd')->getStyle()->getFont()->setBold(true)->setSize(8);
        $sheet->getStyle([$summaryStart + 1, $r, $totalCols, $r])->applyFromArray($this->thinBorder());
        $r++;

        $sheet->getCell([$summaryStart, $r])->setValue('OVER ALL RESULT')->getStyle()->getFont()->setBold(true)->setSize(8);
        $sheet->getStyle([$summaryStart, $r])->applyFromArray($this->thinBorder());
        $sheet->mergeCells([$summaryStart + 1, $r, $totalCols, $r]);
        $sheet->getCell([$summaryStart + 1, $r])->setValue($overallResult)->getStyle()->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle([$summaryStart + 1, $r, $totalCols, $r])->applyFromArray($this->thinBorder());
        $sheet->getStyle([$summaryStart + 1, $r, $totalCols, $r])->applyFromArray($overallResult === 'PASS' ? $this->greenFill() : $this->redFill());
        if ($overallResult === 'FAIL') {
            $sheet->getCell([$summaryStart + 1, $r])->getStyle()->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        }

        $tableStartRow = $r + 2;

        $col = 1;
        $sheet->mergeCells([$col, $tableStartRow, $col, $tableStartRow + 1]);
        $sheet->getCell([$col, $tableStartRow])->setValue('DEFECTS SUMMARY')->getStyle()->getFont()->setBold(true)->setSize(7);
        $sheet->getStyle([$col, $tableStartRow, $col, $tableStartRow + 1])->applyFromArray($this->thinBorder());
        $col++;

        $defectStartCol = $col;
        $defectEndCol = $col + count(self::DEFECT_COLUMNS) - 1;
        $sheet->mergeCells([$defectStartCol, $tableStartRow, $defectEndCol, $tableStartRow]);
        $sheet->getCell([$defectStartCol, $tableStartRow])->setValue('DEFECTS SUMMARY')->getStyle()->getFont()->setBold(true)->setSize(7);
        $sheet->getStyle([$defectStartCol, $tableStartRow, $defectEndCol, $tableStartRow])->applyFromArray($this->thinBorder());

        foreach (self::DEFECT_COLUMNS as $i => $dc) {
            $c = $defectStartCol + $i;
            $sheet->getCell([$c, $tableStartRow + 1])->setValue($dc);
            $sheet->getStyle([$c, $tableStartRow + 1])->getFont()->setBold(true)->setSize(6);
            $sheet->getStyle([$c, $tableStartRow + 1])->getAlignment()->setTextRotation(90)->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle([$c, $tableStartRow + 1])->applyFromArray($this->thinBorder());
        }
        $col = $defectEndCol + 1;

        $rollDetailCols = ['Roll weight (Kgs.)', 'Roll Width F', 'Roll Width M', 'Roll Width E', 'GSM', 'Roll Length (Yards)'];
        $rollDetailStart = $col;
        $rollDetailEnd = $col + count($rollDetailCols) - 1;
        $sheet->mergeCells([$rollDetailStart, $tableStartRow, $rollDetailEnd, $tableStartRow]);
        $sheet->getCell([$rollDetailStart, $tableStartRow])->setValue('ROLL DETAILS')->getStyle()->getFont()->setBold(true)->setSize(7);
        $sheet->getStyle([$rollDetailStart, $tableStartRow, $rollDetailEnd, $tableStartRow])->applyFromArray($this->thinBorder());

        foreach ($rollDetailCols as $i => $rc) {
            $c = $rollDetailStart + $i;
            $sheet->getCell([$c, $tableStartRow + 1])->setValue($rc)->getStyle()->getFont()->setBold(true)->setSize(6);
            $sheet->getStyle([$c, $tableStartRow + 1])->getAlignment()->setTextRotation(90)->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle([$c, $tableStartRow + 1])->applyFromArray($this->thinBorder());
        }
        $col = $rollDetailEnd + 1;

        $penaltyCols = ['Points/Roll', 'Points/100 Sq. Yd'];
        $penaltyStart = $col;
        $penaltyEnd = $col + count($penaltyCols) - 1;
        $sheet->mergeCells([$penaltyStart, $tableStartRow, $penaltyEnd, $tableStartRow]);
        $sheet->getCell([$penaltyStart, $tableStartRow])->setValue('PENALTY POINTS')->getStyle()->getFont()->setBold(true)->setSize(7);
        $sheet->getStyle([$penaltyStart, $tableStartRow, $penaltyEnd, $tableStartRow])->applyFromArray($this->thinBorder());

        foreach ($penaltyCols as $i => $pc) {
            $c = $penaltyStart + $i;
            $sheet->getCell([$c, $tableStartRow + 1])->setValue($pc)->getStyle()->getFont()->setBold(true)->setSize(6);
            $sheet->getStyle([$c, $tableStartRow + 1])->getAlignment()->setTextRotation(90)->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle([$c, $tableStartRow + 1])->applyFromArray($this->thinBorder());
        }
        $col = $penaltyEnd + 1;

        $resultCols = ['RESULT', 'REMARKS'];
        $resultStart = $col;
        $resultEnd = $col + count($resultCols) - 1;
        $sheet->mergeCells([$resultStart, $tableStartRow, $resultEnd, $tableStartRow]);
        $sheet->getCell([$resultStart, $tableStartRow])->setValue('INDIVIDUAL ROLL RESULT')->getStyle()->getFont()->setBold(true)->setSize(7);
        $sheet->getStyle([$resultStart, $tableStartRow, $resultEnd, $tableStartRow])->applyFromArray($this->thinBorder());

        foreach ($resultCols as $i => $rc) {
            $c = $resultStart + $i;
            $sheet->getCell([$c, $tableStartRow + 1])->setValue($rc)->getStyle()->getFont()->setBold(true)->setSize(6);
            $sheet->getStyle([$c, $tableStartRow + 1])->getAlignment()->setTextRotation(90)->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle([$c, $tableStartRow + 1])->applyFromArray($this->thinBorder());
        }

        $dataStartRow = $tableStartRow + 2;
        $r = $dataStartRow;
        foreach ($rolls as $roll) {
            $sheet->getCell([1, $r])->setValue($roll->roll_no)->getStyle()->getFont()->setBold(true)->setSize(7);
            $sheet->getStyle([1, $r])->applyFromArray($this->thinBorder());

            $defectPointsByType = [];
            foreach ($roll->defects as $d) {
                $type = $d->defect_type;
                if (!isset($defectPointsByType[$type])) {
                    $defectPointsByType[$type] = 0;
                }
                $defectPointsByType[$type] += (int) $d->points;
            }

            $c = $defectStartCol;
            foreach (self::DEFECT_COLUMNS as $dc) {
                $sheet->getCell([$c, $r])->setValue($defectPointsByType[$dc] ?? '')->getStyle()->getFont()->setSize(7);
                $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder());
                $c++;
            }

            $sheet->getCell([$c, $r])->setValue((float) $roll->weight_kgs)->getStyle()->getFont()->setSize(7);
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder()); $c++;
            $sheet->getCell([$c, $r])->setValue($roll->width_front ? (float) $roll->width_front : '')->getStyle()->getFont()->setSize(7);
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder()); $c++;
            $sheet->getCell([$c, $r])->setValue($roll->width_middle ? (float) $roll->width_middle : '')->getStyle()->getFont()->setSize(7);
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder()); $c++;
            $sheet->getCell([$c, $r])->setValue($roll->width_end ? (float) $roll->width_end : '')->getStyle()->getFont()->setSize(7);
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder()); $c++;
            $sheet->getCell([$c, $r])->setValue($roll->gsm ? (float) $roll->gsm : '')->getStyle()->getFont()->setSize(7);
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder()); $c++;
            $sheet->getCell([$c, $r])->setValue($roll->roll_length_yards ? (float) $roll->roll_length_yards : '')->getStyle()->getFont()->setSize(7);
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder()); $c++;

            $rollTotalPoints = $roll->defects()->sum('points');
            $sheet->getCell([$c, $r])->setValue((int) $rollTotalPoints)->getStyle()->getFont()->setSize(7);
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder()); $c++;
            $sheet->getCell([$c, $r])->setValue($roll->points_per_100_sq_yd ? (float) $roll->points_per_100_sq_yd : '')->getStyle()->getFont()->setSize(7);
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder()); $c++;

            $sheet->getCell([$c, $r])->setValue(strtoupper($roll->result))->getStyle()->getFont()->setBold(true)->setSize(7);
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder());
            $sheet->getStyle([$c, $r])->applyFromArray($roll->result === 'pass' ? $this->greenFill() : $this->redFill());
            if ($roll->result === 'fail') {
                $sheet->getCell([$c, $r])->getStyle()->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            }
            $sheet->getStyle([$c, $r])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $c++;

            $sheet->getCell([$c, $r])->setValue($roll->remarks ?? '/')->getStyle()->getFont()->setSize(7);
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder());

            $r++;
        }

        $sheet->getCell([1, $r])->setValue('TOTAL')->getStyle()->getFont()->setBold(true)->setSize(7);
        $sheet->getStyle([1, $r])->applyFromArray($this->thinBorder());

        for ($c = $defectStartCol; $c <= $defectEndCol; $c++) {
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder());
        }

        $weightCol = $rollDetailStart;
        $yardsCol = $rollDetailStart + 5;
        $ptsCol = $penaltyStart;

        $sheet->getCell([$weightCol, $r])->setValue(number_format((float) $rolls->sum('weight_kgs'), 3))->getStyle()->getFont()->setBold(true)->setSize(7);
        $sheet->getStyle([$weightCol, $r])->applyFromArray($this->thinBorder());
        for ($c = $weightCol + 1; $c < $yardsCol; $c++) {
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder());
        }
        $sheet->getCell([$yardsCol, $r])->setValue(number_format((float) $totalYards, 1))->getStyle()->getFont()->setBold(true)->setSize(7);
        $sheet->getStyle([$yardsCol, $r])->applyFromArray($this->thinBorder());

        $sheet->getCell([$ptsCol, $r])->setValue((int) $totalPoints)->getStyle()->getFont()->setBold(true)->setSize(7);
        $sheet->getStyle([$ptsCol, $r])->applyFromArray($this->thinBorder());

        for ($c = $ptsCol + 1; $c <= $resultEnd; $c++) {
            $sheet->getStyle([$c, $r])->applyFromArray($this->thinBorder());
        }

        $footerRow = $r + 2;
        $sheet->mergeCells([1, $footerRow, $totalCols, $footerRow]);
        $sheet->getCell([1, $footerRow])->setValue('COMMENTS (If any): ' . ($this->record->inspection?->shade_status === 'rejected' ? 'Shade rejected' : ''));
        $sheet->getStyle([1, $footerRow])->applyFromArray($this->thinBorder());

        $sigRow = $footerRow + 1;
        $midCol = (int) ceil($totalCols / 2);
        $sheet->mergeCells([1, $sigRow, $midCol - 1, $sigRow + 1]);
        $sheet->getCell([1, $sigRow])->setValue("FABRIC INSPECTOR(S)\n\n_________________________")->getStyle()->getFont()->setBold(true)->setSize(8);
        $sheet->getStyle([1, $sigRow])->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle([1, $sigRow, $midCol - 1, $sigRow + 1])->applyFromArray($this->thinBorder());

        $sheet->mergeCells([$midCol, $sigRow, $totalCols, $sigRow + 1]);
        $sheet->getCell([$midCol, $sigRow])->setValue("FACTORY REPRESENTATIVE\n\n_________________________")->getStyle()->getFont()->setBold(true)->setSize(8);
        $sheet->getStyle([$midCol, $sigRow])->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle([$midCol, $sigRow, $totalCols, $sigRow + 1])->applyFromArray($this->thinBorder());

        $sheet->freezePane([1, $dataStartRow]);
    }

    protected function buildSheet2($sheet): void
    {
        $sheet->setTitle('Roll Data Sheet');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        $company = config('app.name', 'INR Global Sourcing');
        $rolls = $this->record->rolls->sortBy('roll_no')->values();

        $totalCols = 15;
        $sheet->mergeCells([1, 1, $totalCols, 1]);
        $sheet->getCell([1, 1])->setValue($company . ' - Fabric Inspection Roll Data Sheet')
            ->getStyle()->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle([1, 1])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $colWidth = 5;
        $currentRow = 3;
        $currentCol = 1;
        $rollsInCol = (int) ceil($rolls->count() / 3);
        $colHeights = [0, 0, 0];

        $rollIndex = 0;
        for ($colIdx = 0; $colIdx < 3 && $rollIndex < $rolls->count(); $colIdx++) {
            $currentCol = 1 + ($colIdx * $colWidth);
            $currentRow = 3;

            while ($rollIndex < $rolls->count() && $colHeights[$colIdx] <= $colHeights[0] && $colHeights[$colIdx] <= $colHeights[1] && $colHeights[$colIdx] <= $colHeights[2] || $colIdx === array_search(min($colHeights), $colHeights)) {
                if ($rollIndex >= $rolls->count()) break;
                $roll = $rolls[$rollIndex];
                $defects = $roll->defects->sortBy('metre_position')->values();

                $sheet->mergeCells([$currentCol, $currentRow, $currentCol + $colWidth - 1, $currentRow]);
                $sheet->getCell([$currentCol, $currentRow])->setValue('Roll#' . $roll->roll_no)
                    ->getStyle()->getFont()->setBold(true)->setSize(9);
                $sheet->getStyle([$currentCol, $currentRow])->applyFromArray($this->thinBorder());
                $sheet->getStyle([$currentCol, $currentRow])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $currentRow++;

                $sheet->getCell([$currentCol, $currentRow])->setValue('Mtr')->getStyle()->getFont()->setBold(true)->setSize(8);
                $sheet->getStyle([$currentCol, $currentRow])->applyFromArray($this->thinBorder());
                $sheet->getCell([$currentCol + 1, $currentRow])->setValue('Defect')->getStyle()->getFont()->setBold(true)->setSize(8);
                $sheet->getStyle([$currentCol + 1, $currentRow])->applyFromArray($this->thinBorder());
                $sheet->mergeCells([$currentCol + 2, $currentRow, $currentCol + $colWidth - 1, $currentRow]);
                $sheet->getCell([$currentCol + 2, $currentRow])->setValue('Points')->getStyle()->getFont()->setBold(true)->setSize(8);
                $sheet->getStyle([$currentCol + 2, $currentRow, $currentCol + $colWidth - 1, $currentRow])->applyFromArray($this->thinBorder());
                $sheet->getStyle([$currentCol + 2, $currentRow])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $currentRow++;

                foreach ($defects as $d) {
                    $sheet->getCell([$currentCol, $currentRow])->setValue($d->metre_position ?? '')->getStyle()->getFont()->setSize(8);
                    $sheet->getStyle([$currentCol, $currentRow])->applyFromArray($this->thinBorder());
                    $sheet->getCell([$currentCol + 1, $currentRow])->setValue($d->defect_type)->getStyle()->getFont()->setSize(8);
                    $sheet->getStyle([$currentCol + 1, $currentRow])->applyFromArray($this->thinBorder());
                    $sheet->mergeCells([$currentCol + 2, $currentRow, $currentCol + $colWidth - 1, $currentRow]);
                    $sheet->getCell([$currentCol + 2, $currentRow])->setValue($d->points ?? '')->getStyle()->getFont()->setSize(8);
                    $sheet->getStyle([$currentCol + 2, $currentRow, $currentCol + $colWidth - 1, $currentRow])->applyFromArray($this->thinBorder());
                    $sheet->getStyle([$currentCol + 2, $currentRow])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $currentRow++;
                }

                $rollTotalPoints = $defects->sum('points');
                $sheet->mergeCells([$currentCol, $currentRow, $currentCol + 1, $currentRow]);
                $sheet->getCell([$currentCol, $currentRow])->setValue('Total penalty points')->getStyle()->getFont()->setBold(true)->setSize(8);
                $sheet->getStyle([$currentCol, $currentRow, $currentCol + 1, $currentRow])->applyFromArray($this->thinBorder());
                $sheet->mergeCells([$currentCol + 2, $currentRow, $currentCol + $colWidth - 1, $currentRow]);
                $sheet->getCell([$currentCol + 2, $currentRow])->setValue($rollTotalPoints)->getStyle()->getFont()->setBold(true)->setSize(8);
                $sheet->getStyle([$currentCol + 2, $currentRow, $currentCol + $colWidth - 1, $currentRow])->applyFromArray($this->thinBorder());
                $sheet->getStyle([$currentCol + 2, $currentRow])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $currentRow++;

                $currentRow++;
                $colHeights[$colIdx] = $currentRow;
                $rollIndex++;
            }
        }

        if ($rolls->isEmpty()) {
            $sheet->mergeCells([1, 3, $totalCols, 3]);
            $sheet->getCell([1, 3])->setValue('No roll data available. Please add rolls in the inspection page.');
        }
    }

    protected function gsmResult(): string
    {
        $target = (float) ($this->record->inspection?->gsm_target ?? 0);
        $actual = (float) ($this->record->inspection?->gsm_actual ?? 0);
        if ($target <= 0 || $actual <= 0) return '';
        $deviation = abs(($actual - $target) / $target) * 100;
        return $deviation <= 5 ? 'PASS' : 'FAIL';
    }
}
