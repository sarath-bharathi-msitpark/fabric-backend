@extends('layouts.app')

@section('title', $title)
@section('header', $title)

@section('actions')
    <a href="{{ route('admin.cutting.reports.export', array_merge(['type' => $type, 'format' => 'xlsx'], request()->query())) }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50">Excel</a>
    <a href="{{ route('admin.cutting.reports.export', array_merge(['type' => $type, 'format' => 'pdf'], request()->query())) }}" class="px-3 py-2 text-xs rounded-md bg-slate-700 text-white hover:bg-slate-800">PDF</a>
    <a href="{{ route('admin.cutting.reports.index') }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50">All reports</a>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm p-4 mb-4 print:hidden">
    <form method="GET" action="{{ route('admin.cutting.reports.show', $type) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-md border-gray-300 text-sm" title="From"></div>
        <div><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-md border-gray-300 text-sm" title="To"></div>
        <div>
            <select name="buyer_id" class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="All buyers">
                <option value="">All buyers</option>
                @foreach($buyers as $buyer)<option value="{{ $buyer->id }}" @selected(($filters['buyer_id'] ?? '')==$buyer->id)>{{ $buyer->buyer_name }}</option>@endforeach
            </select>
        </div>
        <div>
            <select name="cutting_table_id" class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="All tables">
                <option value="">All tables</option>
                @foreach($tables as $table)<option value="{{ $table->id }}" @selected(($filters['cutting_table_id'] ?? '')==$table->id)>{{ $table->table_no }}</option>@endforeach
            </select>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 sm:col-span-2 lg:col-span-1">
            <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Apply</button>
            <a href="{{ route('admin.cutting.reports.show', $type) }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Reset</a>
        </div>
    </form>
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
                <tr><td colspan="{{ count($headings) }}" class="px-3 py-8 text-center text-gray-400">No data for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
