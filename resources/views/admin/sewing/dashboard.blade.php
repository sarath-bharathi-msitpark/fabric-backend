@extends('layouts.app')

@section('title', 'Sewing Dashboard')
@section('header', 'Sewing Inline Inspection — Dashboard')

@section('actions')
    <a href="{{ route('admin.sewing.sheets.create') }}" class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">Create Inspection</a>
    <a href="{{ route('admin.sewing.live-board.index') }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50 whitespace-nowrap">Live Line Board</a>
    <a href="{{ route('admin.sewing.reports.index') }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50 whitespace-nowrap">Reports</a>
@endsection

@section('content')
@php
    $band = fn ($r) => $r === 'green' ? 'green' : ($r === 'amber' ? 'yellow' : ($r === 'red' ? 'red' : 'pending'));
@endphp

<form method="GET" action="{{ route('admin.sewing.dashboard') }}" class="mb-4 flex flex-wrap items-end gap-3 print:hidden">
    <div><label class="block text-xs font-medium text-gray-600 mb-1">From</label><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-md border-gray-300 text-sm"></div>
    <div><label class="block text-xs font-medium text-gray-600 mb-1">To</label><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-md border-gray-300 text-sm"></div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Line</label>
        <select name="sewing_line_id" class="rounded-md border-gray-300 text-sm">
            <option value="">All</option>
            @foreach($lines as $line)<option value="{{ $line->id }}" @selected(($filters['sewing_line_id'] ?? '') == $line->id)>{{ $line->line_no }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Buyer</label>
        <select name="buyer_id" class="rounded-md border-gray-300 text-sm">
            <option value="">All</option>
            @foreach($buyers as $buyer)<option value="{{ $buyer->id }}" @selected(($filters['buyer_id'] ?? '') == $buyer->id)>{{ $buyer->buyer_name }}</option>@endforeach
        </select>
    </div>
    <button class="px-4 py-2 text-sm rounded-md bg-slate-700 text-white">Apply</button>
    <a href="{{ route('admin.sewing.dashboard') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300">Reset</a>
</form>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    @foreach ([
        ['Sheets today', $stats['sheets_today'], 'text-slate-700'],
        ['Open sheets', $stats['open'], 'text-amber-600'],
        ['Signed / locked', $stats['signed'], 'text-green-600'],
        ['Total sheets', $stats['total'], 'text-blue-600'],
    ] as [$label, $value, $color])
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-slate-200">
            <div class="text-xs uppercase tracking-wide text-gray-500">{{ $label }}</div>
            <div class="mt-1 text-2xl font-semibold {{ $color }}">{{ $value }}</div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach ([
        ['Green cells', $stats['green'], 'text-green-600'],
        ['Amber cells', $stats['amber'], 'text-yellow-600'],
        ['Red cells', $stats['red'], 'text-red-600'],
        ['Open re-audits', $stats['open_reaudits'], 'text-orange-600'],
        ['Escalations to PM', $stats['escalations'], 'text-rose-600'],
    ] as [$label, $value, $color])
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-500">{{ $label }}</div>
            <div class="mt-1 text-xl font-semibold {{ $color }}">{{ $value }}</div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-800 text-sm">Top Defects (DCL)</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr><th class="px-3 py-2 text-left">CAPA</th><th class="px-3 py-2 text-left">Defect</th><th class="px-3 py-2 text-left">Type</th><th class="px-3 py-2 text-center">Qty</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($topDefects as $d)
                    <tr>
                        <td class="px-3 py-2 font-mono">{{ $d->capa_code }}</td>
                        <td class="px-3 py-2">{{ $d->defect_name }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $d->defect_type }}</td>
                        <td class="px-3 py-2 text-center font-medium">{{ $d->total }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">No defects logged.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-800 text-sm">Line Performance</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr><th class="px-3 py-2 text-left">Line</th><th class="px-3 py-2 text-center">Cells</th><th class="px-3 py-2 text-center">G</th><th class="px-3 py-2 text-center">A</th><th class="px-3 py-2 text-center">R</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lineStatus as $row)
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-800">{{ $row['line'] }}</td>
                        <td class="px-3 py-2 text-center">{{ $row['cells'] }}</td>
                        <td class="px-3 py-2 text-center text-green-600">{{ $row['green'] }}</td>
                        <td class="px-3 py-2 text-center text-yellow-600">{{ $row['amber'] }}</td>
                        <td class="px-3 py-2 text-center text-red-600">{{ $row['red'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">No results captured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-800 text-sm">Pending Re-audits (Red cells)</div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr><th class="px-3 py-2 text-left">Sheet</th><th class="px-3 py-2 text-left">Operation</th><th class="px-3 py-2 text-center">Round</th><th class="px-3 py-2 text-center">Defects</th><th class="px-3 py-2 text-left">Inspected</th><th class="px-3 py-2"></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($pending as $r)
                <tr>
                    <td class="px-3 py-2 font-mono">{{ $r->sheet->sheet_no ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $r->operation->operation_name ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">R{{ $r->round_no }}</td>
                    <td class="px-3 py-2 text-center"><x-status-badge :status="$band($r->result)" :label="$r->defect_count" /></td>
                    <td class="px-3 py-2 text-gray-500">{{ optional($r->inspected_at)->format('d M Y H:i') }}</td>
                    <td class="px-3 py-2 text-right"><a href="{{ route('admin.sewing.grid.show', $r->sewing_inspection_sheet_id) }}" class="text-xs text-blue-600 hover:underline">Open grid</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-400">No pending re-audits.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
