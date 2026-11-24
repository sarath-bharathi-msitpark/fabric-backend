@extends('layouts.app')

@section('title', $title)
@section('header', $title)

@section('actions')
    <a href="{{ route('admin.sewing.reports.export', array_merge(['type' => $type, 'format' => 'xlsx'], request()->query())) }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50 whitespace-nowrap">Excel</a>
    <a href="{{ route('admin.sewing.reports.export', array_merge(['type' => $type, 'format' => 'pdf'], request()->query())) }}" class="px-3 py-2 text-xs rounded-md bg-slate-700 text-white hover:bg-slate-800 whitespace-nowrap">PDF</a>
    <button type="button" onclick="window.print()" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50 whitespace-nowrap">Print</button>
    <a href="{{ route('admin.sewing.reports.index') }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50 whitespace-nowrap">All reports</a>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm p-4 mb-4 print:hidden">
    <form method="GET" action="{{ route('admin.sewing.reports.show', $type) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-md border-gray-300 text-sm" title="From"></div>
        <div><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-md border-gray-300 text-sm" title="To"></div>
        <div>
            <select name="sewing_line_id" class="w-full rounded-md border-gray-300 text-sm">
                <option value="">All lines</option>
                @foreach($lines as $line)<option value="{{ $line->id }}" @selected(($filters['sewing_line_id'] ?? '') == $line->id)>{{ $line->line_no }}</option>@endforeach
            </select>
        </div>
        <div>
            <select name="buyer_id" class="w-full rounded-md border-gray-300 text-sm">
                <option value="">All buyers</option>
                @foreach($buyers as $buyer)<option value="{{ $buyer->id }}" @selected(($filters['buyer_id'] ?? '') == $buyer->id)>{{ $buyer->buyer_name }}</option>@endforeach
            </select>
        </div>
        <div><input type="text" name="style_number" value="{{ $filters['style_number'] ?? '' }}" placeholder="Style #" class="w-full rounded-md border-gray-300 text-sm"></div>
        <div><input type="text" name="shift" value="{{ $filters['shift'] ?? '' }}" placeholder="Shift" class="w-full rounded-md border-gray-300 text-sm"></div>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-2">
            <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Apply</button>
            <a href="{{ route('admin.sewing.reports.show', $type) }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Reset</a>
        </div>
    </form>
</div>

<div class="hidden print:block mb-3">
    <div class="text-base font-semibold">{{ config('app.name', 'INR Global Sourcing') }}</div>
    <div class="text-sm">{{ $title }}</div>
    <div class="text-xs text-gray-500">Generated {{ now()->format('d M Y H:i') }}</div>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-[10px]">
                <tr>@foreach($headings as $h)<th class="px-3 py-2 text-left">{{ $h }}</th>@endforeach</tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                <tr>@foreach($row as $cell)<td class="px-3 py-2">{{ $cell }}</td>@endforeach</tr>
                @empty
                <tr><td colspan="{{ max(count($headings), 1) }}" class="px-3 py-8 text-center text-gray-400">No data for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
