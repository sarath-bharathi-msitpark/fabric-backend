@extends('layouts.app')

@section('title', 'Inspection Grid')
@section('header', 'Inspection Grid — '.$sheet->sheet_no)

@section('actions')
<a href="{{ route('admin.sewing.sheets.show', $sheet) }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50 whitespace-nowrap">Sheet Summary</a>
<a href="{{ route('admin.sewing.sheets.index') }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50 whitespace-nowrap">All Sheets</a>
@endsection

@section('content')
@php
    $rounds = $sheet->rounds->sortBy('round_no')->values();
    $current = $sheet->current_round;
    $byOp = $sheet->results->groupBy('sewing_inspection_operation_id')->map(fn ($g) => $g->keyBy('round_no'));
    $badge = fn ($r) => $r === 'green' ? 'green' : ($r === 'amber' ? 'yellow' : ($r === 'red' ? 'red' : 'pending'));
    $canEdit = auth()->user()->can('edit sewing inspection');
    $isLocked = $sheet->isLocked();
    $currentResults = $sheet->results->where('round_no', $current);
    $totalOps = $sheet->operations->count();
    $checkedOps = $currentResults->count();
    $progress = $totalOps > 0 ? round($checkedOps / $totalOps * 100) : 0;
@endphp

@if(session('success'))<div class="mb-4 rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>@endif
@if($errors->any())
    <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
        <span class="font-mono font-semibold text-gray-800">{{ $sheet->sheet_no }}</span>
        <span class="text-gray-500">Line: <strong class="text-gray-800">{{ $sheet->line->line_no ?? '—' }}</strong></span>
        <span class="text-gray-500">Buyer: <strong class="text-gray-800">{{ $sheet->buyer->buyer_name ?? '—' }}</strong></span>
        <span class="text-gray-500">Style: <strong class="text-gray-800">{{ $sheet->style_number ?? '—' }}</strong></span>
        <span class="text-gray-500">PO#: <strong class="text-gray-800">{{ $sheet->po_number ?? '—' }}</strong></span>
        <span class="text-gray-500">Date: <strong class="text-gray-800">{{ optional($sheet->inspection_date)->format('d M Y') }}</strong></span>
        <span class="text-gray-500">Pieces/audit: <strong class="text-gray-800">{{ $sheet->pieces_per_audit }}</strong></span>
        <span class="text-gray-500">Current round: <strong class="text-gray-800">{{ $current }}</strong></span>
        <x-status-badge :status="$sheet->status" />
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
        <h3 class="font-semibold text-gray-800 text-sm">Round {{ $current }} progress</h3>
        <div class="flex items-center gap-3 text-xs">
            <span class="text-gray-500">Inspected <strong class="text-gray-800">{{ $checkedOps }}/{{ $totalOps }}</strong></span>
            <span class="inline-flex items-center gap-1 text-green-600"><span class="w-2 h-2 rounded-full bg-green-500"></span>Green {{ $currentResults->where('result', 'green')->count() }}</span>
            <span class="inline-flex items-center gap-1 text-amber-600"><span class="w-2 h-2 rounded-full bg-amber-500"></span>Amber {{ $currentResults->where('result', 'amber')->count() }}</span>
            <span class="inline-flex items-center gap-1 text-red-600"><span class="w-2 h-2 rounded-full bg-red-500"></span>Red {{ $currentResults->where('result', 'red')->count() }}</span>
        </div>
    </div>
    <div class="w-full h-2 rounded-full bg-gray-100 overflow-hidden">
        <div class="h-full bg-blue-500" style="width: {{ $progress }}%"></div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mb-4">
    <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-800 text-sm">Result Overview</div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">#</th>
                    <th class="px-3 py-2 text-left">Operation</th>
                    @foreach($rounds as $round)
                        <th class="px-3 py-2 text-center {{ $round->round_no === $current ? 'bg-blue-50 text-blue-700' : '' }}">R{{ $round->round_no }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sheet->operations as $op)
                <tr>
                    <td class="px-3 py-2 text-gray-400">{{ $op->sequence_no }}</td>
                    <td class="px-3 py-2">
                        <div class="font-medium text-gray-800">{{ $op->operation_name }}</div>
                        <div class="text-xs text-gray-400">{{ $op->operator->name ?? '—' }}{{ $op->machine_no ? ' · '.$op->machine_no : '' }}</div>
                    </td>
                    @foreach($rounds as $round)
                        @php $res = ($byOp[$op->id] ?? collect())->get($round->round_no); @endphp
                        <td class="px-3 py-2 text-center {{ $round->round_no === $current ? 'bg-blue-50/40' : '' }}">
                            @if($res)
                                <x-status-badge :status="$badge($res->result)" :label="$res->defect_count" />
                                @if($res->re_audit_result)
                                    <div class="mt-1 text-[10px] uppercase {{ $res->re_audit_result === 'cleared' ? 'text-green-600' : 'text-red-600' }}">re-audit {{ $res->re_audit_result }}</div>
                                @elseif($res->isRed())
                                    <div class="mt-1 text-[10px] uppercase text-amber-600">re-audit due</div>
                                @endif
                                @if($res->defect_count > 0)
                                    @php $loggedQty = $sheet->defectLogs->where('sewing_inspection_operation_id', $op->id)->where('round_no', $round->round_no)->where('is_reaudit', false)->sum('qty'); @endphp
                                    <div class="mt-1 text-[10px] {{ $loggedQty === $res->defect_count ? 'text-green-600' : 'text-red-600' }}">logged {{ $loggedQty }}/{{ $res->defect_count }}</div>
                                @endif
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
                @empty
                <tr><td colspan="{{ $rounds->count() + 2 }}" class="px-3 py-8 text-center text-gray-400">No operations on this sheet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(! $isLocked && $canEdit)
