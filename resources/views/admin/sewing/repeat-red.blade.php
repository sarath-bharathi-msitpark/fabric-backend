@extends('layouts.app')

@section('title', 'Repeat Red Operators')
@section('header', 'Repeat Red Operators')

@section('content')
<form method="GET" action="{{ route('admin.sewing.repeat-red.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
    <div><label class="block text-xs font-medium text-gray-600 mb-1">From</label><input type="date" name="from" value="{{ request('from') }}" class="rounded-md border-gray-300 text-sm"></div>
    <div><label class="block text-xs font-medium text-gray-600 mb-1">To</label><input type="date" name="to" value="{{ request('to') }}" class="rounded-md border-gray-300 text-sm"></div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Line</label>
        <select name="sewing_line_id" class="rounded-md border-gray-300 text-sm">
            <option value="">All</option>
            @foreach($lines as $line)<option value="{{ $line->id }}" @selected(request('sewing_line_id') == $line->id)>{{ $line->line_no }}</option>@endforeach
        </select>
    </div>
    <button class="px-4 py-2 text-sm rounded-md bg-slate-700 text-white">Filter</button>
    <a href="{{ route('admin.sewing.repeat-red.index') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300">Reset</a>
</form>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">Operator</th>
                    <th class="px-3 py-2 text-left">Employee Code</th>
                    <th class="px-3 py-2 text-left">Line</th>
                    <th class="px-3 py-2 text-center">Red Cells</th>
                    <th class="px-3 py-2 text-center">Escalated</th>
                    <th class="px-3 py-2 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                <tr>
                    <td class="px-3 py-2 font-medium text-gray-800">{{ $row->operator_name ?? 'Unassigned' }}</td>
                    <td class="px-3 py-2">{{ $row->employee_code ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $row->line_no ?? '—' }}</td>
                    <td class="px-3 py-2 text-center font-semibold text-red-600">{{ $row->red_cells }}</td>
                    <td class="px-3 py-2 text-center">{{ $row->escalated }}</td>
                    <td class="px-3 py-2 text-center">
                        @if($row->red_cells >= 2)
                            <x-status-badge status="red" label="Repeat red" />
                        @else
                            <x-status-badge status="yellow" label="Single red" />
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-3 py-8 text-center text-gray-400">No red cells recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
