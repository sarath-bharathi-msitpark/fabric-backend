<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SKW-QMS-17 {{ $audit->audit_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        h1 { font-size: 13px; margin: 0; }
        .sub { font-size: 10px; font-weight: bold; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #888; padding: 3px 5px; vertical-align: top; }
        th { background: #e5e7eb; text-align: left; font-size: 8px; }
        .grid { width: 100%; }
        .grid td { border: 1px solid #888; }
        .label { color: #555; font-size: 7px; text-transform: uppercase; display: block; }
        .ok { color: #15803d; font-weight: bold; }
        .notok { color: #b91c1c; font-weight: bold; }
        .section-title { background: #1e293b; color: #fff; padding: 3px 6px; font-size: 9px; font-weight: bold; }
        .sign { margin-top: 6px; }
        .sign td { height: 34px; }
    </style>
</head>
<body>
    <table style="border:none;">
        <tr>
            <td style="border:none; width:60%;">
                <h1>{{ $company }}</h1>
                <div class="sub">SKW-QMS-17 · Cutting Quality Audit Report</div>
            </td>
            <td style="border:none; text-align:right;">
                <div><strong>Audit ID:</strong> {{ $audit->audit_no }}</div>
                <div><strong>Revision:</strong> {{ $audit->revision }}</div>
                <div><strong>Date:</strong> {{ $audit->audit_date?->format('d/m/Y') }}</div>
                <div><strong>Shift:</strong> {{ $audit->shift ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">ORDER HEADER</div>
    <table class="grid">
        <tr>
            <td><span class="label">Table No.</span>{{ $audit->cuttingTable?->table_no ?? '—' }}</td>
            <td><span class="label">Buyer</span>{{ $audit->buyer?->buyer_name ?? '—' }}</td>
            <td><span class="label">Style#</span>{{ $audit->style_number ?? '—' }}</td>
            <td><span class="label">Style Name</span>{{ $audit->style_name ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="label">PO#</span>{{ $audit->po_number ?? '—' }}</td>
            <td><span class="label">LOT#</span>{{ $audit->lot_number ?? '—' }}</td>
            <td><span class="label">Fabric</span>{{ $audit->fabric ?? '—' }}</td>
            <td><span class="label">Colour</span>{{ $audit->colour ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="label">DIA</span>{{ $audit->dia ?? '—' }}</td>
            <td><span class="label">Lay#</span>{{ $audit->lay_number ?? '—' }}</td>
            <td><span class="label">Cut#</span>{{ $audit->cut_number ?? '—' }}</td>
            <td><span class="label">Sizes</span>{{ $audit->number_of_sizes }}</td>
        </tr>
    </table>

    <div class="section-title">LAY DETAILS</div>
    <table class="grid">
        <tr>
            <td><span class="label">Lay Method</span>{{ ucfirst($audit->lay_method) }}</td>
            <td><span class="label">Type of Marking</span>{{ $audit->marking_type ?? '—' }}</td>
            <td><span class="label">Req L×W (m)</span>{{ $audit->required_lay_length ?? '—' }} × {{ $audit->required_lay_width ?? '—' }}</td>
            <td><span class="label">Act L×W (m)</span>{{ $audit->actual_lay_length ?? '—' }} × {{ $audit->actual_lay_width ?? '—' }}</td>
        </tr>
        <tr>
            <td><span class="label">Plies (req/act)</span>{{ $audit->required_plies ?? '—' }} / {{ $audit->actual_plies ?? '—' }}</td>
            <td><span class="label">Lay Height (cm)</span>{{ $audit->actual_lay_height ?? '—' }}</td>
            <td><span class="label">Total Parts</span>{{ $audit->total_parts ?? '—' }}</td>
            <td><span class="label">Tolerance %</span>{{ $audit->tolerance_pct }}</td>
        </tr>
    </table>

    @foreach(\App\Models\CuttingAudit::STAGES as $stage)
        @php $sm = $audit->stages->firstWhere('stage', $stage); @endphp
        <div class="section-title">{{ strtoupper(\App\Models\CuttingAudit::STAGE_LABELS[$stage]) }} — {{ $sm?->status ? strtoupper(str_replace('_',' ', $sm->status)) : 'NOT STARTED' }}</div>
        @if($sm)
            <table>
                <thead><tr><th style="width:60%;">Checkpoint</th><th style="width:15%;">Result</th><th>Remarks</th></tr></thead>
                <tbody>
                    @foreach($sm->results as $result)
                    <tr>
                        <td>{{ $result->checkpoint_name }}</td>
                        <td class="{{ $result->result === 'ok' ? 'ok' : ($result->result === 'not_ok' ? 'notok' : '') }}">
                            {{ $result->result === 'ok' ? 'OK' : ($result->result === 'not_ok' ? 'NOT OK' : '—') }}
                        </td>
                        <td>{{ $result->remarks ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <table class="grid">
                <tr>
                    <td><span class="label">Overall Result</span>{{ $sm->resultLabel() }}</td>
                    <td><span class="label">Inspection Date</span>{{ $sm->inspection_date?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td><span class="label">Comments</span>{{ $sm->comments ?? '—' }}</td>
                </tr>
            </table>
        @else
            <p class="muted">Stage not started.</p>
        @endif
    @endforeach

    @if($audit->sizePatterns->isNotEmpty())
    <div class="section-title">SIZE-WISE PATTERN MATCHING (TOP / MIDDLE / BOTTOM)</div>
    <table>
        <thead><tr><th>Size</th><th>Top</th><th>Middle</th><th>Bottom</th><th>Remarks</th></tr></thead>
        <tbody>
            @foreach($audit->sizePatterns as $pattern)
            <tr>
                <td>{{ $pattern->size_name }}</td>
                <td>{{ $pattern->top_result === null ? '—' : ($pattern->top_result ? 'Match' : 'No match') }}</td>
                <td>{{ $pattern->middle_result === null ? '—' : ($pattern->middle_result ? 'Match' : 'No match') }}</td>
                <td>{{ $pattern->bottom_result === null ? '—' : ($pattern->bottom_result ? 'Match' : 'No match') }}</td>
                <td>{{ $pattern->remarks ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($audit->bundles->isNotEmpty())
    <div class="section-title">BUNDLES</div>
    <table>
        <thead><tr><th>Bundle No</th><th>Size</th><th>Panel Numbers</th><th>Pieces</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($audit->bundles as $bundle)
            <tr><td>{{ $bundle->bundle_no }}</td><td>{{ $bundle->size ?? '—' }}</td><td>{{ $bundle->panel_numbers ?? '—' }}</td><td>{{ $bundle->pieces }}</td><td>{{ ucfirst($bundle->status) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="section-title">DIGITAL SIGN-OFFS</div>
    <table>
        <thead><tr><th>Stage</th><th>Role</th><th>Name</th><th>Result</th><th>Signed At</th><th>Checklist v.</th></tr></thead>
        <tbody>
            @forelse($audit->signoffs as $signoff)
            <tr>
                <td>{{ \App\Models\CuttingAudit::STAGE_LABELS[$signoff->stage] ?? $signoff->stage }}</td>
                <td>{{ ucwords(str_replace('_',' ', $signoff->role)) }}</td>
                <td>{{ $signoff->user_name }}</td>
                <td>{{ $signoff->result ? ucfirst(str_replace('_',' ', $signoff->result)) : '—' }}</td>
                <td>{{ $signoff->signed_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $signoff->checklist_version ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6">No sign-offs recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($audit->specialApprovals->isNotEmpty())
    <div class="section-title">SPECIAL APPROVALS</div>
    <table>
        <thead><tr><th>Stage</th><th>Reason</th><th>Requested By</th><th>Status</th><th>Approved By</th><th>Approved At</th></tr></thead>
        <tbody>
            @foreach($audit->specialApprovals as $approval)
            <tr>
                <td>{{ \App\Models\CuttingAudit::STAGE_LABELS[$approval->stage] ?? $approval->stage }}</td>
                <td>{{ $approval->reason }}</td>
                <td>{{ $approval->requester?->name ?? '—' }}</td>
                <td>{{ ucfirst($approval->status) }}</td>
                <td>{{ $approval->approver?->name ?? '—' }}</td>
                <td>{{ $approval->approved_at?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <table class="sign">
        <tr>
            <td style="width:33%;"><span class="label">Cutting QC</span><br><br>______________________</td>
            <td style="width:33%;"><span class="label">Cutting Incharge</span><br><br>______________________</td>
            <td><span class="label">QA Manager</span><br><br>______________________</td>
        </tr>
    </table>
    <p class="muted">Generated {{ now()->format('d/m/Y H:i') }} · Digital sign-offs are recorded with signer identity, role and timestamp. This is not a cryptographic signature.</p>
</body>
</html>
