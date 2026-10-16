<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 14px; margin: 0 0 4px; }
        .muted { color: #666; font-size: 9px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
        th { background: #e5e7eb; }
    </style>
</head>
<body>
    <h1>{{ $company }}</h1>
    <div style="font-size:12px; font-weight:bold;">{{ $title }}</div>
    <div class="muted">Generated {{ now()->format('d M Y H:i') }}</div>
    <table>
        <thead>
            <tr>@foreach($headings as $h)<th>{{ $h }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
            <tr><td colspan="{{ count($headings) }}">No data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
