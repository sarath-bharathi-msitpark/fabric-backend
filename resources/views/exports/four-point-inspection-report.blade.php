<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { size: A4 landscape; margin: 8mm; }
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 8px; color: #000; margin: 0; }
    .page { width: 100%; margin-bottom: 20px; }
    .page-break { page-break-after: always; }

    /* Title band */
    .title-band { display: flex; align-items: center; border: 1px solid #000; margin-bottom: 4px; }
    .title-band .logo { padding: 4px 8px; font-weight: bold; font-size: 11px; border-right: 1px solid #000; }
    .title-band .title { flex: 1; text-align: center; font-weight: bold; font-size: 13px; padding: 4px; }

    /* Control block */
    .control-block { background: #D6E4F0; border: 1px solid #000; padding: 4px 6px; margin-bottom: 4px; font-size: 7px; }
    .control-block strong { font-size: 8px; }

    /* Formula box */
    .formula-box { background: #D6E4F0; border: 1px solid #000; padding: 6px; margin-bottom: 6px; font-size: 7px; line-height: 1.6; }

    /* Job + Summary grid */
    .top-grid { display: flex; gap: 4px; margin-bottom: 6px; }
    .top-grid .col { flex: 1; }
    .job-table, .summary-table { width: 100%; border-collapse: collapse; }
    .job-table td, .summary-table td { border: 1px solid #000; padding: 2px 4px; font-size: 7px; }
    .job-table td:first-child, .summary-table td:first-child { font-weight: bold; white-space: nowrap; width: 45%; }

    .summary-table .result-pass { background: #22C55E; color: #fff; font-weight: bold; text-align: center; }
    .summary-table .result-fail { background: #DC2626; color: #fff; font-weight: bold; text-align: center; }

    /* Roll inspection table */
    .roll-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .roll-table th, .roll-table td { border: 1px solid #000; padding: 2px 3px; font-size: 6px; text-align: center; }
    .roll-table th { font-weight: bold; background: #f0f0f0; }
    .roll-table .group-header { background: #D6E4F0; font-size: 7px; }
    .roll-table .defect-header { writing-mode: vertical-rl; transform: rotate(180deg); height: 70px; font-size: 6px; }
    .roll-table .total-row td { font-weight: bold; background: #f5f5f5; }
    .roll-table .pass { background: #22C55E; color: #fff; font-weight: bold; }
    .roll-table .fail { background: #DC2626; color: #fff; font-weight: bold; }

    /* Footer */
    .footer { margin-top: 8px; }
    .footer .comments { border: 1px solid #000; padding: 4px; font-size: 7px; margin-bottom: 6px; min-height: 24px; }
    .signatures { display: flex; gap: 8px; }
    .signatures .sig-box { flex: 1; border: 1px solid #000; padding: 6px; font-size: 8px; font-weight: bold; min-height: 50px; }

    /* Sheet 2 - roll data */
    .roll-data-grid { display: flex; flex-wrap: wrap; gap: 8px; }
    .roll-data-table { width: 31%; border-collapse: collapse; margin-bottom: 6px; }
    .roll-data-table caption { font-weight: bold; font-size: 9px; border: 1px solid #000; border-bottom: none; padding: 3px; text-align: center; background: #f0f0f0; }
    .roll-data-table th, .roll-data-table td { border: 1px solid #000; padding: 2px 4px; font-size: 7px; text-align: center; }
    .roll-data-table th { background: #f0f0f0; }
    .roll-data-table .total-row td { font-weight: bold; }
    .roll-data-table .total-row td:first-child { text-align: left; }
</style>
</head>
<body>

{{-- SHEET 1 --}}
<div class="page">
    <div class="title-band">
        <div class="logo">{{ $company }}</div>
        <div class="title">{{ $company }} - 4 Point Fabric Inspection Report</div>
    </div>

    <div class="control-block">
        <strong>REPORT#:</strong> {{ $reportNo }} &nbsp;|&nbsp;
        <strong>Doc#:</strong> QC-FI-001 · <strong>REV#:</strong> 01 · <strong>IMP.ON:</strong> 01/07/2026 &nbsp;|&nbsp;
        <strong>Passing Criteria:</strong> Per Roll = 20 pts/100 sq yd · Overall Shipment = 18 pts/100 sq yd
    </div>

    <div class="formula-box">
        <strong>ROLL LENGTH IN YARDS</strong> = (ROLL WEIGHT KGS × 1000 × 100 × 1.0936) ÷ (GSM × FABRIC WIDTH IN INCHES × 2.54 × 0.9144)<br>
        <strong>POINTS/100 SQ. YARD</strong> = (TOTAL POINTS × 3600) ÷ (YARDS INSPECTED × WIDTH IN INCHES)
    </div>

    <div class="top-grid">
        <div class="col">
            <table class="job-table">
                @php($jobLeft = array_slice($job, 0, (int)ceil(count($job) / 2), true))
                @php($jobRight = array_slice($job, (int)ceil(count($job) / 2), null, true))
                @foreach($jobLeft as $label => $value)
                <tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>
                @endforeach
            </table>
        </div>
        <div class="col">
            <table class="job-table">
                @foreach($jobRight as $label => $value)
                <tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>
                @endforeach
            </table>
        </div>
        <div class="col">
            <table class="summary-table">
                <tr><td>Total Weight (Kgs.)</td><td>{{ $summary['total_weight'] }}</td></tr>
                <tr><td>Passed Qty (Kgs.)</td><td>{{ $summary['passed_qty'] }}</td></tr>
                <tr><td>Failed Qty (Kgs.)</td><td>{{ $summary['failed_qty'] }}</td></tr>
                <tr><td>Pass %</td><td>{{ $summary['pass_pct'] }} %</td></tr>
                <tr><td>Overall Points/100 Sq. Yd</td><td>{{ $summary['overall_points'] }}</td></tr>
                <tr><td>Overall Result</td><td class="{{ $summary['overall_result'] === 'PASS' ? 'result-pass' : 'result-fail' }}">{{ $summary['overall_result'] }}</td></tr>
            </table>
        </div>
    </div>

    <table class="roll-table">
        <thead>
            <tr class="group-header">
                <th rowspan="2">#</th>
                <th colspan="{{ count($defectColumns) }}">DEFECTS SUMMARY</th>
                <th colspan="6">ROLL DETAILS</th>
                <th colspan="2">PENALTY POINTS</th>
                <th colspan="2">RESULT</th>
            </tr>
            <tr>
                @foreach($defectColumns as $dc)
                <th class="defect-header">{{ $dc }}</th>
                @endforeach
                <th>Roll Wt (Kg)</th>
                <th>W-F</th>
                <th>W-M</th>
                <th>W-E</th>
                <th>GSM</th>
                <th>Length (Yd)</th>
                <th>Pts/Roll</th>
                <th>Pts/100 Sq.Yd</th>
                <th>Result</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rolls as $roll)
            <tr>
                <td>{{ $roll->roll_no }}</td>
                @foreach($defectColumns as $dc)
                @php($pts = $roll->defects->where('defect_type', $dc)->sum('points'))
                <td>{{ $pts > 0 ? $pts : '' }}</td>
                @endforeach
                <td>{{ number_format((float)$roll->weight_kgs, 3) }}</td>
                <td>{{ $roll->width_front ?? '' }}</td>
                <td>{{ $roll->width_middle ?? '' }}</td>
                <td>{{ $roll->width_end ?? '' }}</td>
                <td>{{ $roll->gsm ?? '' }}</td>
                <td>{{ $roll->roll_length_yards ?? '' }}</td>
                <td>{{ $roll->defects->sum('points') }}</td>
                <td>{{ $roll->points_per_100_sq_yd ?? '' }}</td>
                <td class="{{ $roll->result === 'pass' ? 'pass' : 'fail' }}">{{ strtoupper($roll->result) }}</td>
                <td>{{ $roll->remarks ?? '/' }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>TOTAL</td>
                @foreach($defectColumns as $dc)<td></td>@endforeach
                <td>{{ $summary['total_weight'] }}</td>
                <td colspan="4"></td>
                <td></td>
                <td>{{ $summary['total_yards'] }}</td>
                <td>{{ $summary['total_points'] }}</td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="comments"><strong>COMMENTS (If any):</strong> {{ $job['inspector'] ? '' : '' }}</div>
        <div class="signatures">
            <div class="sig-box">FABRIC INSPECTOR(S)<br><br>_________________________<br>{{ $job['inspector'] }}</div>
            <div class="sig-box">FACTORY REPRESENTATIVE<br><br>_________________________</div>
        </div>
    </div>
</div>

{{-- SHEET 2 --}}
<div class="page page-break">
    <div class="title-band">
        <div class="logo">{{ $company }}</div>
        <div class="title">{{ $company }} - Fabric Inspection Roll Data Sheet</div>
    </div>

    <div class="roll-data-grid">
        @foreach($rolls as $roll)
        <table class="roll-data-table">
            <caption>Roll#{{ $roll->roll_no }}</caption>
            <thead>
                <tr><th>Mtr</th><th>Defect</th><th>Points</th></tr>
            </thead>
            <tbody>
                @php($defects = $roll->defects->sortBy('metre_position'))
                @foreach($defects as $d)
                <tr><td>{{ $d->metre_position ?? '' }}</td><td style="text-align:left">{{ $d->defect_type }}</td><td>{{ $d->points ?? '' }}</td></tr>
                @endforeach
                @if($defects->isEmpty())
                <tr><td colspan="3" style="color:#999">No defects recorded</td></tr>
                @endif
                <tr class="total-row"><td colspan="2" style="text-align:left">Total penalty points</td><td>{{ $roll->defects->sum('points') }}</td></tr>
            </tbody>
        </table>
        @endforeach
    </div>

    @if($rolls->isEmpty())
    <p style="text-align:center; color:#999; margin-top:30px;">No roll data available.</p>
    @endif
</div>

</body>
</html>
