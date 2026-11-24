@extends('layouts.app')

@section('title', 'Inspection Sheets')
@section('header', 'Sewing Inspection Sheets')

@section('actions')
<a href="{{ route('admin.sewing.sheets.create') }}" class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">+ New Inspection</a>
@endsection

@section('content')
<form method="GET" action="{{ route('admin.sewing.sheets.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Sheet / Style / PO / LOT" class="rounded-md border-gray-300 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
        <select name="status" class="rounded-md border-gray-300 text-sm">
            <option value="">All</option>
            @foreach($statuses as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach
        </select>
    </div>
    <button class="px-4 py-2 text-sm rounded-md bg-slate-700 text-white">Filter</button>
    <a href="{{ route('admin.sewing.sheets.index') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300">Reset</a>
</form>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">Sheet No</th>
                    <th class="px-3 py-2 text-left">Date</th>
                    <th class="px-3 py-2 text-left">Line</th>
                    <th class="px-3 py-2 text-left">Buyer</th>
                    <th class="px-3 py-2 text-left">Style</th>
                    <th class="px-3 py-2 text-left">PO#</th>
                    <th class="px-3 py-2 text-left">QC</th>
                    <th class="px-3 py-2 text-left">Round</th>
                    <th class="px-3 py-2 text-left">Status</th>
                    <th class="px-3 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sheets as $sheet)
                <tr>
                    <td class="px-3 py-2 font-mono font-medium">{{ $sheet->sheet_no }}</td>
                    <td class="px-3 py-2">{{ optional($sheet->inspection_date)->format('d M Y') }}</td>
                    <td class="px-3 py-2">{{ $sheet->line->line_no ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $sheet->buyer->buyer_name ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $sheet->style_number ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $sheet->po_number ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $sheet->qc->name ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $sheet->current_round }}</td>
                    <td class="px-3 py-2"><x-status-badge :status="$sheet->status" /></td>
                    <td class="px-3 py-2">
                        <div class="inline-flex items-center gap-1">
                            <a href="{{ route('admin.sewing.sheets.show', $sheet) }}" class="px-2.5 py-1.5 text-xs rounded-md bg-gray-50 text-gray-700 hover:bg-gray-100">View</a>
                            <a href="{{ route('admin.sewing.grid.show', $sheet) }}" class="px-2.5 py-1.5 text-xs rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100">Grid</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="px-3 py-8 text-center text-gray-400">No inspection sheets yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $sheets->links() }}</div>
</div>
@endsection
