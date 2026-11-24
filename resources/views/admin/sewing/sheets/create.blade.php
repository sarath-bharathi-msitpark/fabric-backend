@extends('layouts.app')

@section('title', 'Create Inspection Sheet')
@section('header', 'Create Sewing Inspection Sheet')

@section('content')
<form method="POST" action="{{ route('admin.sewing.sheets.store') }}" id="sheet-form">
    @csrf

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <h3 class="font-semibold text-gray-800 text-sm mb-3">Order Header</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Inspection Date *</label>
                <input type="date" name="inspection_date" required value="{{ old('inspection_date', now()->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Line</label>
                <select name="sewing_line_id" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">—</option>
                    @foreach($lines as $line)<option value="{{ $line->id }}" @selected(old('sewing_line_id') == $line->id)>{{ $line->line_no }}{{ $line->name ? ' — '.$line->name : '' }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Shift</label>
                <input type="text" name="shift" value="{{ old('shift') }}" placeholder="e.g. A / B" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Rounds *</label>
                <input type="number" name="rounds" min="1" max="12" value="{{ old('rounds', $rule->default_rounds) }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Buyer</label>
                <select name="buyer_id" id="buyer_select" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">—</option>
                    @foreach($buyers as $buyer)<option value="{{ $buyer->id }}" @selected(old('buyer_id') == $buyer->id)>{{ $buyer->buyer_name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Style</label>
                <select name="style_id" id="style_select" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">—</option>
                    @foreach($styles as $style)
                        <option value="{{ $style->id }}" data-number="{{ $style->style_number }}" data-name="{{ $style->style_number }}" @selected(old('style_id') == $style->id)>{{ $style->style_number }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Style No</label>
                <input type="text" name="style_number" id="style_number" value="{{ old('style_number') }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Style Name</label>
                <input type="text" name="style_name" id="style_name" value="{{ old('style_name') }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Colour</label>
                <input type="text" name="color" value="{{ old('color') }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">PO#</label>
                <input type="text" name="po_number" value="{{ old('po_number') }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">LOT#</label>
                <input type="text" name="lot_number" value="{{ old('lot_number') }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Bundle#</label>
                <input type="text" name="bundle_no" value="{{ old('bundle_no') }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-800 text-sm">Operation Sequence *</h3>
            <button type="button" id="add-op" class="px-3 py-1.5 text-xs rounded-md bg-slate-700 text-white hover:bg-slate-800">+ Add Operation</button>
        </div>
        <div id="ops" class="space-y-3"></div>
        <p class="mt-2 text-xs text-gray-400">Each row is one operation on the line, inspected across every round. At least one operation is required.</p>
    </div>

    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2">
        <a href="{{ route('admin.sewing.sheets.index') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Cancel</a>
        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Create sheet</button>
    </div>
</form>

<script type="application/json" id="style-ops-data">@json($styleOperations)</script>

<template id="op-template">
    <div class="op-row grid grid-cols-1 lg:grid-cols-12 gap-2 items-end border border-gray-100 rounded-md p-3">
        <div class="lg:col-span-3">
            <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Operation</label>
            <select data-name="sewing_operation_id" class="op-select w-full rounded-md border-gray-300 text-sm">
                <option value="">Select operation</option>
                @foreach($operations as $op)<option value="{{ $op->id }}" data-name="{{ $op->name }}">{{ $op->name }}</option>@endforeach
            </select>
        </div>
        <div class="lg:col-span-3">
            <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Operation Name</label>
            <input type="text" data-name="operation_name" class="op-name w-full rounded-md border-gray-300 text-sm">
        </div>
        <div class="lg:col-span-2">
            <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Machine</label>
            <select data-name="sewing_machine_id" class="machine-select w-full rounded-md border-gray-300 text-sm">
                <option value="">—</option>
                @foreach($machines as $m)<option value="{{ $m->id }}" data-no="{{ $m->machine_no }}">{{ $m->machine_no }}</option>@endforeach
            </select>
        </div>
        <div class="lg:col-span-2">
            <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Operator</label>
            <select data-name="sewing_operator_id" class="w-full rounded-md border-gray-300 text-sm">
                <option value="">—</option>
                @foreach($operators as $o)<option value="{{ $o->id }}">{{ $o->name }} ({{ $o->employee_code }})</option>@endforeach
            </select>
        </div>
        <div class="lg:col-span-1">
            <label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-1">Machine No</label>
            <input type="text" data-name="machine_no" class="machine-no w-full rounded-md border-gray-300 text-sm">
        </div>
        <div class="lg:col-span-1 flex justify-end gap-1">
            <button type="button" class="move-up px-2 py-1.5 text-xs rounded-md bg-gray-50 text-gray-700 hover:bg-gray-100" title="Move up">↑</button>
            <button type="button" class="move-down px-2 py-1.5 text-xs rounded-md bg-gray-50 text-gray-700 hover:bg-gray-100" title="Move down">↓</button>
            <button type="button" class="remove-op px-2 py-1.5 text-xs rounded-md bg-red-50 text-red-700 hover:bg-red-100" title="Remove">✕</button>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
(function () {
    var ops = document.getElementById('ops');
    var template = document.getElementById('op-template');
    var counter = 0;

    function addRow(defaults) {
        defaults = defaults || {};
        var index = counter++;
        var node = template.content.firstElementChild.cloneNode(true);
        node.querySelectorAll('[data-name]').forEach(function (field) {
            field.name = 'operations[' + index + '][' + field.getAttribute('data-name') + ']';
        });

        var opSelect = node.querySelector('.op-select');
        var opName = node.querySelector('.op-name');
        var machineSelect = node.querySelector('.machine-select');
        var machineNo = node.querySelector('.machine-no');

        if (defaults.operation_id) opSelect.value = String(defaults.operation_id);
        if (defaults.operation_name) opName.value = defaults.operation_name;
        if (defaults.machine_id) machineSelect.value = String(defaults.machine_id);
        if (defaults.machine_no) machineNo.value = defaults.machine_no;

        opSelect.addEventListener('change', function () {
            var opt = opSelect.options[opSelect.selectedIndex];
            if (opt && opt.getAttribute('data-name')) opName.value = opt.getAttribute('data-name');
        });

        machineSelect.addEventListener('change', function () {
            var opt = machineSelect.options[machineSelect.selectedIndex];
            if (opt && opt.getAttribute('data-no')) machineNo.value = opt.getAttribute('data-no');
        });

        node.querySelector('.remove-op').addEventListener('click', function () {
            if (ops.querySelectorAll('.op-row').length > 1) node.remove();
        });
        node.querySelector('.move-up').addEventListener('click', function () {
            var prev = node.previousElementSibling;
            if (prev) ops.insertBefore(node, prev);
        });
        node.querySelector('.move-down').addEventListener('click', function () {
            var next = node.nextElementSibling;
            if (next) ops.insertBefore(next, node);
        });

        ops.appendChild(node);
        return node;
    }

    document.getElementById('add-op').addEventListener('click', function () { addRow(); });
    addRow();

    var styleOpsData = {};
    var dataEl = document.getElementById('style-ops-data');
    if (dataEl) {
        try { styleOpsData = JSON.parse(dataEl.textContent) || {}; } catch (e) { styleOpsData = {}; }
    }

    var styleSelect = document.getElementById('style_select');
    if (styleSelect) {
        styleSelect.addEventListener('change', function () {
            var opt = styleSelect.options[styleSelect.selectedIndex];
            if (opt && opt.value) {
                document.getElementById('style_number').value = opt.getAttribute('data-number') || '';
                document.getElementById('style_name').value = opt.getAttribute('data-name') || '';
            }

            var mapped = (opt && opt.value && styleOpsData[opt.value]) ? styleOpsData[opt.value] : null;
            if (mapped && mapped.length) {
                ops.innerHTML = '';
                mapped.forEach(function (row) { addRow(row); });
            }
        });
    }
})();
</script>
@endpush
