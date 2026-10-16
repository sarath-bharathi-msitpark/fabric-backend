<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bundle Card {{ $bundle->bundle_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .card { border: 2px solid #111; padding: 16px; width: 480px; margin: 0 auto; }
        .row { display: flex; justify-content: space-between; margin: 4px 0; }
        .label { color: #555; }
        h1 { font-size: 16px; margin: 0 0 8px; text-align: center; }
    </style>
</head>
<body onload="window.print()">
    <div class="card">
        <h1>Bundle Card</h1>
        <div class="row"><span class="label">Audit ID</span><strong>{{ $bundle->audit->audit_no }}</strong></div>
        <div class="row"><span class="label">Bundle No</span><strong>{{ $bundle->bundle_no }}</strong></div>
        <div class="row"><span class="label">Size</span><strong>{{ $bundle->size ?? '—' }}</strong></div>
        <div class="row"><span class="label">Panel Numbers</span><strong>{{ $bundle->panel_numbers ?? '—' }}</strong></div>
        <div class="row"><span class="label">Pieces</span><strong>{{ $bundle->pieces }}</strong></div>
        <div class="row"><span class="label">Table</span><strong>{{ $bundle->audit->cuttingTable?->table_no ?? '—' }}</strong></div>
        <div class="row"><span class="label">Buyer</span><strong>{{ $bundle->audit->buyer?->buyer_name ?? '—' }}</strong></div>
        <div class="row"><span class="label">Style# / PO#</span><strong>{{ $bundle->audit->style_number ?? '—' }} / {{ $bundle->audit->po_number ?? '—' }}</strong></div>
        <div class="row"><span class="label">Colour</span><strong>{{ $bundle->audit->colour ?? '—' }}</strong></div>
        <div class="row" style="margin-top:12px;"><span class="label">Checked by</span><span>___________________</span></div>
    </div>
</body>
</html>
