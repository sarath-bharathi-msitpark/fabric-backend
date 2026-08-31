@extends('layouts.app')

@section('title', $supplier->supplier_name . ' Performance')
@section('header', 'Supplier Performance — ' . $supplier->supplier_name)

@section('actions')
    <a href="{{ route('admin.suppliers.index') }}" class="px-3 py-1.5 text-xs rounded-md border border-gray-300 hover:bg-gray-50">Back to Suppliers</a>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4"><div class="text-xs uppercase text-gray-500">On-Time %</div><div class="text-2xl font-bold text-blue-600 mt-1">{{ number_format((float)$supplier->on_time_pct, 2) }}</div></div>
    <div class="bg-white rounded-lg shadow-sm p-4"><div class="text-xs uppercase text-gray-500">Quality %</div><div class="text-2xl font-bold text-green-600 mt-1">{{ number_format((float)$supplier->quality_pct, 2) }}</div></div>
    <div class="bg-white rounded-lg shadow-sm p-4"><div class="text-xs uppercase text-gray-500">Rating</div><div class="text-2xl font-bold mt-1"><x-status-badge :status="$supplier->rating" /></div></div>
    <div class="bg-white rounded-lg shadow-sm p-4"><div class="text-xs uppercase text-gray-500">Total Lots</div><div class="text-2xl font-bold text-gray-800 mt-1">{{ $supplier->fabricRecords->count() }}</div></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">On-Time Delivery Trend (6 months)</h3>
        <canvas id="onTimeChart" height="120"></canvas>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Quality % Trend</h3>
        <canvas id="qualityChart" height="120"></canvas>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <h3 class="text-sm font-semibold text-gray-700 p-4 border-b border-gray-100">Lots Supplied</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr><th class="px-3 py-2 text-left">Lot No</th><th class="px-3 py-2 text-left">Style</th><th class="px-3 py-2 text-right">Ordered</th><th class="px-3 py-2 text-right">Approved</th><th class="px-3 py-2 text-right">Rejected</th><th class="px-3 py-2 text-right">Pass %</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($supplier->fabricRecords as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-medium text-blue-600"><a href="{{ route('admin.fabric-records.show', $r) }}">{{ $r->lot_no }}</a></td>
                    <td class="px-3 py-2">{{ $r->style?->style_number }}</td>
                    <td class="px-3 py-2 text-right">{{ number_format((float)$r->ordered_kg, 2) }}</td>
                    <td class="px-3 py-2 text-right text-green-700">{{ number_format((float)($r->inspection?->approved_kg ?? 0), 2) }}</td>
                    <td class="px-3 py-2 text-right text-red-700">{{ number_format((float)($r->inspection?->rejected_kg ?? 0), 2) }}</td>
                    <td class="px-3 py-2 text-right">{{ $r->inspection?->pass_pct ? number_format((float)$r->inspection->pass_pct, 2) : '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-400">No lots.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
const trend = {!! $trend->toJson() !!};
new Chart(document.getElementById('onTimeChart'), { type:'line', data:{ labels: trend.map(t=>t.month), datasets:[{ label:'On-Time %', data: trend.map(t=>t.on_time_pct), borderColor:'#0ea5e9', tension:0.3 }] }, options:{ responsive:true, scales:{ y:{ beginAtZero:true, max:100 } } } });
new Chart(document.getElementById('qualityChart'), { type:'line', data:{ labels: trend.map(t=>t.month), datasets:[{ label:'Quality %', data: trend.map(t=>t.quality_pct), borderColor:'#10b981', tension:0.3 }] }, options:{ responsive:true, scales:{ y:{ beginAtZero:true, max:100 } } } });
</script>
@endpush
@endsection