<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-800 text-sm">Round {{ $current }} — capture</h3>
        <form method="POST" action="{{ route('admin.sewing.grid.close-round', [$sheet, $current]) }}" onsubmit="return confirm('Close round {{ $current }}? It can no longer be edited.')">
            @csrf
            <button class="px-3 py-2 text-xs rounded-md bg-slate-700 text-white hover:bg-slate-800">Close round {{ $current }}</button>
        </form>
    </div>

    @foreach($sheet->operations as $op)
    @php $res = ($byOp[$op->id] ?? collect())->get($current); @endphp
    @php $logs = $sheet->defectLogs->where('sewing_inspection_operation_id', $op->id)->where('round_no', $current); @endphp
    <details class="border border-gray-100 rounded-md mb-2" @if($res && $res->isRed() && ! $res->re_audit_result) open @endif>
        <summary class="cursor-pointer px-3 py-2 flex items-center justify-between text-sm">
            <span class="font-medium text-gray-800">{{ $op->sequence_no }}. {{ $op->operation_name }}
                <span class="text-xs text-gray-400">{{ $op->operator->name ?? '' }}</span>
            </span>
            @if($res)<x-status-badge :status="$badge($res->result)" :label="$res->defect_count.' defects'" />@else<span class="text-xs text-gray-400">not inspected</span>@endif
        </summary>

        <div class="px-3 pb-3 space-y-3">
            <form method="POST" action="{{ route('admin.sewing.grid.result', [$sheet, $op]) }}" class="result-form grid grid-cols-1 lg:grid-cols-12 gap-2 items-end" data-green-max="{{ $rule->green_max }}" data-amber-max="{{ $rule->amber_max }}" data-reaudit="{{ $res && $res->re_audit_result ? 1 : 0 }}">
                @csrf
                <input type="hidden" name="round_no" value="{{ $current }}">
                <div class="lg:col-span-2">
                    <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Defect count</label>
                    <input type="number" name="defect_count" min="0" max="{{ $sheet->pieces_per_audit }}" required value="{{ $res->defect_count ?? 0 }}" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="lg:col-span-12 num-pad flex flex-wrap items-center gap-1" data-target="defect_count">
                    <span class="text-[10px] uppercase tracking-wide text-gray-400 mr-1">Keypad</span>
                    @for($n = 0; $n <= $sheet->pieces_per_audit; $n++)
                        <button type="button" class="num-pad-btn w-9 h-9 text-sm rounded-md border border-gray-300 text-gray-700 hover:bg-gray-100" data-value="{{ $n }}">{{ $n }}</button>
                    @endfor
                </div>
                <div class="lg:col-span-5">
                    <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Corrective action</label>
                    <input type="text" name="corrective_action" value="{{ $res->corrective_action ?? '' }}" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="lg:col-span-3">
                    <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Note</label>
                    <input type="text" name="note" value="{{ $res->note ?? '' }}" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="lg:col-span-2">
                    <button class="w-full px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">Save result</button>
                </div>
            </form>

            @if($res && $res->isRed())
            <form method="POST" action="{{ route('admin.sewing.grid.reaudit', [$sheet, $res]) }}" class="grid grid-cols-1 lg:grid-cols-12 gap-2 items-end border-t border-gray-100 pt-3">
                @csrf
                <div class="lg:col-span-2">
                    <label class="block text-[10px] uppercase tracking-wide text-amber-600 mb-1">Re-audit defects</label>
                    <input type="number" name="defect_count" min="0" max="100" required value="{{ $res->re_audit_count ?? '' }}" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="lg:col-span-6">
                    <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Re-audit note</label>
                    <input type="text" name="note" value="{{ $res->re_audit_note ?? '' }}" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="lg:col-span-4">
                    <button class="w-full px-3 py-2 text-xs rounded-md bg-amber-600 text-white hover:bg-amber-700">Record re-audit</button>
                </div>
                @if($res->re_audit_result)
                    <div class="lg:col-span-12 text-xs {{ $res->re_audit_result === 'cleared' ? 'text-green-600' : 'text-red-600' }}">
                        Re-audit {{ $res->re_audit_result }} ({{ $res->re_audit_count }} defects).
                    </div>
                @endif
            </form>
            @endif

            <form method="POST" action="{{ route('admin.sewing.grid.defect', [$sheet, $op]) }}" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-2 items-end border-t border-gray-100 pt-3">
                @csrf
                <input type="hidden" name="round_no" value="{{ $current }}">
                <div class="lg:col-span-4">
                    <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Defect (DCL)</label>
                    <select name="defect_master_id" required class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">Select defect</option>
                        @foreach($defects->groupBy('defect_type') as $type => $group)
                            <optgroup label="{{ $type }}">
                                @foreach($group as $d)<option value="{{ $d->id }}">{{ $d->capa_code }} — {{ $d->defect_name }}</option>@endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-1">
                    <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Qty</label>
                    <input type="number" name="qty" min="1" max="100" value="1" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Note</label>
                    <input type="text" name="note" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Photo</label>
                    <input type="file" name="photo" accept="image/*" class="w-full text-xs rounded-md border border-gray-300">
                </div>
                <div class="lg:col-span-1 flex items-center pt-4">
                    <label class="inline-flex items-center gap-1 text-xs"><input type="checkbox" name="is_reaudit" value="1"> Re-audit</label>
                </div>
                <div class="lg:col-span-2">
                    <button class="w-full px-3 py-2 text-xs rounded-md bg-slate-700 text-white hover:bg-slate-800">Log defect</button>
                </div>
            </form>

            @php $loggedQty = $logs->where('is_reaudit', false)->sum('qty'); $requiredQty = $res->defect_count ?? 0; @endphp
            <div class="text-xs text-gray-600 border-t border-gray-100 pt-2">
                <div class="font-medium text-gray-500 mb-1">
                    Logged {{ $loggedQty }} of {{ $requiredQty }}
                    @if($requiredQty > 0 && $loggedQty !== $requiredQty)
                        <span class="text-red-600">— every defective piece must be logged before the round can close</span>
                    @elseif($requiredQty > 0)
                        <span class="text-green-600">— complete</span>
                    @endif
                </div>
                @if($logs->isNotEmpty())
                <ul class="space-y-0.5">
                    @foreach($logs as $log)
                        <li class="flex items-center gap-2">
                            <span><span class="font-mono">{{ $log->capa_code }}</span> {{ $log->defect_name }} ×{{ $log->qty }}@if($log->is_reaudit) <span class="text-amber-600">(re-audit)</span>@endif</span>
                            @if($log->photo_path)
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($log->photo_path) }}" target="_blank" class="text-blue-600 hover:underline">photo</a>
                            @endif
                            <form method="POST" action="{{ route('admin.sewing.grid.defect.remove', [$sheet, $log]) }}" onsubmit="return confirm('Remove this log entry?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-500 hover:underline">remove</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </details>
    @endforeach
