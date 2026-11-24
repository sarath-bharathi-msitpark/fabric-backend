@extends('layouts.app')

@section('title', 'Live Line Board')
@section('header', 'Live Line Board')

@section('actions')
    <span id="board-updated" class="text-xs text-gray-500 self-center"></span>
    <a href="{{ route('admin.sewing.dashboard') }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50 whitespace-nowrap">Dashboard</a>
@endsection

@section('content')
<div id="board" class="space-y-4">
    <div class="text-sm text-gray-400">Loading live data…</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var board = document.getElementById('board');
    var stamp = document.getElementById('board-updated');
    var dataUrl = @json(route('admin.sewing.live-board.data'));

    var colorFor = function (result) {
        if (result === 'green') return 'bg-green-100 text-green-800';
        if (result === 'amber') return 'bg-yellow-100 text-yellow-800';
        if (result === 'red') return 'bg-red-100 text-red-800';
        return 'bg-gray-100 text-gray-400';
    };

    var esc = function (value) {
        var d = document.createElement('div');
        d.textContent = value == null ? '' : String(value);
        return d.innerHTML;
    };

    function render(data) {
        if (!data.lines || data.lines.length === 0) {
            board.innerHTML = '<div class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-400 text-sm">No open inspection sheets right now.</div>';
            return;
        }

        board.innerHTML = data.lines.map(function (line) {
            var sheets = line.sheets.map(function (sheet) {
                var ops = sheet.operations.map(function (op) {
                    var label = op.count == null ? '—' : op.count;
                    var reaudit = op.reaudit ? ' <span class="text-[10px] uppercase">' + esc(op.reaudit) + '</span>' : '';
                    return '<div class="flex items-center justify-between gap-2 px-2 py-1 rounded ' + colorFor(op.result) + '">' +
                        '<span class="truncate text-xs">' + esc(op.name) + (op.operator ? ' <span class="text-[10px] opacity-70">(' + esc(op.operator) + ')</span>' : '') + '</span>' +
                        '<span class="text-xs font-semibold whitespace-nowrap">' + esc(label) + reaudit + '</span>' +
                    '</div>';
                }).join('');

                return '<div class="border border-gray-100 rounded-md p-3">' +
                    '<div class="flex items-center justify-between mb-2">' +
                        '<div><span class="font-mono text-sm font-semibold">' + esc(sheet.sheet_no) + '</span>' +
                        '<span class="ml-2 text-xs text-gray-500">' + esc(sheet.style || '') + ' · PO ' + esc(sheet.po || '—') + '</span></div>' +
                        '<span class="text-xs text-gray-500">Round ' + esc(sheet.current_round) + '</span>' +
                    '</div>' +
                    '<div class="space-y-1">' + ops + '</div>' +
                '</div>';
            }).join('');

            return '<div class="bg-white rounded-lg shadow-sm p-4">' +
                '<h3 class="font-semibold text-gray-800 text-sm mb-3">Line ' + esc(line.line) + '</h3>' +
                '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">' + sheets + '</div>' +
            '</div>';
        }).join('');
    }

    function load() {
        fetch(dataUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                render(data);
                if (stamp) stamp.textContent = 'Updated ' + new Date().toLocaleTimeString();
            })
            .catch(function () {
                board.innerHTML = '<div class="bg-white rounded-lg shadow-sm p-8 text-center text-red-500 text-sm">Could not load live data.</div>';
            });
    }

    load();
    setInterval(load, 30000);
})();
</script>
@endpush
