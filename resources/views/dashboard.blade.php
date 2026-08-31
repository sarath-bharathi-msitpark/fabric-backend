@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Fabric Management Dashboard')

@section('actions')
    <button onclick="window.print()" class="px-3 py-1.5 text-xs rounded-md border border-gray-300 hover:bg-gray-50">Export as PDF</button>
@endsection

@section('content')
<div id="dashboardRoot" x-data="{ filters: { buyer_id: '{{ $filters['buyer_id'] ?? '' }}', style_id: '{{ $filters['style_id'] ?? '' }}', supplier_id: '{{ $filters['supplier_id'] ?? '' }}', fabric_type: '{{ $filters['fabric_type'] ?? '' }}', color: '{{ $filters['color'] ?? '' }}', from: '{{ $filters['from'] ?? '' }}', to: '{{ $filters['to'] ?? '' }}' }, apply() { DashboardApp.load(this.filters); }, clear() { this.filters = { buyer_id:'', style_id:'', supplier_id:'', fabric_type:'', color:'', from:'', to:'' }; DashboardApp.load(this.filters); } }" x-init="DashboardApp.init(filters)">
    {{-- Filter Bar --}}
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6 sticky top-16 z-20 print:hidden">
        <form id="filterForm" @submit.prevent="apply()">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                <select x-model="filters.buyer_id" class="rounded-md border-gray-300 text-sm">
                    <option value="">All Buyers</option>
                    @foreach($buyers as $b)<option value="{{ $b->id }}">{{ $b->buyer_name }}</option>@endforeach
                </select>
                <select x-model="filters.style_id" class="rounded-md border-gray-300 text-sm">
                    <option value="">All Styles</option>
                    @foreach($styles as $s)<option value="{{ $s->id }}">{{ $s->style_number }}</option>@endforeach
                </select>
                <select x-model="filters.supplier_id" class="rounded-md border-gray-300 text-sm">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->supplier_name }}</option>@endforeach
                </select>
                <select x-model="filters.fabric_type" class="rounded-md border-gray-300 text-sm">
                    <option value="">All Fabric Types</option>
                    @foreach($fabricTypes as $ft)<option value="{{ $ft }}">{{ $ft }}</option>@endforeach
                </select>
                <select x-model="filters.color" class="rounded-md border-gray-300 text-sm">
                    <option value="">All Colors</option>
                    @foreach($colors as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                </select>
                <input type="date" x-model="filters.from" class="rounded-md border-gray-300 text-sm" placeholder="From">
                <input type="date" x-model="filters.to" class="rounded-md border-gray-300 text-sm" placeholder="To">
            </div>
            <div class="mt-3 flex gap-2">
                <button type="submit" class="px-4 py-1.5 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Apply</button>
                <button type="button" @click="clear()" class="px-4 py-1.5 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Clear Filters</button>
            </div>
        </form>
    </div>

    {{-- Row 1: KPI cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <x-kpi-card :label="$kpis['total_required']['label']" :value="$kpis['total_required']['value']" :status="$kpis['total_required']['status']" unit="kg" />
        <x-kpi-card :label="$kpis['total_received']['label']" :value="$kpis['total_received']['value']" :status="$kpis['total_received']['status']" unit="kg" />
        <x-kpi-card :label="$kpis['total_approved']['label']" :value="$kpis['total_approved']['value']" :status="$kpis['total_approved']['status']" unit="kg" />
        <x-kpi-card :label="$kpis['pass_rate']['label']" :value="$kpis['pass_rate']['value']" :status="$kpis['pass_rate']['status']" unit="%" :target="$kpis['pass_rate']['target'] ?? null" />
        <x-kpi-card :label="$kpis['rejection_rate']['label']" :value="$kpis['rejection_rate']['value']" :status="$kpis['rejection_rate']['status']" unit="%" :target="$kpis['rejection_rate']['target'] ?? null" />
        <x-kpi-card :label="$kpis['delayed_lots']['label']" :value="$kpis['delayed_lots']['value']" :status="$kpis['delayed_lots']['status']" :target="$kpis['delayed_lots']['target'] ?? null" />
    </div>

    {{-- Row 2: Trends --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Daily Receipt Trend (30 days)</h3>
            <div style="height:240px"><canvas id="trendChart"></canvas></div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Fabric Status Breakdown</h3>
            <div style="height:240px"><canvas id="statusChart"></canvas></div>
        </div>
    </div>

    {{-- Row 3: Performance --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Supplier Performance (Top 10)</h3>
            <div style="height:240px"><canvas id="supplierChart"></canvas></div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Inspection Completion %</h3>
            <div class="relative" style="height:240px">
                <canvas id="gaugeChart"></canvas>
                <div id="gaugeLabel" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <span class="text-3xl font-bold text-gray-700">{{ number_format($kpis['inspection_completed']['value'], 2) }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 4: Quality & Inventory --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Top Defects (Pareto)</h3>
            <div style="height:240px"><canvas id="paretoChart"></canvas></div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Stock by Fabric Type</h3>
            <div style="height:240px"><canvas id="stockChart"></canvas></div>
        </div>
    </div>

    {{-- Row 5: Action Items --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Consumption vs Plan (by Style)</h3>
            <div style="height:240px"><canvas id="consumptionChart"></canvas></div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700">Critical Alerts</h3>
                <span class="text-xs text-gray-400">{{ $alerts->count() }} open</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase">
                        <tr><th class="px-2 py-2 text-left">Type</th><th class="px-2 py-2 text-left">Lot/Supplier</th><th class="px-2 py-2 text-left">Message</th><th class="px-2 py-2 text-left">Sev</th><th class="px-2 py-2 text-left">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($alerts as $alert)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-2"><x-status-badge :status="$alert->alert_type" /></td>
                            <td class="px-2 py-2">{{ $alert->fabricRecord?->lot_no ?? $alert->supplier?->supplier_name }}</td>
                            <td class="px-2 py-2 max-w-xs truncate" title="{{ $alert->message }}">{{ $alert->message }}</td>
                            <td class="px-2 py-2"><x-status-badge :status="$alert->severity" /></td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                @can('resolve', $alert)
                                <x-form-modal :id="'alert-'.$alert->id" title="Resolve Alert" :confirm-text="'Resolve'">
                                    <button class="text-blue-600 hover:underline">Resolve</button>
                                    <x-slot:content>
                                        <form method="POST" action="{{ route('alerts.resolve', $alert) }}">@csrf @method('PATCH')
                                            <p class="text-sm text-gray-600 mb-3">{{ $alert->message }}</p>
                                            <textarea name="resolution_note" rows="3" placeholder="Resolution note..." class="w-full rounded-md border-gray-300 text-sm"></textarea>
                                            <div class="mt-3 flex justify-end gap-2">
                                                <button type="button" @click="open = false" class="px-3 py-1.5 text-sm border rounded-md">Cancel</button>
                                                <button type="submit" class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-md">Resolve</button>
                                            </div>
                                        </form>
                                    </x-slot:content>
                                </x-form-modal>
                                @endcan
                                @if($alert->fabric_record_id)
                                <a href="{{ route('admin.fabric-records.show', $alert->fabric_record_id) }}" class="ml-2 text-gray-600 hover:underline">View</a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-2 py-6 text-center text-gray-400">No open alerts</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
window.DashboardApp = {
    charts: {},
    init(filters) { this.load(filters); },
    async load(filters) {
        const params = new URLSearchParams(filters || {});
        if (filters) { Object.keys(filters).forEach(k => { if(!filters[k]) params.delete(k); }); }
        try {
            const res = await fetch('{{ route('dashboard.data') }}?' + params, { headers: { 'Accept':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }});
            const data = await res.json();
            this.render(data);
        } catch(e) { console.error('Dashboard load failed:', e); }
    },
    render(data) {
        this.renderTrend(data.trend);
        this.renderStatus(data.status_breakdown);
        this.renderSupplier(data.supplier_performance);
        this.renderGauge(data.inspection_gauge);
        this.renderPareto(data.top_defects);
        this.renderStock(data.stock_by_type);
        this.renderConsumption(data.consumption_vs_plan);
    },
    renderTrend(trend) {
        const ctx = document.getElementById('trendChart');
        if (!ctx) return;
        const labels = Object.keys(trend);
        const values = Object.values(trend).map(v => parseFloat(v));
        if (this.charts.trend) this.charts.trend.destroy();
        this.charts.trend = new Chart(ctx, { type:'line', data:{ labels, datasets:[{ label:'Received kg', data: values, borderColor:'#0ea5e9', backgroundColor:'rgba(14,165,233,0.1)', fill:true, tension:0.3 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{ beginAtZero:true } } } });
    },
    renderStatus(s) {
        const ctx = document.getElementById('statusChart');
        if (!ctx) return;
        if (this.charts.status) this.charts.status.destroy();
        this.charts.status = new Chart(ctx, { type:'doughnut', data:{ labels:['Pending','Approved','Rejected'], datasets:[{ data:[s.pending || 0, s.approved || 0, s.rejected || 0], backgroundColor:['#f59e0b','#10b981','#ef4444'] }] }, options:{ responsive:true, maintainAspectRatio:false } });
    },
    renderSupplier(sp) {
        const ctx = document.getElementById('supplierChart');
        if (!ctx) return;
        const labels = sp.map(s => s.supplier_name);
        const values = sp.map(s => parseFloat(s.quality_pct));
        const colors = sp.map(s => s.rating === 'excellent' ? '#10b981' : s.rating === 'good' ? '#3b82f6' : s.rating === 'average' ? '#f59e0b' : '#ef4444');
        if (this.charts.supplier) this.charts.supplier.destroy();
        this.charts.supplier = new Chart(ctx, { type:'bar', data:{ labels, datasets:[{ label:'Quality %', data: values, backgroundColor: colors }] }, options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ x:{ beginAtZero:true, max:100 } } } });
    },
    renderGauge(g) {
        const ctx = document.getElementById('gaugeChart');
        if (!ctx) return;
        const pct = Math.min(100, Math.max(0, parseFloat(g.value)));
        const color = g.status === 'green' ? '#10b981' : g.status === 'yellow' ? '#f59e0b' : '#ef4444';
        if (this.charts.gauge) this.charts.gauge.destroy();
        this.charts.gauge = new Chart(ctx, { type:'doughnut', data:{ labels:['Completed','Remaining'], datasets:[{ data:[pct, Math.max(0.01,100-pct)], backgroundColor:[color, '#e5e7eb'], borderWidth:0 }] }, options:{ responsive:true, maintainAspectRatio:false, cutout:'72%', plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: (c) => c.dataIndex === 0 ? pct + '%' : (100-pct) + '%' } } } } });
        const labelEl = document.getElementById('gaugeLabel').querySelector('span');
        if (labelEl) { labelEl.textContent = pct + '%'; labelEl.className = 'text-3xl font-bold ' + (g.status === 'green' ? 'text-green-600' : g.status === 'yellow' ? 'text-yellow-600' : 'text-red-600'); }
    },
    renderPareto(defects) {
        const ctx = document.getElementById('paretoChart');
        if (!ctx) return;
        const labels = defects.map(d => d.defect_type);
        const counts = defects.map(d => parseInt(d.total));
        const total = counts.reduce((a,b)=>a+b,0) || 1;
        let cum = 0;
        const cumPct = counts.map(c => { cum += c; return (cum/total)*100; });
        if (this.charts.pareto) this.charts.pareto.destroy();
        this.charts.pareto = new Chart(ctx, { data:{ labels, datasets:[{ type:'bar', label:'Count', data: counts, backgroundColor:'#0ea5e9' },{ type:'line', label:'Cumulative %', data: cumPct, borderColor:'#ef4444', yAxisID:'y1' }] }, options:{ responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:true }, y1:{ position:'right', max:100, beginAtZero:true } } } });
    },
    renderStock(stock) {
        const ctx = document.getElementById('stockChart');
        if (!ctx) return;
        const labels = stock.map(s => s.fabric_type);
        if (this.charts.stock) this.charts.stock.destroy();
        this.charts.stock = new Chart(ctx, { type:'bar', data:{ labels, datasets:[{ label:'Received', data: stock.map(s=>parseFloat(s.received)), backgroundColor:'#0ea5e9' },{ label:'Approved', data: stock.map(s=>parseFloat(s.approved)), backgroundColor:'#10b981' },{ label:'Rejected', data: stock.map(s=>parseFloat(s.rejected)), backgroundColor:'#ef4444' }] }, options:{ responsive:true, maintainAspectRatio:false, scales:{ x:{ stacked:true }, y:{ stacked:true } } } });
    },
    renderConsumption(cp) {
        const ctx = document.getElementById('consumptionChart');
        if (!ctx) return;
        const labels = cp.map(c => c.style_number);
        if (this.charts.consumption) this.charts.consumption.destroy();
        this.charts.consumption = new Chart(ctx, { type:'bar', data:{ labels, datasets:[{ label:'Planned', data: cp.map(c=>parseFloat(c.planned)), backgroundColor:'#6366f1' },{ type:'line', label:'Actual (Approved)', data: cp.map(c=>parseFloat(c.actual)), borderColor:'#10b981', fill:false } ] }, options:{ responsive:true, maintainAspectRatio:false } });
    }
};
</script>
@endpush
@endsection
