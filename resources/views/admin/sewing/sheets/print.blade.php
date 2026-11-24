<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $sheet->sheet_no }} — Sewing Inline Inspection Report</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 16px; }
    h1 { font-size: 15px; margin: 0; }
    .muted { color: #666; }
    .header { border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 10px; display: flex; justify-content: space-between; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    th, td { border: 1px solid #999; padding: 4px 5px; text-align: left; vertical-align: top; }
    th { background: #eee; font-size: 10px; text-transform: uppercase; letter-spacing: .03em; }
    .c { text-align: center; }
    .meta td { border: none; padding: 2px 6px 2px 0; }
    .green { background: #d1fae5; }
    .amber { background: #fef3c7; }
    .red { background: #fee2e2; }
    .sig td { height: 34px; }
    .section { font-weight: bold; margin: 0 0 4px; }
    .no-print { margin-bottom: 12px; }
    @media print { .no-print { display: none; } body { padding: 0; } }
</style>
</head>
<body>
@php
    $band = fn ($r) => $r === 'green' ? 'green' : ($r === 'amber' ? 'amber' : ($r === 'red' ? 'red' : ''));
    $letter = fn ($r) => $r === 'green' ? 'G' : ($r === 'amber' ? 'A' : ($r === 'red' ? 'R' : '-'));
@endphp

@unless($pdf ?? false)
<div class="no-print">
    <button onclick="window.print()">Print</button>
</div>
@endunless

<div class="header">
    <div>
        <h1>Sewing Inline Inspection Report</h1>
        <div class="muted">7-piece audit &middot; Doc No. UKM-QMS-22A Rev.00</div>
    </div>
    <div class="c">
        <div class="muted">Sheet No.</div>
        <div style="font-size:14px; font-weight:bold;">{{ $sheet->sheet_no }}</div>
    </div>
</div>

<table class="meta">
    <tr>
        <td><strong>Buyer:</strong> {{ $sheet->buyer->buyer_name ?? '—' }}</td>
        <td><strong>PO#:</strong> {{ $sheet->po_number ?? '—' }}</td>
        <td><strong>Style#:</strong> {{ $sheet->style_number ?? '—' }}</td>
        <td><strong>Colour:</strong> {{ $sheet->color ?? '—' }}</td>
    </tr>
    <tr>
        <td><strong>Line:</strong> {{ $sheet->line->line_no ?? '—' }}</td>
        <td><strong>LOT#:</strong> {{ $sheet->lot_number ?? '—' }}</td>
        <td><strong>Bundle#:</strong> {{ $sheet->bundle_no ?? '—' }}</td>
        <td><strong>Shift:</strong> {{ $sheet->shift ?? '—' }}</td>
    </tr>
    <tr>
        <td><strong>Inspection date:</strong> {{ optional($sheet->inspection_date)->format('d M Y') }}</td>
        <td><strong>QC:</strong> {{ $sheet->qc->name ?? '—' }}</td>
        <td><strong>Pieces / audit:</strong> {{ $sheet->pieces_per_audit }}</td>
        <td><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $sheet->status)) }}</td>
    </tr>
</table>

<div class="section">Results by round (0 = Green, 1-2 = Amber, 3+ = Red)</div>
<table>
    <thead>
        <tr>
            <th style="width:26px;">#</th>
            <th>Operation</th>
            <th>M/C</th>
            <th>Operator</th>
            @foreach($rounds as $round)<th class="c">R{{ $round->round_no }}</th>@endforeach
            <th class="c">Re-audit</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sheet->operations as $op)
            @php $opResults = $byOp[$op->id] ?? collect(); @endphp
            <tr>
                <td class="c">{{ $op->sequence_no }}</td>
                <td>{{ $op->operation_name }}</td>
                <td>{{ $op->machine_no ?? '—' }}</td>
                <td>{{ $op->operator->name ?? '—' }}</td>
                @foreach($rounds as $round)
                    @php $res = $opResults->get($round->round_no); @endphp
                    <td class="c {{ $res ? $band($res->result) : '' }}">{{ $res ? $res->defect_count.' '.$letter($res->result) : '—' }}</td>
                @endforeach
                @php $re = $opResults->first(fn ($r) => $r->re_audit_result); @endphp
                <td class="c">{{ $re ? ucfirst($re->re_audit_result).' ('.$re->re_audit_count.')' : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ $rounds->count() + 5 }}" class="c">No operations.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section">Defect log ({{ $defects->count() }})</div>
<table>
    <thead>
        <tr>
            <th>Time</th>
            <th class="c">Round</th>
            <th>Operation</th>
            <th>M/C</th>
            <th>Operator</th>
            <th>Type</th>
            <th>Defect</th>
            <th>CAPA</th>
            <th class="c">Qty</th>
            <th>Root cause</th>
            <th>Corrective action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($defects as $log)
            <tr>
                <td>{{ optional($log->created_at)->format('d M H:i') }}</td>
                <td class="c">{{ $log->is_reaudit ? 'R' : '' }}{{ $log->round_no }}</td>
                <td>{{ $log->operation->operation_name ?? '—' }}</td>
                <td>{{ $log->operation->machine_no ?? '—' }}</td>
                <td>{{ $log->operation->operator->name ?? '—' }}</td>
                <td>{{ $log->defect_type ?? '—' }}</td>
                <td>{{ $log->defect_name ?? '—' }}</td>
                <td>{{ $log->capa_code ?? '—' }}</td>
                <td class="c">{{ $log->qty }}</td>
                <td>{{ $log->root_cause ?? '—' }}</td>
                <td>{{ $log->corrective_action ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="11" class="c">No defects logged.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section">Sign-offs</div>
<table>
    <thead>
        <tr><th>Role</th><th>Name</th><th>Date / time</th><th>Comments</th></tr>
    </thead>
    <tbody class="sig">
        @foreach($signoffOrder as $role)
            @php $done = $signoffs->get($role); @endphp
            <tr>
                <td>{{ \App\Models\SewingSignoff::ROLES[$role] ?? $role }}</td>
                <td>{{ $done->user_name ?? '' }}</td>
                <td>{{ $done ? optional($done->signed_at)->format('d M Y H:i') : '' }}</td>
                <td>{{ $done->comments ?? '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