</div>
@elseif($isLocked)
<div class="mb-4 rounded-md bg-gray-50 border border-gray-200 p-3 text-sm text-gray-600">This sheet is locked. Results are read-only.</div>
@endif

<div class="text-sm text-gray-500">
    Sign-offs are completed on the <a href="{{ route('admin.sewing.sheets.show', $sheet) }}" class="text-blue-600 hover:underline">sheet summary</a>.
</div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.num-pad').forEach(function (pad) {
        var targetName = pad.getAttribute('data-target');
        var form = pad.closest('form');
        var input = form ? form.querySelector('input[name="' + targetName + '"]') : null;
        if (!input) return;

        pad.querySelectorAll('.num-pad-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                input.value = btn.getAttribute('data-value');
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    });

    document.querySelectorAll('.result-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var field = form.querySelector('input[name="defect_count"]');
            if (!field) return;

            var count = parseInt(field.value, 10);
            var amberMax = parseInt(form.getAttribute('data-amber-max'), 10);
            var alreadyReaudited = form.getAttribute('data-reaudit') === '1';

            if (!isNaN(count) && !isNaN(amberMax) && count > amberMax && !alreadyReaudited) {
                if (!confirm('This is a RED result (' + count + ' defects). A mandatory re-audit is required before this round can be closed. Continue?')) {
                    event.preventDefault();
                }
            }
        });
    });
})();
</script>
@endpush
