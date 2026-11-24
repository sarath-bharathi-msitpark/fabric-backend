@extends('layouts.app')

@section('title', 'Inspection Sheet')
@section('header', 'Inspection Sheet — '.$sheet->sheet_no)

@section('actions')
<a href="{{ route('admin.sewing.grid.show', $sheet) }}" class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">Open Grid</a>
<a href="{{ route('admin.sewing.sheets.index') }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50 whitespace-nowrap">All Sheets</a>
@endsection

@section('content')
@php
    $rounds = $sheet->rounds->sortBy('round_no')->values();
    $current = $sheet->current_round;
    $byOp = $sheet->results->groupBy('sewing_inspection_operation_id')->map(fn ($g) => $g->keyBy('round_no'));
    $badge = fn ($r) => $r === 'green' ? 'green' : ($r === 'amber' ? 'yellow' : ($r === 'red' ? 'red' : 'pending'));
    $signed = $sheet->signoffs->keyBy('role');
    $signoffOrder = $signoffOrder ?? \App\Services\SewingInspectionService::SIGNOFF_ORDER;
    $signoffPermissions = $signoffPermissions ?? \App\Services\SewingInspectionService::SIGNOFF_PERMISSIONS;
@endphp

@if(session('success'))<div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>@endif
@if($errors->any())
    <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-4">
        <h3 class="font-semibold text-gray-800 text-sm mb-3">Order Information</h3>
        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-y-3 gap-x-4 text-sm">
            <div><dt class="text-xs text-gray-400">Buyer</dt><dd class="text-gray-800">{{ $sheet->buyer->buyer_name ?? '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400">PO#</dt><dd class="text-gray-800">{{ $sheet->po_number ?? '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400">Style No</dt><dd class="text-gray-800">{{ $sheet->style_number ?? '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400">Style Name</dt><dd class="text-gray-800">{{ $sheet->style_name ?? '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400">Colour</dt><dd class="text-gray-800">{{ $sheet->color ?? '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400">Line</dt><dd class="text-gray-800">{{ $sheet->line->line_no ?? '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400">LOT#</dt><dd class="text-gray-800">{{ $sheet->lot_number ?? '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400">Bundle#</dt><dd class="text-gray-800">{{ $sheet->bundle_no ?? '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400">Shift</dt><dd class="text-gray-800">{{ $sheet->shift ?? '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400">QC</dt><dd class="text-gray-800">{{ $sheet->qc->name ?? '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400">Inspection Date</dt><dd class="text-gray-800">{{ optional($sheet->inspection_date)->format('d M Y') }}</dd></div>
            <div><dt class="text-xs text-gray-400">Pieces / audit</dt><dd class="text-gray-800">{{ $sheet->pieces_per_audit }}</dd></div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="font-semibold text-gray-800 text-sm mb-3">Status</h3>
        <div class="flex items-center gap-2 mb-2"><x-status-badge :status="$sheet->status" /></div>
        <p class="text-sm text-gray-500">Current round: <strong class="text-gray-800">{{ $current }} / {{ $rounds->count() }}</strong></p>
        <p class="text-sm text-gray-500 mt-1">Defects logged: <strong class="text-gray-800">{{ $sheet->defectLogs->count() }}</strong></p>
        @if($sheet->isLocked())<p class="mt-2 text-xs text-gray-500">Locked {{ optional($sheet->locked_at)->format('d M Y H:i') }}</p>@endif
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-4">
    <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-800 text-sm">Results by Round</div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">Operation</th>
                    @foreach($rounds as $round)<th class="px-3 py-2 text-center">R{{ $round->round_no }}</th>@endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($sheet->operations as $op)
                <tr>
                    <td class="px-3 py-2 text-gray-800">{{ $op->operation_name }}</td>
                    @foreach($rounds as $round)
                        @php $res = ($byOp[$op->id] ?? collect())->get($round->round_no); @endphp
                        <td class="px-3 py-2 text-center">
                            @if($res)<x-status-badge :status="$badge($res->result)" :label="$res->defect_count" />@else<span class="text-gray-300">—</span>@endif
                        </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <h3 class="font-semibold text-gray-800 text-sm mb-3">Sign-offs</h3>
    <div class="space-y-3">
        @foreach($signoffOrder as $i => $role)
            @php
                $done = $signed->get($role);
                $priorComplete = collect(array_slice($signoffOrder, 0, $i))->every(fn ($r) => $signed->has($r));
                $canSign = ! $sheet->isLocked() && ! $done && $priorComplete && auth()->user()->can($signoffPermissions[$role]);
            @endphp
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border border-gray-100 rounded-md p-3">
                <div>
                    <div class="text-sm font-medium text-gray-800">{{ \App\Models\SewingSignoff::ROLES[$role] }}</div>
                    @if($done)
                        <div class="text-xs text-green-600">Signed by {{ $done->user_name }} on {{ optional($done->signed_at)->format('d M Y H:i') }}</div>
                        @if($done->comments)<div class="text-xs text-gray-500">{{ $done->comments }}</div>@endif
                    @else
                        <div class="text-xs text-gray-400">Pending</div>
                    @endif
                </div>
                @if($canSign)
                    <form method="POST" action="{{ route('admin.sewing.sheets.signoff', $sheet) }}" class="flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="role" value="{{ $role }}">
                        <input type="text" name="comments" placeholder="Comments" class="rounded-md border-gray-300 text-sm">
                        <button class="px-3 py-2 text-xs rounded-md bg-green-600 text-white hover:bg-green-700">Sign</button>
                    </form>
                @elseif($done)
                    <x-status-badge status="approved" label="Signed" />
                @else
                    <x-status-badge status="pending" />
                @endif
            </div>
        @endforeach
    </div>
</div>

@if($sheet->isLocked() && auth()->user()->can('manage sewing rules'))
<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <h3 class="font-semibold text-gray-800 text-sm mb-1">Controlled Post-lock Correction</h3>
    <p class="text-xs text-gray-500 mb-3">Editing a locked sheet is recorded in the audit trail with the reason below. Only header fields can be corrected.</p>
    <form method="POST" action="{{ route('admin.sewing.sheets.correct', $sheet) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        @csrf
        <div><label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Style Name</label><input type="text" name="style_name" value="{{ $sheet->style_name }}" class="w-full rounded-md border-gray-300 text-sm"></div>
        <div><label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Colour</label><input type="text" name="color" value="{{ $sheet->color }}" class="w-full rounded-md border-gray-300 text-sm"></div>
        <div><label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">PO#</label><input type="text" name="po_number" value="{{ $sheet->po_number }}" class="w-full rounded-md border-gray-300 text-sm"></div>
        <div><label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">LOT#</label><input type="text" name="lot_number" value="{{ $sheet->lot_number }}" class="w-full rounded-md border-gray-300 text-sm"></div>
        <div><label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Bundle#</label><input type="text" name="bundle_no" value="{{ $sheet->bundle_no }}" class="w-full rounded-md border-gray-300 text-sm"></div>
        <div><label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Shift</label><input type="text" name="shift" value="{{ $sheet->shift }}" class="w-full rounded-md border-gray-300 text-sm"></div>
        <div><label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Inspection Date</label><input type="date" name="inspection_date" value="{{ optional($sheet->inspection_date)->format('Y-m-d') }}" class="w-full rounded-md border-gray-300 text-sm"></div>
        <div class="lg:col-span-3"><label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Reason (required)</label><input type="text" name="reason" required class="w-full rounded-md border-gray-300 text-sm"></div>
        <div class="lg:col-span-1 flex items-end"><button class="w-full px-3 py-2 text-xs rounded-md bg-slate-700 text-white hover:bg-slate-800">Apply correction</button></div>
    </form>
</div>
@endif

<div class="bg-white rounded-lg shadow-sm p-4">
    <h3 class="font-semibold text-gray-800 text-sm mb-3">Defect Log ({{ $sheet->defectLogs->count() }})</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">Round</th>
                    <th class="px-3 py-2 text-left">Operation</th>
                    <th class="px-3 py-2 text-left">CAPA</th>
                    <th class="px-3 py-2 text-left">Defect</th>
                    <th class="px-3 py-2 text-center">Qty</th>
                    <th class="px-3 py-2 text-left">Root Cause</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sheet->defectLogs->sortBy([['round_no','asc'],['id','asc']]) as $log)
                <tr>
                    <td class="px-3 py-2">R{{ $log->round_no }}</td>
                    <td class="px-3 py-2">{{ $log->operation->operation_name ?? '—' }}</td>
                    <td class="px-3 py-2 font-mono">{{ $log->capa_code }}</td>
                    <td class="px-3 py-2">{{ $log->defect_name }}</td>
                    <td class="px-3 py-2 text-center">{{ $log->qty }}</td>
                    <td class="px-3 py-2 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($log->root_cause, 60) ?: '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-400">No defects logged.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
